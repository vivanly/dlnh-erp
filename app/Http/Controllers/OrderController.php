<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Ppcb;
use App\Models\ProductionMonthlyPlan;
use Carbon\Carbon;
use App\Services\SalesOrderPlanner;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function isItOrAdmin($user): bool
    {
        return $user && (
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with('customer', 'items.lotAllocations')->latest();

        $query = Order::with(['customer', 'items.lotAllocations', 'productionOrders'])->latest();
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.lotAllocations.supplierBatch', function ($sub) use ($search) {
                        $sub->where('batch_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.lotAllocations.finishedBatch', function ($sub) use ($search) {
                        $sub->where('batch_number', 'like', "%{$search}%");
                    });
            });
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('order_type') && in_array($request->input('order_type'), Order::TYPE_CODES, true)) {
            $query->where('order_type', $request->input('order_type'));
        }

        $orders = $query->paginate($this->perPage($request))->withQueryString();

        $canManageLockedOrders = $this->isItOrAdmin(auth()->user());
        $user = auth()->user();
        $canManageOrderDrafts = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            $this->isItOrAdmin($user)
        );
        $canSubmitOrders = $user && $user->canSubmitOrders();

        return view('orders.index', compact('orders', 'canManageLockedOrders', 'canManageOrderDrafts', 'canSubmitOrders'));
    }

    private function canApproveSalesOrder($user): bool
    {
        return $user && $user->canApproveSalesOrder();
    }

    public function salesApprovalQueue(Request $request)
    {
        abort_unless($this->canApproveSalesOrder(auth()->user()), 403);

        $orders = Order::with(['customer', 'items.product.ppcb', 'items.rawMaterial', 'items.ppcb'])
            ->where('status', 'pending_sales_approval')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('order_type') && in_array($request->input('order_type'), Order::TYPE_CODES, true), function ($query) use ($request) {
                $query->where('order_type', $request->input('order_type'));
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $canApproveSalesOrders = $this->canApproveSalesOrder(auth()->user());

        return view('orders.sales-approval-queue', compact('orders', 'canApproveSalesOrders'));
    }

    public function approveSalesOrder(Order $order)
    {
        abort_unless($this->canApproveSalesOrder(auth()->user()), 403);

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_sales_approval') {
                    throw new DomainException('Đơn hàng không còn chờ Giám đốc Kinh doanh duyệt.');
                }
                if ($lockedOrder->items()->count() === 0) {
                    throw new DomainException('Không thể duyệt đơn hàng chưa có dòng sản phẩm.');
                }

                $lockedOrder->update([
                    'status' => $lockedOrder->order_type === 'DLT' ? 'pending_material_issue' : 'pending_warehouse_check',
                    'sales_approved_at' => now(),
                    'sales_approved_by' => auth()->id(),
                    'sales_rejection_reason' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $order->order_type === 'DLT'
            ? 'Đã duyệt đơn dược liệu thô; đơn được chuyển sang Kho xuất hàng.'
            : 'Đã duyệt đơn bán; đơn được chuyển sang Kho xác nhận tồn.');
    }

    public function submitSalesOrder(Order $order)
    {
        $user = auth()->user();
        if (! $user || ! $user->canSubmitOrders()) {
            return back()->with('error', 'Bạn không có quyền gửi duyệt đơn bán. Chỉ Kinh doanh và IT được thực hiện.');
        }

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if (! in_array($lockedOrder->status, ['draft', 'sales_rejected'], true)) {
                    throw new DomainException('Chỉ đơn nháp hoặc đơn bị từ chối mới có thể gửi duyệt.');
                }
                if (! $lockedOrder->items()->exists()) {
                    throw new DomainException('Không thể gửi duyệt đơn hàng chưa có sản phẩm.');
                }

                $lockedOrder->update([
                    'status' => 'pending_sales_approval',
                    'sales_rejection_reason' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã gửi đơn hàng lên Giám đốc Kinh doanh duyệt.');
    }

    public function rejectSalesOrder(Request $request, Order $order)
    {
        abort_unless($this->canApproveSalesOrder(auth()->user()), 403);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_sales_approval') {
                    throw new DomainException('Đơn hàng không còn chờ Giám đốc Kinh doanh duyệt.');
                }

                $lockedOrder->update([
                    'status' => 'sales_rejected',
                    'sales_rejection_reason' => trim($validated['reason']),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã từ chối đơn bán và lưu lý do.');
    }

    public function productionPlanningIndex(Request $request, SalesOrderPlanner $planner)
    {
        $user = auth()->user();
        $canPlan = $user && (
            (method_exists($user, 'isPlanningDepartment') && $user->isPlanningDepartment()) ||
            $this->isItOrAdmin($user)
        );
        abort_unless($user, 403);

        $orders = Order::with(['customer', 'items.product', 'items.lotAllocations'])
            ->where('status', 'pending_planning')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order) use ($planner) {
            foreach ($order->items as $item) {
                $selectedQuantity = $planner->availableCoverage($item);
                $item->setAttribute('qa_selected_quantity', $selectedQuantity);
                $item->setAttribute('production_shortage', max(
                    0,
                    (float) $item->quantity - (float) $item->actual_quantity - $selectedQuantity,
                ));
            }
        });

        return view('production-planning.index', compact('orders', 'canPlan'));
    }

    public function productionPlanningHistory(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
        $month = $validated['month'] ?? null;
        $status = $validated['status'] ?? null;
        $start = $month ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay() : null;

        $orders = Order::with(['customer', 'productionOrders.product.ppcb', 'productionOrders.orderItem.ppcb'])
            ->whereHas('productionOrders', function ($query) use ($start) {
                if ($start) {
                    $query->whereBetween('created_at', [$start, $start->copy()->endOfMonth()]);
                }
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($this->perPage($request), ['*'], 'orders_page')
            ->withQueryString();

        $monthlyPlans = ProductionMonthlyPlan::with(['lines.product.ppcb', 'lines.ppcb'])
            ->when($start, fn ($query) => $query->whereDate('plan_month', $start->toDateString()))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('plan_month')
            ->paginate(20, ['*'], 'monthly_page')
            ->withQueryString();

        return view('production-planning.history', compact('orders', 'monthlyPlans', 'month', 'status'));
    }

    private function canApproveProductionPlan($user): bool
    {
        return $user && $user->canApproveProductionPlan();
    }

    public function productionApprovalQueue(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $orders = Order::with(['customer', 'items.product', 'productionOrders.product', 'productionOrders.materials.product'])
            ->where('status', 'pending_production_approval')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $canApproveProductionPlan = $this->canApproveProductionPlan(auth()->user());

        return view('production-planning.approval-queue', compact('orders', 'canApproveProductionPlan'));
    }

    public function approveProductionPlan(Order $order)
    {
        abort_unless($this->canApproveProductionPlan(auth()->user()), 403);

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::with('productionOrders')->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_production_approval' || $lockedOrder->productionOrders->isEmpty()) {
                    throw new DomainException('Kế hoạch không còn chờ giám đốc duyệt hoặc chưa có lệnh sản xuất.');
                }
                $ordersToApprove = $lockedOrder->productionOrders
                    ->where('status', 'pending_director_approval');
                if ($ordersToApprove->isEmpty()) {
                    throw new DomainException('Không có lệnh sản xuất mới đang chờ duyệt.');
                }

                foreach ($ordersToApprove as $productionOrder) {
                    $productionOrder->update(['status' => 'released']);
                }
                $lockedOrder->update([
                    'status' => 'waiting_production',
                    'production_plan_approved_at' => now(),
                    'production_plan_approved_by' => auth()->id(),
                    'production_plan_rejection_reason' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã duyệt kế hoạch; Kho có thể lập phiếu xuất nguyên liệu FIFO.');
    }

    public function rejectProductionPlan(Request $request, Order $order)
    {
        abort_unless($this->canApproveProductionPlan(auth()->user()), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::with('productionOrders')->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_production_approval') {
                    throw new DomainException('Kế hoạch không còn chờ duyệt.');
                }
                $lockedOrder->productionOrders()->where('status', 'pending_director_approval')->delete();
                $lockedOrder->update([
                    'status' => 'pending_planning',
                    'production_plan_approved_at' => null,
                    'production_plan_approved_by' => null,
                    'production_plan_rejection_reason' => trim($validated['reason']),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã trả kế hoạch về Kế hoạch để điều chỉnh.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        abort_unless($user && $user->canSubmitOrders(), 403);

        // Gợi ý mã đơn hàng tự động theo định dạng ĐH-YYYYMMDD-XXXX
        $orderType = request('order_type', 'DL');
        $orderDate = request('order_date', now()->toDateString());
        $suggestedCode = $this->suggestOrderCode($orderType, $orderDate);

        return view('orders.create', compact('suggestedCode', 'orderType', 'orderDate'));
    }

    public function suggestedCode(Request $request)
    {
        $validated = $request->validate([
            'order_type' => ['required', 'in:'.implode(',', Order::TYPE_CODES)],
            'order_date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json(['code' => $this->suggestOrderCode($validated['order_type'], $validated['order_date'])]);
    }

    private function suggestOrderCode(string $orderType, string $orderDate): string
    {
        abort_unless(in_array($orderType, Order::TYPE_CODES, true), 422);
        $prefix = $orderType.Carbon::parse($orderDate)->format('ym');
        $next = Order::where('order_code', 'like', $prefix.'%')
            ->pluck('order_code')
            ->map(fn ($code) => preg_match('/^'.preg_quote($prefix, '/').'([0-9]{4})$/', $code, $matches) ? (int) $matches[1] : 0)
            ->max() + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created resource in storage.
     */
    private function normalizeSaleItems(Request $request): void
    {
        $items = collect($request->input('items', []))->map(function ($item) {
            if (is_array($item) && empty($item['item_id']) && !empty($item['product_id'])) {
                $item['item_id'] = $item['product_id'];
            }

            return $item;
        })->all();

        $request->merge(['items' => $items]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && $user->canSubmitOrders(), 403);

        $this->normalizeSaleItems($request);
        $isRawMaterialOrder = $request->input('order_type') === 'DLT';

        $validated = $request->validate([
            'order_code' => ['required', 'string', 'regex:/^(DLT|DLSC|VTCT|TPCN|DL|VT|TCT)[0-9]{6,}$/', 'unique:orders,order_code'],
            'customer_id' => [
                'required',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    if (! Customer::whereKey($value)->where('is_active', true)->exists()) {
                        $fail('Không thể tạo đơn mới cho khách hàng đang không hoạt động.');
                    }
                },
            ],
            'order_type' => 'required|in:'.implode(',', Order::TYPE_CODES),
            'order_date' => 'required|date',
            'delivery_date' => 'required|date',
            'province_city' => 'required|string',
            'contact_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            // Validate cấu trúc mảng items gửi từ form tạo đơn
            'items' => 'required|array|min:1',
            'items.*.item_id' => ['required', 'integer', $isRawMaterialOrder ? 'exists:raw_materials,id' : 'exists:products,id'],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.packaging_spec' => 'nullable|string|max:255',
            'items.*.finished_quantity' => 'nullable|numeric|min:0',
            'items.*.ppcb_id' => 'nullable|exists:ppcb,id',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $expectedCodePrefix = $validated['order_type'].Carbon::parse($validated['order_date'])->format('ym');
        if (! preg_match('/^'.preg_quote($expectedCodePrefix, '/').'[0-9]{4,}$/', $validated['order_code'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'order_code' => 'Mã đơn phải đúng loại đơn và tháng nhận đơn (ví dụ: '.$expectedCodePrefix.'0001).',
            ]);
        }

        $validated['status'] = 'draft';

        DB::beginTransaction();
        try {
            // Tạo đơn hàng chính
            $order = Order::create([
                'order_code' => $validated['order_code'],
                'customer_id' => $validated['customer_id'],
                'order_type' => $validated['order_type'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'],
                'province_city' => $validated['province_city'],
                'contact_person' => $validated['contact_person'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            // Tạo các dòng vị thuốc dược liệu chi tiết
            foreach ($validated['items'] as $itemData) {
                $itemId = $itemData['item_id'];
                unset($itemData['item_id']);
                $order->items()->create($itemData + ($isRawMaterialOrder
                    ? ['raw_material_id' => $itemId, 'ppcb_id' => null]
                    : ['product_id' => $itemId]));
            }

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Đã lưu đơn ở trạng thái Nháp. Kiểm tra đơn rồi bấm Gửi duyệt trong danh sách.');
        } catch (DomainException $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo đơn hàng: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id, \App\Services\OrderProgressTimeline $progressTimeline)
    {
        $order = Order::with([
            'items.product',
            'items.rawMaterial',
            'items.ppcb',
            'items.labelPrints.printedBy',
            'items.lotAllocations.supplierBatch',
            'items.lotAllocations.finishedBatch',
            'customer',
            'salesApprovedBy',
            'productionPlanApprovedBy',
            'productionPackagedBy',
            'warehouseStockCheckedBy',
            'productionOrders.finishedBatches',
        ])->findOrFail($id);
        $canManageLockedOrders = $this->isItOrAdmin(auth()->user());
        $orderProgress = $progressTimeline->build($order);

        return view('orders.show', compact('order', 'canManageLockedOrders', 'orderProgress'));
    }

    public function sendToProduction(Request $request, Order $order, SalesOrderPlanner $planner)
    {
        $user = auth()->user();
        $canSendToProduction = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            (method_exists($user, 'isPlanningDepartment') && $user->isPlanningDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );

        abort_unless($canSendToProduction, 403);
        $validated = $request->validate([
            'planned_quantities' => 'nullable|array',
            'planned_quantities.*' => 'nullable|numeric|min:0',
            'selected_item_ids' => 'nullable|array|min:1',
            'selected_item_ids.*' => 'required|integer|exists:order_items,id',
        ]);

        try {
            $hasProductionShortage = $planner->plan(
                $order,
                $user->id,
                $validated['planned_quantities'] ?? [],
                $validated['selected_item_ids'] ?? null
            );
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $order->refresh();
        $message = match (true) {
            $hasProductionShortage => 'Đã kiểm tồn theo FIFO và lập lệnh sản xuất cho phần còn thiếu.',
            $order->status === 'waiting_finished_goods_receipt' => 'Đơn chờ Kho nhập đủ số lượng lô thành phẩm trước khi đóng gói.',
            default => 'Đã kiểm tồn theo FIFO; đơn sẵn sàng chuyển Kho đóng hàng.',
        };

        return back()->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $order = Order::with('items.product', 'customer', 'items.ppcb', 'items.lotAllocations')->findOrFail($id);
        if ($order->order_type === 'DLT') {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn dược liệu thô chưa hỗ trợ chỉnh sửa; hãy xóa và tạo lại đơn.');
        }
        if ($order->isEditLockedAfterProgress()) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã chuyển sang công đoạn tiếp theo, không thể chỉnh sửa.');
        }
        $user = auth()->user();
        $isSales = $user && method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment();
        $canEditDraft = ($isSales || $this->isItOrAdmin($user))
            && in_array($order->status, ['draft', 'sales_rejected'], true);
        abort_unless($canEditDraft || $this->isItOrAdmin($user), 403);

        if (in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) && ! $this->isItOrAdmin($user)) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được chỉnh sửa.');
        }

        // Lấy danh sách PPCB/YCBC để truyền sang form
        $ppcbList = Ppcb::all();

        return view('orders.edit', compact('order', 'ppcbList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        if ($order->order_type === 'DLT') {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn dược liệu thô chưa hỗ trợ chỉnh sửa; hãy xóa và tạo lại đơn.');
        }
        if ($order->isEditLockedAfterProgress()) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã chuyển sang công đoạn tiếp theo, không thể chỉnh sửa.');
        }
        $user = auth()->user();

        $isItOrAdmin = $this->isItOrAdmin($user);
        $isSales = $user && method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment();
        $canEditDraft = ($isSales || $isItOrAdmin) && in_array($order->status, ['draft', 'sales_rejected'], true);
        abort_unless($canEditDraft || $isItOrAdmin, 403);

        if (in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) && ! $isItOrAdmin) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được chỉnh sửa.');
        }

        $salesResubmission = $order->status === 'sales_rejected'
            && $user
            && (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment());

        DB::beginTransaction();
        try {
            // ==========================================
            // LUỒNG 2: ADMIN / IT / SALES (Cập nhật toàn bộ)
            // ==========================================
            $validated = $request->validate([
                'order_code' => 'required|unique:orders,order_code,'.$order->id,
                'customer_id' => [
                    'required',
                    'exists:customers,id',
                    function ($attribute, $value, $fail) use ($order) {
                        $isSameCustomer = (int) $value === (int) $order->customer_id;
                        if (! $isSameCustomer && ! Customer::whereKey($value)->where('is_active', true)->exists()) {
                            $fail('Không thể chuyển đơn sang khách hàng đang không hoạt động.');
                        }
                    },
                ],
                'order_type' => 'required|in:'.implode(',', Order::TYPE_CODES),
                'order_date' => 'required|date',
                'delivery_date' => 'required|date',
                'province_city' => 'required|string',
                'contact_person' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'status' => 'required|string|not_in:pending_qa',

                'items' => 'nullable|array',
                'items.*.id' => 'nullable|exists:order_items,id',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.packaging_spec' => 'required|numeric|min:0',
                'items.*.finished_quantity' => 'nullable|numeric|min:0',
                'items.*.ppcb_id' => 'nullable|exists:ppcb,id',
                'items.*.notes' => 'nullable|string|max:500',
            ]);

            // 1. Cập nhật thông tin chung đơn hàng
            $order->update([
                'order_code' => $validated['order_code'],
                'customer_id' => $validated['customer_id'],
                'order_type' => $validated['order_type'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'],
                'province_city' => $validated['province_city'],
                'contact_person' => $validated['contact_person'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $salesResubmission ? 'sales_rejected' : $validated['status'],
                'sales_rejection_reason' => $order->sales_rejection_reason,
            ]);

            // 2. Xử lý cập nhật / thêm mới danh sách vị thuốc (items)
            $submittedItemIds = [];

            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    $payload = [
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'packaging_spec' => $itemData['packaging_spec'],
                        'finished_quantity' => $itemData['finished_quantity'],
                        'ppcb_id' => $itemData['ppcb_id'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                    ];

                    if (! empty($itemData['id'])) {
                        $item = $order->items()->where('id', $itemData['id'])->first();
                        if ($item) {
                            $item->update($payload);
                            $submittedItemIds[] = $item->id;
                        }
                    } else {
                        $newItem = $order->items()->create($payload);
                        $submittedItemIds[] = $newItem->id;
                    }
                }
            }

            // 3. Xóa các dòng item cũ bị loại bỏ trên giao diện
            $removedItems = $order->items()->whereNotIn('id', $submittedItemIds)->get();
            foreach ($removedItems as $removedItem) {
                if ($removedItem->labelPrints()->exists()) {
                    throw new DomainException('Không thể xóa dòng đơn đã có lịch sử in nhãn.');
                }
                $removedItem->delete();
            }
            $order->update(['qa_confirmed_at' => null]);

            DB::commit();

            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Cập nhật thông tin đơn hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        $user = auth()->user();
        $canManageDraftOrder = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            $this->isItOrAdmin($user)
        );
        abort_unless($canManageDraftOrder, 403);
        if ($order->isApproved()) {
            return redirect()->route('orders.index')->with('error', 'Đơn hàng đã được duyệt, không thể xóa.');
        }
        if ($order->items()->whereHas('labelPrints')->exists()) {
            return redirect()->route('orders.index')->with('error', 'Đơn đã có lịch sử in nhãn, không thể xóa để bảo toàn đối soát tem.');
        }
        if (in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) && ! $this->isItOrAdmin(auth()->user())) {
            return redirect()->route('orders.index')->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được xóa.');
        }

        DB::beginTransaction();
        try {
            $order->items()->delete();
            $order->delete();

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Đã xóa đơn hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể xóa đơn hàng: '.$e->getMessage());
        }
    }
}
