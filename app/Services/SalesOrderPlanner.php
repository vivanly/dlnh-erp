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
                'items.lotAllocations.finishedBatch',
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
                $availableQuantity = $item->lotAllocations
                    ->where('status', 'reserved')
                    ->filter(fn ($allocation) => ! $allocation->finishedBatch || in_array($allocation->finishedBatch->status, ['active', 'pending_qa'], true))
                    ->sum(fn ($allocation) => max(0, (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity));
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
                    : ($this->hasUnreceivedAllocatedFinishedGoods($lockedOrder) ? 'waiting_finished_goods_receipt' : 'ready_to_ship'),
                'production_plan_approved_at' => null,
                'production_plan_approved_by' => null,
                'production_plan_rejection_reason' => null,
            ]);

            return $hasProductionShortage;
        });
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
