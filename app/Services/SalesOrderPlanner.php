<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductionOrder;
use App\Models\SalesOrderLotAllocation;
use DomainException;
use Illuminate\Support\Facades\DB;

class SalesOrderPlanner
{
    public function plan(Order $order, ?int $userId, array $plannedQuantities = []): bool
    {
        return DB::transaction(function () use ($order, $userId, $plannedQuantities) {
            $lockedOrder = Order::with([
                'items.product',
                'items.lotAllocations.finishedBatch.productionOrder',
                'items.lotAllocations.finishedBatch.orderAllocations',
                'items.lotAllocations.supplierBatch',
            ])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->status !== 'pending_planning' || ! $lockedOrder->qa_confirmed_at) {
                throw new DomainException('Đơn hàng không còn ở trạng thái chờ hoạch định.');
            }

            if ($lockedOrder->items->isEmpty()) {
                throw new DomainException('Đơn hàng không có sản phẩm để hoạch định.');
            }

            $orderItemIds = $lockedOrder->items->pluck('id')->map(fn ($id) => (int) $id)->all();
            $submittedItemIds = array_map('intval', array_keys($plannedQuantities));
            if (array_diff($submittedItemIds, $orderItemIds)) {
                throw new DomainException('Số lượng kế hoạch có dòng hàng không thuộc đơn này.');
            }

            $hasProductionShortage = false;

            foreach ($lockedOrder->items as $item) {
                $requestedQuantity = (float) $item->quantity;
                $availableQuantity = $this->availableCoverage($item);
                $shortage = round($requestedQuantity - $availableQuantity, 4);

                if ($shortage <= 0) {
                    continue;
                }

                $plannedQuantity = isset($plannedQuantities[$item->id])
                    ? round((float) $plannedQuantities[$item->id], 4)
                    : $shortage;
                if ($plannedQuantity + 0.0001 < $shortage) {
                    throw new DomainException("Số lượng kế hoạch {$item->product->name} không được thấp hơn phần đơn còn thiếu ({$shortage}).");
                }
                if ($plannedQuantity <= 0) {
                    throw new DomainException("Nhập số lượng sản xuất lớn hơn 0 cho {$item->product->name}.");
                }

                ProductionOrder::create([
                    'production_code' => $this->productionCode($lockedOrder, $item),
                    'order_id' => $lockedOrder->id,
                    'order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'planned_quantity' => $plannedQuantity,
                    'unit' => $item->product->unit,
                    'yield_rate' => 1,
                    'status' => 'pending_director_approval',
                    'created_by' => $userId,
                ]);
                $hasProductionShortage = true;
            }

            $lockedOrder->update([
                'status' => $hasProductionShortage
                    ? 'pending_production_approval'
                    : (($this->hasUnreceivedAllocatedFinishedGoods($lockedOrder) || $this->hasUnreceivedProductionOutput($lockedOrder)) ? 'waiting_finished_goods_receipt' : 'ready_to_ship'),
                'production_plan_approved_at' => null,
                'production_plan_approved_by' => null,
                'production_plan_rejection_reason' => null,
            ]);

            return $hasProductionShortage;
        });
    }

    public function availableCoverage(OrderItem $item): float
    {
        $item->loadMissing([
            'lotAllocations.finishedBatch.productionOrder',
            'lotAllocations.finishedBatch.orderAllocations',
            'lotAllocations.supplierBatch',
        ]);

        $lotCoverage = 0.0;
        foreach ($item->lotAllocations->where('status', 'reserved') as $allocation) {
            $reservedForItem = max(0, (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity);
            if ($reservedForItem <= 0) {
                continue;
            }

            $batch = $allocation->finishedBatch ?? $allocation->supplierBatch;
            if (! $batch) {
                continue;
            }

            $onHand = max(0, (float) $batch->current_quantity);
            if ($allocation->finishedBatch) {
                $hasProductionSource = $batch->production_order_id !== null || $batch->orderAllocations->isNotEmpty();
                if ($hasProductionSource && in_array($batch->status, ['pending_qa', 'active'], true)) {
                    $onHand += max(0, (float) $batch->pending_warehouse_quantity);
                }
                $reservationColumn = 'production_finished_batch_id';
            } else {
                $reservationColumn = 'supplier_batch_id';
            }

            $otherReservations = (float) SalesOrderLotAllocation::query()
                ->where($reservationColumn, $batch->id)
                ->where('order_item_id', '!=', $item->id)
                ->where('status', 'reserved')
                ->selectRaw('COALESCE(SUM(reserved_quantity - shipped_quantity), 0) as reserved_quantity')
                ->value('reserved_quantity');
            $lotCoverage += min($reservedForItem, max(0, $onHand - $otherReservations));
        }

        $unreceivedProductionOutput = (float) ProductionOrder::query()
            ->where('order_item_id', $item->id)
            ->where('status', 'completed')
            ->sum('pending_finished_quantity');

        return min((float) $item->quantity, $lotCoverage + max(0, $unreceivedProductionOutput));
    }

    private function hasUnreceivedProductionOutput(Order $order): bool
    {
        return ProductionOrder::query()
            ->where('order_id', $order->id)
            ->where('status', 'completed')
            ->where('pending_finished_quantity', '>', 0.0001)
            ->exists();
    }

    private function hasUnreceivedAllocatedFinishedGoods(Order $order): bool
    {
        foreach ($order->items as $item) {
            $allocatedQuantity = $item->lotAllocations
                ->where('status', 'reserved')
                ->sum(fn ($allocation) => max(0, (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity));
            if ($allocatedQuantity + 0.0001 < (float) $item->quantity) {
                continue;
            }

            foreach ($item->lotAllocations->where('status', 'reserved') as $allocation) {
                if ((float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity <= 0.0001) {
                    continue;
                }

                $batch = $allocation->finishedBatch;
                if (! $batch) {
                    continue;
                }

                $reservedQuantity = (float) SalesOrderLotAllocation::query()
                    ->where('production_finished_batch_id', $batch->id)
                    ->where('status', 'reserved')
                    ->selectRaw('COALESCE(SUM(reserved_quantity - shipped_quantity), 0) as reserved_quantity')
                    ->value('reserved_quantity');

                if ($batch->status !== 'active' || (float) $batch->current_quantity + 0.0001 < $reservedQuantity) {
                    return true;
                }
            }
        }

        return false;
    }

    private function productionCode(Order $order, OrderItem $item): string
    {
        $sequence = ProductionOrder::where('order_item_id', $item->id)->count() + 1;

        return 'MO-'.now()->format('Ymd').'-'.$order->id.'-'.$item->id.'-'.$sequence;
    }
}
