<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ProductBom;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\SalesOrderLotAllocation;
use App\Models\SalesOrderReturn;
use App\Models\SalesOrderReturnItem;
use App\Models\SupplierBatch;
use App\Services\InventoryLedger;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderReturnController extends Controller
{
    public function index()
    {
        abort_unless($this->canViewReturns(), 403);

        $orders = Order::with([
            'customer',
            'items.product',
            'items.lotAllocations.finishedBatch',
            'items.lotAllocations.supplierBatch',
        ])
            ->where('status', 'completed')
            ->whereNotNull('warehouse_confirmed_at')
            ->where('warehouse_confirmed_at', '>=', now()->subDays(30))
            ->latest('warehouse_confirmed_at')
            ->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                foreach ($item->lotAllocations->where('shipped_quantity', '>', 0) as $allocation) {
                    $returned = (float) SalesOrderReturnItem::where('sales_order_lot_allocation_id', $allocation->id)->sum('quantity');
                    $allocation->setAttribute('returnable_quantity', max(0, (float) $allocation->shipped_quantity - $returned));
                }
            }
        }

        $returnItems = SalesOrderReturnItem::with([
            'salesOrderReturn.order.customer',
            'orderItem.product',
            'allocation',
            'productionFinishedBatch',
            'supplierBatch',
            'reworkProductionOrder',
        ])
            ->latest()
            ->paginate(30);

        return view('warehouse.customer-returns', compact('orders', 'returnItems'));
    }

    public function receive(Request $request, Order $order)
    {
        abort_unless($this->canReceiveReturns(), 403);
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if (
                    $lockedOrder->status !== 'completed'
                    || ! $lockedOrder->warehouse_confirmed_at
                    || $lockedOrder->warehouse_confirmed_at->lt(now()->subDays(30))
                ) {
                    throw new DomainException('Chỉ nhận hàng trả trong vòng 30 ngày kể từ khi đơn được giao.');
                }

                $return = SalesOrderReturn::create([
                    'order_id' => $lockedOrder->id,
                    'received_by' => auth()->id(),
                    'received_at' => now(),
                    'status' => 'pending_inspection',
                    'note' => $validated['note'] ?? null,
                ]);
                $createdItems = 0;

                foreach ($validated['items'] as $allocationId => $data) {
                    $quantity = round((float) ($data['quantity'] ?? 0), 4);
                    if ($quantity <= 0) {
                        continue;
                    }

                    $allocation = SalesOrderLotAllocation::with('orderItem')
                        ->whereKey($allocationId)
                        ->lockForUpdate()
                        ->firstOrFail();
                    if (
                        (int) $allocation->orderItem->order_id !== (int) $lockedOrder->id
                        || (float) $allocation->shipped_quantity <= 0
                    ) {
                        throw new DomainException('Chỉ nhận trả theo đúng lô đã giao của đơn hàng.');
                    }

                    $alreadyReturned = (float) SalesOrderReturnItem::where('sales_order_lot_allocation_id', $allocation->id)
                        ->sum('quantity');
                    $returnable = max(0, (float) $allocation->shipped_quantity - $alreadyReturned);
                    if ($quantity > $returnable + 0.0001) {
                        throw new DomainException("Số lượng trả lô {$allocation->id} vượt mức còn được nhận ({$returnable}).");
                    }

                    $return->items()->create([
                        'order_item_id' => $allocation->order_item_id,
                        'sales_order_lot_allocation_id' => $allocation->id,
                        'production_finished_batch_id' => $allocation->production_finished_batch_id,
                        'supplier_batch_id' => $allocation->supplier_batch_id,
                        'quantity' => $quantity,
                        'status' => 'pending_inspection',
                    ]);
                    $createdItems++;
                }

                if ($createdItems === 0) {
                    throw new DomainException('Nhập số lượng trả lớn hơn 0 cho ít nhất một lô đã giao.');
                }
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã tiếp nhận hàng trả vào khu cách ly. Tồn kho chưa thay đổi; chờ QC quyết định.');
    }

    public function inspect(Request $request, SalesOrderReturnItem $salesOrderReturnItem, InventoryLedger $inventoryLedger)
    {
        abort_unless($this->canInspectReturns(), 403);
        $validated = $request->validate([
            'decision' => ['required', 'in:restock,rework,discard'],
            'inspection_note' => ['required', 'string', 'max:2000'],
        ]);

        try {
            DB::transaction(function () use ($salesOrderReturnItem, $validated, $inventoryLedger) {
                $item = SalesOrderReturnItem::with(['orderItem.product', 'salesOrderReturn'])
                    ->whereKey($salesOrderReturnItem->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($item->status !== 'pending_inspection') {
                    throw new DomainException('Phiếu trả này đã được QC xử lý.');
                }

                $decision = $validated['decision'];
                if ($decision === 'restock') {
                    $batch = $item->production_finished_batch_id
                        ? ProductionFinishedBatch::whereKey($item->production_finished_batch_id)->lockForUpdate()->firstOrFail()
                        : SupplierBatch::whereKey($item->supplier_batch_id)->lockForUpdate()->firstOrFail();
                    if ($batch->status !== 'active') {
                        throw new DomainException('Chỉ được nhập lại đúng lô nguồn đang hoạt động; lô không hoạt động cần tái sản xuất hoặc hủy.');
                    }
                    if ($batch instanceof ProductionFinishedBatch && ($batch->qc_result !== 'passed' || ! $batch->qc_test_report_file)) {
                        throw new DomainException('Lô thành phẩm phải có PKN đạt trước khi hàng trả được nhập lại tồn kho.');
                    }
                    if ($batch instanceof SupplierBatch && ! $batch->coa_file) {
                        throw new DomainException('Lô NCC chưa có COA được duyệt nên không thể nhập lại tồn.');
                    }

                    $inventoryLedger->post(
                        $batch,
                        'CUSTOMER_RETURN',
                        'in',
                        (float) $item->quantity,
                        $item->orderItem->product->unit,
                        SalesOrderReturnItem::class,
                        $item->id,
                        auth()->id(),
                        'QC chấp nhận hàng khách trả về đúng lô nguồn.',
                    );
                } elseif ($decision === 'rework') {
                    $orderItem = $item->orderItem()->lockForUpdate()->firstOrFail();
                    $order = Order::whereKey($item->salesOrderReturn->order_id)->lockForUpdate()->firstOrFail();
                    $bom = ProductBom::where('product_id', $orderItem->product_id)
                        ->where('is_active', true)
                        ->latest('version')
                        ->first();
                    if (! $bom) {
                        throw new DomainException('Không thể tái sản xuất vì sản phẩm chưa có BOM đang được duyệt.');
                    }
                    $productionOrder = ProductionOrder::create([
                        'production_code' => 'MO-RETURN-'.now()->format('Ymd').'-'.$item->id,
                        'order_id' => $order->id,
                        'order_item_id' => $orderItem->id,
                        'product_bom_id' => $bom->id,
                        'product_id' => $orderItem->product_id,
                        'planned_quantity' => $item->quantity,
                        'unit' => $orderItem->product->unit,
                        'yield_rate' => 1,
                        'status' => 'pending_director_approval',
                        'created_by' => auth()->id(),
                        'notes' => 'Tái sản xuất từ hàng khách trả, phiếu trả #'.$item->sales_order_return_id,
                    ]);
                    $item->rework_production_order_id = $productionOrder->id;
                    $orderItem->decrement('actual_quantity', min((float) $item->quantity, (float) $orderItem->actual_quantity));
                    $order->update([
                        'status' => 'pending_production_approval',
                        'production_plan_approved_at' => null,
                        'production_plan_approved_by' => null,
                        'warehouse_confirmed_at' => null,
                        'warehouse_packed_at' => null,
                        'production_packaged_at' => null,
                        'production_packaged_by' => null,
                    ]);
                }

                $item->update([
                    'status' => $decision === 'restock' ? 'restocked' : ($decision === 'rework' ? 'rework' : 'discarded'),
                    'inspection_note' => $validated['inspection_note'],
                    'inspected_by' => auth()->id(),
                    'inspected_at' => now(),
                ]);

                $return = SalesOrderReturn::whereKey($item->sales_order_return_id)->lockForUpdate()->firstOrFail();
                if (! $return->items()->where('status', 'pending_inspection')->exists()) {
                    $return->update(['status' => 'processed']);
                }
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã ghi nhận kết quả kiểm tra hàng trả.');
    }

    private function canViewReturns(): bool
    {
        $user = auth()->user();

        return $user && ($user->isITDepartment() || $user->isWarehouseDepartment() || $user->isQCDepartment());
    }

    private function canReceiveReturns(): bool
    {
        $user = auth()->user();

        return $user && ($user->isITDepartment() || $user->isWarehouseDepartment());
    }

    private function canInspectReturns(): bool
    {
        $user = auth()->user();

        return $user && ($user->isITDepartment() || $user->isQCDepartment());
    }
}
