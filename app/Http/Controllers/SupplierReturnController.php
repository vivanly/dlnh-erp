<?php

namespace App\Http\Controllers;

use App\Models\MaterialLot;
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

    private function failedLots(PurchaseOrderItem $item)
    {
        return MaterialLot::query()
            ->where('purchase_order_item_id', $item->id)
            ->where('status', 'rejected')
            ->orderBy('id')
            ->get()
            ->map(function (MaterialLot $lot) {
                $returned = (float) SupplierReturnOrder::where('material_lot_id', $lot->id)->sum('quantity');

                return (object) [
                    'id' => $lot->id,
                    'batch' => $lot->batch_number ?: 'Lô nhập #' . $lot->id,
                    'received' => (float) $lot->quantity,
                    'returned' => $returned,
                    'returnable' => max(0, (float) $lot->quantity - $returned),
                    'qc_note' => $lot->qc_note,
                ];
            });
    }

    public function index(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $items = PurchaseOrderItem::with(['purchaseOrder.supplier', 'rawMaterial', 'accessory'])
            ->whereNotNull('material_type')
            ->whereHas('materialLots', fn ($query) => $query->where('status', 'rejected'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(fn ($subQuery) => $subQuery
                    ->whereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'like', "%{$search}%"))
                    ->orWhereHas('rawMaterial', fn ($material) => $material->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('accessory', fn ($material) => $material->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")));
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $items->getCollection()->each(function (PurchaseOrderItem $item) {
            $lots = $this->failedLots($item);
            $item->setAttribute('lots', $lots);
            $item->setAttribute('received_total', $lots->sum('received'));
            $item->setAttribute('returned_total', $lots->sum('returned'));
            $item->setAttribute('returnable', $lots->sum('returnable'));
        });

        $returnOrders = SupplierReturnOrder::with(['supplier', 'purchaseOrder', 'materialLot'])
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
            'material_lot_id' => ['required', 'integer', 'exists:material_lots,id'],
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

                $lot = MaterialLot::whereKey($validated['material_lot_id'])
                    ->where('purchase_order_item_id', $item->id)
                    ->lockForUpdate()
                    ->first();
                if (!$lot || $lot->status !== 'rejected') {
                    throw new DomainException('Chỉ được trả lô đã được QC kết luận không đạt.');
                }

                $returned = (float) SupplierReturnOrder::where('material_lot_id', $lot->id)->sum('quantity');
                $returnable = max(0, (float) $lot->quantity - $returned);
                $quantity = (float) $validated['quantity'];
                if ($quantity > $returnable + 0.0001) {
                    throw new DomainException('Số lượng trả vượt quá lượng còn lại của lô QC không đạt (' . number_format($returnable, 4) . ').');
                }

                $returnOrder = SupplierReturnOrder::create([
                    'supplier_id' => $item->purchaseOrder->supplier_id,
                    'purchase_order_id' => $item->purchase_order_id,
                    'purchase_order_item_id' => $item->id,
                    'material_lot_id' => $lot->id,
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

                return $returnOrder;
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Đã lập đơn trả {$returnOrder->return_code} cho lô QC không đạt.");
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
