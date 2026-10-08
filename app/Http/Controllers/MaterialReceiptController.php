<?php

namespace App\Http\Controllers;

use App\Models\MaterialLot;
use App\Models\MaterialStockMovement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialReceiptController extends Controller
{
    private function authorizeWarehouse(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isWarehouseDepartment() || $user->isWarehouseManager() || $user->isITDepartment()), 403, 'Chỉ bộ phận Kho hoặc IT mới có quyền nhập kho nguyên liệu thô/phụ liệu.');
    }

    private function materialItems(PurchaseOrder $purchaseOrder)
    {
        $items = $purchaseOrder->items()->whereNotNull('material_type')->with(['rawMaterial', 'accessory'])->get();
        foreach ($items as $item) {
            $item->setAttribute('remaining_quantity', max(0, (float) $item->quantity - $item->processedQuantity()));
        }

        return $items;
    }

    public function create(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeWarehouse();

        if ($purchaseOrder->status !== 'delivered') {
            return redirect()->route('warehouse.purchase-orders')->with('error', 'Kho cần xác nhận hàng đã đến trước khi nhập kho.');
        }

        $items = $this->materialItems($purchaseOrder);
        if ($items->isEmpty()) {
            return redirect()->route('warehouse.purchase-orders')->with('error', 'Đơn mua không có nguyên liệu thô/phụ liệu.');
        }

        return view('material-receipts.create', compact('purchaseOrder', 'items'));
    }

    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeWarehouse();

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.returned_quantity' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($purchaseOrder, $validated) {
                $locked = PurchaseOrder::whereKey($purchaseOrder->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== 'delivered') {
                    throw new DomainException('Đơn mua không còn chờ nhập kho.');
                }

                $items = PurchaseOrderItem::where('purchase_order_id', $locked->id)->whereNotNull('material_type')->lockForUpdate()->get()->keyBy('id');
                $submitted = array_map('intval', array_keys($validated['items']));
                if (array_diff($items->keys()->all(), $submitted) || array_diff($submitted, $items->keys()->all())) {
                    throw new DomainException('Phiếu nhận phải khớp chính xác các dòng nguyên liệu/phụ liệu của đơn mua.');
                }

                foreach ($validated['items'] as $itemId => $data) {
                    $item = $items->get((int) $itemId);
                    $received = (float) $data['received_quantity'];
                    $returned = (float) $data['returned_quantity'];
                    $remaining = max(0, (float) $item->quantity - $item->processedQuantity());
                    if ($received + $returned > $remaining + 0.0001) {
                        throw new DomainException('Tổng lượng đạt và trả của ' . ($item->catalog_item->name ?? 'dòng hàng') . " vượt lượng PO còn chưa xử lý ({$remaining}).");
                    }

                    $base = [
                        'material_type' => $item->material_type,
                        'material_id' => $item->material_id,
                        'unit' => $item->unit,
                        'purchase_order_item_id' => $item->id,
                        'user_id' => auth()->id(),
                        'note' => $data['note'] ?? null,
                    ];
                    if ($received > 0) {
                        MaterialLot::create([
                            'purchase_order_item_id' => $item->id,
                            'material_type' => $item->material_type,
                            'material_id' => $item->material_id,
                            'quantity' => $received,
                            'unit' => $item->unit,
                            'status' => 'pending_qa',
                            'received_by' => auth()->id(),
                        ]);
                    }
                    if ($returned > 0) {
                        MaterialStockMovement::create($base + [
                            'movement_type' => 'REJECT_PURCHASE',
                            'direction' => 'none',
                            'quantity' => $returned,
                        ]);
                    }
                }

                $resolved = PurchaseOrderItem::where('purchase_order_id', $locked->id)->get()
                    ->every(fn ($poItem) => $poItem->processedQuantity() + 0.0001 >= (float) $poItem->quantity);
                $locked->update(['status' => $resolved ? 'completed' : 'approved']);
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('warehouse.purchase-orders')->with('success', 'Đã nhập kho. Hàng chờ QC xác nhận mới vào tồn (nguyên liệu thô cần QA cập nhật số lô NCC/COA trước).');
    }
}
