<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierBatch;
use App\Models\PurchaseOrder;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    // Hàm phụ trợ kiểm tra quyền Kho (Warehouse Manager) hoặc IT hoặc Admin
    private function checkWarehouseOrItPermission()
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $isWarehouse = (method_exists($user, 'isWarehouseManager') && $user->isWarehouseManager()) ||
                       (isset($user->department) && strtolower($user->department) === 'warehouse');

        $isIT = (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                (isset($user->department) && strtolower($user->department) === 'it') ||
                (isset($user->role) && strtolower($user->role) === 'it');

        return $isWarehouse || $isIT;
    }

    public function index(Request $request)
    {
        $query = GoodsReceipt::with(['purchaseOrder.supplier', 'receiver'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('receipt_code', 'like', "%{$search}%")
                  ->orWhereHas('purchaseOrder', function($sub) use ($search) {
                      $sub->where('po_number', 'like', "%{$search}%");
                  });
            });
        }

        $goodsReceipts = $query->paginate($this->perPage($request))->withQueryString();
        return view('goods-receipts.index', compact('goodsReceipts'));
    }

    public function create(PurchaseOrder $purchaseOrder)
    {
        if (!$this->checkWarehouseOrItPermission()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder->id)
                ->with('error', 'Chỉ bộ phận Kho hoặc IT mới có quyền lập phiếu nhận hàng và kiểm định QC.');
        }

        if ($purchaseOrder->status !== 'delivered') {
            return redirect()->route('warehouse.purchase-orders')
                ->with('error', 'Kho cần xác nhận hàng đã đến trước khi lập phiếu nhập kho.');
        }

        $purchaseOrder->load('supplier', 'items.product', 'items.goodsReceiptItems');
        $purchaseOrder->setRelation('items', $purchaseOrder->items->whereNotNull('product_id')->values());
        if ($purchaseOrder->items->isEmpty()) {
            return redirect()->route('material-receipts.create', $purchaseOrder);
        }
        foreach ($purchaseOrder->items as $item) {
            $processedQuantity = $item->goodsReceiptItems->sum(fn ($receiptItem) => (float) $receiptItem->received_quantity + (float) $receiptItem->returned_quantity);
            $item->setAttribute('processed_quantity', $processedQuantity);
            $item->setAttribute('remaining_quantity', max(0, (float) $item->quantity - $processedQuantity));
        }
        return view('goods-receipts.create', compact('purchaseOrder'));
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder, InventoryLedger $inventoryLedger)
    {
        if (!$this->checkWarehouseOrItPermission()) {
            return redirect()->route('purchase-orders.show', $purchaseOrder->id)
                ->with('error', 'Chỉ bộ phận Kho hoặc IT mới có quyền thực hiện lưu phiếu nhận hàng.');
        }

        if ($purchaseOrder->status !== 'delivered') {
            return redirect()->route('warehouse.purchase-orders')
                ->with('error', 'Chỉ lập phiếu nhập kho sau khi Kho xác nhận hàng đã đến.');
        }

        $purchaseOrder->load('items');
        $purchaseOrder->setRelation('items', $purchaseOrder->items->whereNotNull('product_id')->values());
        $rules = [
            'receipt_code' => 'nullable|string|unique:goods_receipts,receipt_code|max:255',
            'receipt_date' => 'required|date',
            'items' => 'required|array',
        ];
        foreach ($purchaseOrder->items as $item) {
            $rules["items.{$item->id}.product_id"] = ['required', 'in:' . $item->product_id];
            $rules["items.{$item->id}.received_quantity"] = 'required|numeric|min:0';
            $rules["items.{$item->id}.returned_quantity"] = 'required|numeric|min:0';
            $rules["items.{$item->id}.return_reason"] = 'nullable|string|max:1000';
            $rules["items.{$item->id}.batch_number"] = 'nullable|string|max:255';
            $rules["items.{$item->id}.mfg_date"] = 'nullable|date';
            $rules["items.{$item->id}.exp_date"] = 'nullable|date';
        }
        $validated = $request->validate($rules, [
            'receipt_code.unique' => 'Mã phiếu nhập kho này đã tồn tại trên hệ thống, vui lòng nhập mã khác.',
        ]);

        $expectedItemIds = $purchaseOrder->items->modelKeys();
        $submittedItemIds = array_map('intval', array_keys($validated['items']));
        if (array_diff($expectedItemIds, $submittedItemIds) || array_diff($submittedItemIds, $expectedItemIds)) {
            return back()->withInput()->with('error', 'Phiếu nhận phải khớp chính xác các dòng hàng của đơn mua.');
        }

        DB::beginTransaction();
        try {
            $lockedPurchaseOrder = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
            if ($lockedPurchaseOrder->status !== 'delivered') {
                throw new \DomainException('Đơn mua không còn chờ nhập kho; phiếu nhập đã được xử lý hoặc trạng thái đã thay đổi.');
            }

            $lockedItems = PurchaseOrderItem::where('purchase_order_id', $lockedPurchaseOrder->id)
                ->whereNotNull('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($lockedItems->keys()->map(fn ($id) => (int) $id)->sort()->values()->all() !== collect($expectedItemIds)->map(fn ($id) => (int) $id)->sort()->values()->all()) {
                throw new \DomainException('Danh sách dòng hàng của đơn mua đã thay đổi; vui lòng tải lại phiếu.');
            }

            $receiptCode = $request->filled('receipt_code') 
                ? $request->receipt_code 
                : 'GRN-' . date('Ymd') . '-' . rand(1000, 9999);

            $goodsReceipt = GoodsReceipt::create([
                'purchase_order_id' => $lockedPurchaseOrder->id,
                'receipt_code' => $receiptCode,
                'receipt_date' => $request->receipt_date,
                'receiver_id' => auth()->id(),
                'qc_status' => 'pending',
                'notes' => $request->notes,
            ]);

            foreach ($validated['items'] as $itemId => $data) {
                $poItem = $lockedItems->get((int) $itemId);
                if (!$poItem || (int) $data['product_id'] !== (int) $poItem->product_id) {
                    throw new \DomainException('Dòng hàng nhận không thuộc đơn mua đang xử lý.');
                }
                
                $receivedQty = isset($data['received_quantity']) ? (float) $data['received_quantity'] : 0; // Số lượng ĐẠT
                $returnedQty = isset($data['returned_quantity']) ? (float) $data['returned_quantity'] : 0; // Số lượng LỖI
                $previouslyProcessed = (float) GoodsReceiptItem::query()
                    ->where('purchase_order_item_id', $poItem->id)
                    ->selectRaw('COALESCE(SUM(received_quantity + returned_quantity), 0) as processed_quantity')
                    ->value('processed_quantity');
                $remainingQuantity = max(0, (float) $poItem->quantity - $previouslyProcessed);
                if ($receivedQty + $returnedQty > $remainingQuantity + 0.0001) {
                    throw new \DomainException("Tổng lượng đạt và trả của {$poItem->product->name} vượt lượng PO còn chưa xử lý ({$remainingQuantity}).");
                }
                $returnReason = $data['return_reason'] ?? null;
                $productId = $poItem->product_id ?? $poItem->herb_id;

                // SỬA LẠI: Lượng thực nhận vào kho chính là số lượng ĐẠT ($receivedQty)
                $netReceivedQty = max(0, $receivedQty);

                // 1. Tạo chi tiết dòng nhận hàng (lưu đầy đủ số lượng nhận đạt và trả lỗi để theo dõi lịch sử)
                $receiptItem = GoodsReceiptItem::create([
                    'goods_receipt_id' => $goodsReceipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'herb_id' => $productId, 
                    'ordered_quantity' => $poItem->quantity,
                    'received_quantity' => $receivedQty,
                    'returned_quantity' => $returnedQty,
                    'return_reason' => $returnReason,
                ]);

                // 2. Chỉ tạo Lô gốc (SupplierBatches) NẾU lượng thực nhận đạt lớn hơn 0
                if ($netReceivedQty > 0) {
                    $batchNumber = !empty($data['batch_number']) 
                        ? $data['batch_number'] 
                        : 'LOT-' . date('Ymd') . '-' . $receiptItem->id;

                    $supplierBatch = SupplierBatch::create([
                        'goods_receipt_item_id' => $receiptItem->id,
                        'product_id' => $productId,
                        'batch_number' => $batchNumber,
                        'initial_quantity' => $netReceivedQty, // Khớp chính xác với số lượng Đạt
                        'current_quantity' => 0,
                        'mfg_date' => $data['mfg_date'] ?? null,
                        'exp_date' => $data['exp_date'] ?? null,
                        'status' => 'pending_qa',
                    ]);

                    $inventoryLedger->post(
                        $supplierBatch,
                        'RECEIVE_PURCHASE',
                        'in',
                        $netReceivedQty,
                        $poItem->unit,
                        GoodsReceipt::class,
                        $goodsReceipt->id,
                        auth()->id(),
                    );
                }
            }

            $allItemsResolved = true;
            foreach (PurchaseOrderItem::where('purchase_order_id', $lockedPurchaseOrder->id)->get() as $poItem) {
                $processedQuantity = $poItem->processedQuantity();
                if ($processedQuantity + 0.0001 < (float) $poItem->quantity) {
                    $allItemsResolved = false;
                    break;
                }
            }

            $lockedPurchaseOrder->update(['status' => $allItemsResolved ? 'completed' : 'approved']);

            DB::commit();

            return redirect()->route('warehouse.purchase-orders')
                ->with('success', $allItemsResolved
                    ? 'Đã xử lý đủ lượng đạt/trả của đơn mua, PO đã hoàn tất.'
                    : 'Đã ghi nhận đợt nhận hàng; PO còn lượng chưa xử lý và tiếp tục chờ đợt giao sau.');

        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['purchaseOrder.supplier', 'items.herb', 'items.supplierBatches', 'receiver']);
        return view('goods-receipts.show', compact('goodsReceipt'));
    }
}