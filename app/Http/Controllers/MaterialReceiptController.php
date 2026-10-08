<?php

namespace App\Http\Controllers;

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
            'items.*.batch_number' => 'nullable|string|max:255',
            'items.*.mfg_date' => 'nullable|date',
            'items.*.exp_date' => 'nullable|date',
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
                    $batchNumber = trim((string) ($data['batch_number'] ?? ''));
                    if ($received > 0 && $item->material_type === 'raw_material' && $batchNumber === '') {
                        throw new DomainException('Nguyên liệu thô ' . ($item->catalog_item->name ?? '') . ' phải nhập số lô nhà cung cấp.');
                    }
                    if ($received > 0 && $item->material_type === 'accessory' && $batchNumber === '') {
                        $batchNumber = 'DN-' . now()->format('Ymd') . '-' . $item->id;
                    }
                    $isAccessory = $item->material_type === 'accessory';
                    if ($received > 0) {
                        MaterialStockMovement::create($base + [
                            'movement_type' => 'RECEIVE_PURCHASE',
                            'direction' => 'in',
                            'quantity' => $received,
                            'batch_number' => $batchNumber,
                            'mfg_date' => $isAccessory ? null : ($data['mfg_date'] ?? null),
                            'exp_date' => $isAccessory ? null : ($data['exp_date'] ?? null),
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

        return redirect()->route('warehouse.material-stock')->with('success', 'Đã nhập kho nguyên liệu thô/phụ liệu.');
    }
}
