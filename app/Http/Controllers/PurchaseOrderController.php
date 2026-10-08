<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    private function isItOrAdminPermission(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    // Hàm phụ trợ kiểm tra quyền Sales hoặc IT hoặc Admin
    private function checkSalesOrItPermission()
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $isSales = (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
                   (isset($user->department) && strtolower($user->department) === 'sales');

        $isIT = (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                (isset($user->department) && strtolower($user->department) === 'it') ||
                (isset($user->role) && strtolower($user->role) === 'it');

        return $isSales || $isIT;
    }

    // 1. Hiển thị danh sách đơn mua hàng kèm tìm kiếm và bộ lọc tối ưu
    public function searchItems(Request $request)
    {
        $controller = match ($request->get('type')) {
            'raw_material' => app(RawMaterialController::class),
            'accessory' => app(AccessoryController::class),
            default => app(ProductController::class),
        };

        return $controller->searchAjax($request);
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'user'])->orderBy('id', 'desc');

        // Lọc theo từ khóa (Mã PO hoặc Tên Nhà cung cấp)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('po_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('supplier', function($subQuery) use ($search) {
                      $subQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Giữ nguyên tham số bộ lọc khi phân trang
        $purchaseOrders = $query->paginate($this->perPage($request))->appends($request->query());
            
        $canManageLockedPurchaseOrders = $this->isItOrAdminPermission();

        return view('purchase-orders.index', compact('purchaseOrders', 'canManageLockedPurchaseOrders'));
    }

    // 2. Hiển thị form tạo đơn mua hàng
    public function create()
    {
        // Kiểm tra phân quyền: Chỉ nhân viên Kinh doanh hoặc IT mới được tạo đơn mua hàng
        if (!$this->checkSalesOrItPermission()) {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Chỉ bộ phận Kinh doanh hoặc IT mới có quyền tạo đơn mua hàng.');
        }

        return view('purchase-orders.create');
    }

    private function normalizeItems(Request $request): void
    {
        $items = collect($request->input('items', []))->map(function ($item) {
            if (is_array($item) && empty($item['item_type']) && !empty($item['product_id'])) {
                $item['item_type'] = 'product';
                $item['item_id'] = $item['product_id'];
            }

            return $item;
        })->all();

        $request->merge(['items' => $items]);
    }

    // 5. Lưu đơn mua hàng mới xuống Database (Mặc định ở trạng thái draft - Nháp)
    public function store(Request $request)
    {
        // Kiểm tra phân quyền: Chỉ nhân viên Kinh doanh hoặc IT mới được lưu đơn mua hàng
        if (!$this->checkSalesOrItPermission()) {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Chỉ bộ phận Kinh doanh hoặc IT mới có quyền tạo đơn mua hàng.');
        }

        $this->normalizeItems($request);

        $request->validate([
            'po_number' => 'required|string|max:50|unique:purchase_orders,po_number',
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:product,raw_material,accessory',
            'items.*.item_id' => ['required', 'integer', function ($attribute, $value, $fail) use ($request) {
                $index = explode('.', $attribute)[1];
                if (!PurchaseOrderItem::catalogExists((string) $request->input("items.{$index}.item_type"), (int) $value)) {
                    $fail('Hàng hóa chọn trong đơn mua không tồn tại.');
                }
            }],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ], [
            'po_number.required' => 'Vui lòng nhập mã đơn mua hàng.',
            'po_number.unique' => 'Mã đơn mua hàng này đã tồn tại trong hệ thống.',
            'supplier_id.required' => 'Vui lòng chọn nhà cung cấp.',
            'items.required' => 'Đơn mua hàng phải có ít nhất một vị thuốc.',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $poNumber = trim($request->po_number);

            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $request->supplier_id,
                'user_id' => auth()->id() ?? 1,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'status' => 'draft', // Khởi tạo ở trạng thái Nháp
                'subtotal' => $subtotal,
                'tax_amount' => $subtotal * 0.05,
                'grand_total' => $subtotal * 1.05,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    ...PurchaseOrderItem::catalogColumns($item['item_type'], (int) $item['item_id']),
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Kg',
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.index')->with('success', 'Tạo đơn nháp mua dược liệu thành công! Mã PO: ' . $poNumber);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi hệ thống: ' . $e->getMessage()])->withInput();
        }
    }

    // 6. Hiển thị form Sửa đơn hàng (Cho phép sửa khi ở trạng thái draft hoặc rejected)
    public function edit($id)
    { 
        // Kiểm tra phân quyền: Chỉ bộ phận Kinh doanh hoặc IT mới được sửa đơn hàng
        if (!$this->checkSalesOrItPermission()) {
            return redirect()->route('purchase-orders.index')->with('error', 'Chỉ bộ phận Kinh doanh hoặc IT mới có quyền chỉnh sửa đơn mua hàng.');
        }

        $purchaseOrder = PurchaseOrder::with(['supplier', 'items.product', 'items.rawMaterial', 'items.accessory'])->findOrFail($id);
        
        if (!in_array($purchaseOrder->status, ['draft', 'rejected'], true) && !$this->isItOrAdminPermission()) {
            return redirect()->route('purchase-orders.index')->with('error', 'Chỉ được chỉnh sửa đơn hàng đang ở trạng thái Nháp hoặc Bị từ chối.');
        }

        return view('purchase-orders.edit', compact('purchaseOrder'));
    }

    // 7. Cập nhật đơn hàng
    public function update(Request $request, $id)
    { 
        // Kiểm tra phân quyền: Chỉ bộ phận Kinh doanh hoặc IT mới được sửa đơn hàng
        if (!$this->checkSalesOrItPermission()) {
            return redirect()->route('purchase-orders.index')->with('error', 'Chỉ bộ phận Kinh doanh hoặc IT mới có quyền chỉnh sửa đơn mua hàng.');
        }
        
        $this->normalizeItems($request);

        $request->validate([
            'po_number' => 'required|string|max:50|unique:purchase_orders,po_number,' . $id,
            'supplier_id' => 'required|exists:suppliers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:product,raw_material,accessory',
            'items.*.item_id' => ['required', 'integer', function ($attribute, $value, $fail) use ($request) {
                $index = explode('.', $attribute)[1];
                if (!PurchaseOrderItem::catalogExists((string) $request->input("items.{$index}.item_type"), (int) $value)) {
                    $fail('Hàng hóa chọn trong đơn mua không tồn tại.');
                }
            }],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if (!in_array($purchaseOrder->status, ['draft', 'rejected'], true) && !$this->isItOrAdminPermission()) {
                DB::rollBack();
                return redirect()->route('purchase-orders.index')->with('error', 'Đơn hàng này đã được gửi duyệt hoặc xử lý, không thể chỉnh sửa.');
            }

            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }

            $purchaseOrder->update([
                'po_number' => trim($request->po_number),
                'supplier_id' => $request->supplier_id,
                'order_date' => $request->order_date,
                'expected_delivery_date' => $request->expected_delivery_date,
                'subtotal' => $subtotal,
                'tax_amount' => $subtotal * 0.05,
                'grand_total' => $subtotal * 1.05,
                'notes' => $request->notes,
            ]);

            // Xóa các chi tiết cũ và tạo lại danh sách mới
            $purchaseOrder->items()->delete();

            foreach ($request->items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    ...PurchaseOrderItem::catalogColumns($item['item_type'], (int) $item['item_id']),
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? 'Kg',
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return redirect()->route('purchase-orders.show', $purchaseOrder->id)->with('success', 'Cập nhật đơn mua hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Lỗi hệ thống: ' . $e->getMessage()])->withInput();
        }
    }

    // 8. Chức năng Gửi duyệt đơn hàng (Chuyển từ draft/rejected sang pending)
    public function submit($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if (!in_array($purchaseOrder->status, ['draft', 'rejected'])) {
                return back()->with('error', 'Đơn hàng không ở trạng thái có thể gửi duyệt.');
            }

            $purchaseOrder->update([
                'status' => 'pending',
                'rejection_reason' => null
            ]);

            return back()->with('success', 'Đã gửi đơn mua dược liệu tới bộ phận Kinh doanh.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Không thể gửi duyệt đơn hàng: ' . $e->getMessage()]);
        }
    }

    // Alias để tương thích nếu route cũ gọi submitForApproval
    public function submitForApproval($id)
    {
        return $this->submit($id);
    }

    // 9. Giám đốc Kinh doanh phê duyệt (Pending -> Approved)
    public function approve($id)
    {
        abort_unless(auth()->user()->canApprovePurchaseOrder(), 403);

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status !== 'pending') {
                return back()->with('error', 'Đơn hàng này không ở trạng thái chờ duyệt.');
            }

            $purchaseOrder->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            return back()->with('success', 'Đã phê duyệt đơn hàng thành công.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Lỗi phê duyệt: ' . $e->getMessage()]);
        }
    }

    // 10. Giám đốc Kinh doanh từ chối (Pending -> Rejected)
    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        abort_unless(auth()->user()->canApprovePurchaseOrder(), 403);

        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            if ($purchaseOrder->status !== 'pending') {
                return back()->with('error', 'Đơn hàng này không ở trạng thái chờ duyệt.');
            }

            $purchaseOrder->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason
            ]);

            return back()->with('success', 'Đã từ chối đơn hàng.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Lỗi từ chối đơn hàng: ' . $e->getMessage()]);
        }
    }

    // 12. Xóa đơn hàng (Chỉ cho phép xóa khi ở trạng thái draft)
    public function destroy($id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findOrFail($id);

            $isItOrAdmin = $this->isItOrAdminPermission();
            if ($purchaseOrder->status !== 'draft' && !$isItOrAdmin) {
                return back()->with('error', 'Đơn mua đã duyệt, chỉ IT/Admin mới được xóa.');
            }

            if ($purchaseOrder->status === 'draft' && !$this->checkSalesOrItPermission()) {
                return back()->with('error', 'Bạn không có quyền xóa đơn mua hàng này.');
            }

            $purchaseOrder->items()->delete();
            $purchaseOrder->delete();

            return redirect()->route('purchase-orders.index')->with('success', 'Đã xóa đơn mua hàng thành công!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Không thể xóa đơn hàng: ' . $e->getMessage()]);
        }
    }

    // 13. Xem chi tiết đơn mua hàng (Đã tối ưu thêm relation goodsReceipts để thống kê kho/QC)
    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with([
            'supplier', 
            'user', 
            'items.product',
            'items.rawMaterial',
            'items.accessory', 
            'goodsReceipts.items'
        ])->findOrFail($id);
        
        $canManageLockedPurchaseOrders = $this->isItOrAdminPermission();

        return view('purchase-orders.show', compact('purchaseOrder', 'canManageLockedPurchaseOrders'));
    }
}