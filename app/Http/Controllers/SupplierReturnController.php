<?php

namespace App\Http\Controllers;

use App\Models\MaterialStockMovement;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierReturnOrder;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierReturnController extends Controller
{
    private function canManage(): bool
    {
        $user = auth()->user();

        return $user && ($user->isQCDepartment() || $user->isQADepartment() || $user->isWarehouseDepartment() || $user->isWarehouseManager() || $user->isITDepartment());
    }

    private function returnedQuantity(int $itemId): float
    {
        return (float) SupplierReturnOrder::where('purchase_order_item_id', $itemId)->sum('quantity');
    }

    private function itemLots(PurchaseOrderItem $item)
    {
        $movements = $item->materialMovements()->whereIn('movement_type', ['RECEIVE_PURCHASE', 'RETURN_SUPPLIER'])->get()->groupBy(fn ($m) => (string) $m->batch_number);

        return $movements->map(function ($group, $batch) use ($item) {
            $received = (float) $group->where('movement_type', 'RECEIVE_PURCHASE')->sum('quantity');
            $returned = (float) $group->where('movement_type', 'RETURN_SUPPLIER')->sum('quantity');
            $balance = MaterialStockMovement::lotBalance($item->material_type, (int) $item->material_id, $batch);

            return (object) ['batch' => $batch, 'received' => $received, 'returned' => $returned, 'returnable' => max(0, min($received - $returned, $balance))];
        })->filter(fn ($lot) => $lot->received > 0)->values();
    }
    public function index(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $items = PurchaseOrderItem::with(['purchaseOrder.supplier', 'rawMaterial', 'accessory'])
            ->whereNotNull('material_type')
            ->whereHas('materialMovements', fn ($q) => $q->where('movement_type', 'RECEIVE_PURCHASE'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(fn ($q) => $q->whereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'like', "%{$search}%"))
                    ->orWhereHas('rawMaterial', fn ($m) => $m->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('accessory', fn ($m) => $m->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")));
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $items->getCollection()->each(function (PurchaseOrderItem $item) {
            $lots = $this->itemLots($item);
            $item->setAttribute('lots', $lots);
            $item->setAttribute('received_total', $lots->sum('received'));
            $item->setAttribute('returned_total', $lots->sum('returned'));
            $item->setAttribute('returnable', $lots->sum('returnable'));
        });

        $returnOrders = SupplierReturnOrder::with(['supplier', 'purchaseOrder'])
            ->latest()
            ->paginate($this->perPage($request), ['*'], 'returns_page')
            ->withQueryString();

        $canManage = $this->canManage();

        return view('warehouse.supplier-returns', compact('items', 'returnOrders', 'canManage'));
    }

    public function store(Request $request, PurchaseOrderItem $purchaseOrderItem)
    {
        abort_unless($this->canManage(), 403, 'Bạn không có quyền lập đơn trả nhà cung cấp.');

        $validated = $request->validate([
            'batch_number' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'reason' => ['required', 'string', 'max:1000'],
            'qc_test_report' => ['required', 'string', 'max:255'],
            'qc_date' => ['required', 'date'],
        ]);

        try {
            $returnOrder = DB::transaction(function () use ($purchaseOrderItem, $validated) {
                $item = PurchaseOrderItem::with('purchaseOrder')->whereKey($purchaseOrderItem->id)->lockForUpdate()->firstOrFail();
                if (!$item->material_type) {
                    throw new DomainException('Chỉ lập đơn trả cho nguyên liệu thô hoặc phụ liệu.');
                }

                $batch = (string) ($validated['batch_number'] ?? '');
                $lot = $this->itemLots($item)->firstWhere('batch', $batch);
                if (!$lot) {
                    throw new DomainException('Lô/đợt nhập không thuộc dòng đơn mua này.');
                }
                $returnable = $lot->returnable;
                $quantity = (float) $validated['quantity'];
                if ($quantity > $returnable + 0.0001) {
                    throw new DomainException('Số lượng trả vượt quá lượng còn trả được của lô/đợt ' . ($batch ?: '---') . ' hoặc vượt tồn lô (' . number_format(max(0, $returnable), 4) . ').');
                }

                $returnOrder = SupplierReturnOrder::create([
                    'supplier_id' => $item->purchaseOrder->supplier_id,
                    'purchase_order_id' => $item->purchase_order_id,
                    'purchase_order_item_id' => $item->id,
                    'material_type' => $item->material_type,
                    'material_id' => $item->material_id,
                    'quantity' => $quantity,
                    'unit' => $item->unit ?: 'kg',
                    'reason' => trim($validated['reason']),
                    'qc_test_report' => trim($validated['qc_test_report']),
                    'qc_date' => $validated['qc_date'],
                    'status' => 'pending_dispatch',
                    'created_by' => auth()->id(),
                ]);
                $returnOrder->update(['return_code' => 'RTN-' . now()->format('Ymd') . '-' . str_pad((string) $returnOrder->id, 6, '0', STR_PAD_LEFT)]);

                MaterialStockMovement::create([
                    'material_type' => $item->material_type,
                    'material_id' => $item->material_id,
                    'movement_type' => 'RETURN_SUPPLIER',
                    'direction' => 'out',
                    'quantity' => $quantity,
                    'unit' => $returnOrder->unit,
                    'purchase_order_item_id' => $item->id,
                    'batch_number' => $batch !== '' ? $batch : null,
                    'user_id' => auth()->id(),
                    'note' => $returnOrder->reason,
                ]);

                return $returnOrder;
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Đã lập đơn trả {$returnOrder->return_code}; tồn kho đã được trừ.");
    }

    public function dispatch(SupplierReturnOrder $supplierReturnOrder)
    {
        abort_unless($this->canManage(), 403);

        if ($supplierReturnOrder->status !== 'pending_dispatch') {
            return back()->with('error', 'Đơn trả không còn chờ giao.');
        }
        $supplierReturnOrder->update(['status' => 'dispatched']);

        return back()->with('success', 'Đã xác nhận giao trả hàng cho nhà cung cấp.');
    }
}
