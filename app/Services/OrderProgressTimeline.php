<?php

namespace App\Services;

use App\Models\MaterialStockMovement;
use App\Models\Order;
use Illuminate\Support\Collection;

class OrderProgressTimeline
{
    public function build(Order $order): array
    {
        $status = (string) $order->status;
        $items = $order->items;
        $productionOrders = $order->productionOrders;
        $allocations = $items->flatMap(fn ($item) => $item->lotAllocations->whereIn('status', ['reserved', 'shipped']));
        $productionBatches = $allocations->map(fn ($allocation) => $allocation->finishedBatch)->filter()->unique('id')->values();
        $hasInternalLots = $allocations->contains(fn ($allocation) => (bool) $allocation->production_finished_batch_id);
        $isRawMaterialOrder = $order->order_type === 'NL';
        $stages = [];

        $add = function (string $key, string $title, bool $done = false, $at = null, ?string $detail = null, bool $applicable = true, bool $rejected = false, bool $started = false, ?string $by = null) use (&$stages): void {
            $stages[] = compact('key', 'title', 'done', 'at', 'detail', 'applicable', 'rejected', 'started', 'by');
        };

        $salesApproved = (bool) $order->sales_approved_at || ! in_array($status, ['draft', 'pending_sales_approval', 'sales_rejected'], true);
        $add(
            'sales_approval',
            'Duyệt đơn hàng',
            $salesApproved,
            $order->sales_approved_at,
            $order->sales_rejection_reason ?: null,
            true,
            $status === 'sales_rejected',
            false,
            $order->salesApprovedBy?->name
        );

        if ($isRawMaterialOrder) {
            $movements = MaterialStockMovement::query()
                ->whereIn('order_item_id', $items->modelKeys())
                ->where('movement_type', 'ISSUE_SALE');
            $issuedItemIds = (clone $movements)->distinct()->pluck('order_item_id');
            $movement = MaterialStockMovement::query()
                ->whereIn('order_item_id', $items->modelKeys())
                ->where('movement_type', 'ISSUE_SALE')
                ->latest('created_at')
                ->first();
            $issuedLines = $issuedItemIds->count();
            $add(
                'raw_material_issue',
                'Kho xuất nguyên liệu',
                $status === 'completed',
                $movement?->created_at,
                $movement ? "Đã xuất cho {$issuedLines}/{$items->count()} dòng hàng" : null
            );
        } else {
            $afterStockCheck = in_array($status, ['pending_qa', 'pending_planning', 'pending_production_approval', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true);
            $add('warehouse_stock', 'Kho kiểm tra tồn', (bool) $order->warehouse_stock_checked_at || $afterStockCheck, $order->warehouse_stock_checked_at, by: $order->warehouseStockCheckedBy?->name);

            $afterQa = in_array($status, ['pending_planning', 'pending_production_approval', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true);
            $add('qa_lots', 'QA chốt và phân lô', (bool) $order->qa_confirmed_at || $afterQa, $order->qa_confirmed_at);

            $noProductionRequired = $productionOrders->isEmpty() && in_array($status, ['waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true);
            $planDone = (bool) $order->production_plan_approved_at || $noProductionRequired || ($productionOrders->isEmpty() && $status === 'waiting_finished_goods_receipt');
            $add(
                'production_plan',
                'Kế hoạch sản xuất / kiểm tra lượng thiếu',
                $planDone,
                $order->production_plan_approved_at,
                $noProductionRequired ? 'Không phát sinh lệnh sản xuất' : null,
                ! $isRawMaterialOrder,
                by: $order->productionPlanApprovedBy?->name
            );

            $hasProduction = $productionOrders->isNotEmpty();
            $allMaterialsIssued = $hasProduction && $productionOrders->every(fn ($productionOrder) => (bool) $productionOrder->materials_issued_at);
            $materialsIssuedCount = $productionOrders->filter(fn ($productionOrder) => (bool) $productionOrder->materials_issued_at)->count();
            $add(
                'materials_issue',
                'Kho xuất nguyên liệu sản xuất',
                $allMaterialsIssued,
                $this->latestTimestamp($productionOrders, 'materials_issued_at'),
                $hasProduction ? "{$materialsIssuedCount}/{$productionOrders->count()} lệnh đã xuất nguyên liệu" : null,
                $hasProduction,
                false,
                $materialsIssuedCount > 0 && ! $allMaterialsIssued
            );

            $allReported = $hasProduction && $productionOrders->every(fn ($productionOrder) => (bool) $productionOrder->production_reported_at);
            $reportedCount = $productionOrders->filter(fn ($productionOrder) => (bool) $productionOrder->production_reported_at)->count();
            $add(
                'production_output',
                'Sản xuất báo cáo sản lượng',
                $allReported,
                $this->latestTimestamp($productionOrders, 'production_reported_at'),
                $hasProduction ? "{$reportedCount}/{$productionOrders->count()} lệnh đã báo cáo" : null,
                $hasProduction,
                false,
                $reportedCount > 0 && ! $allReported
            );

            $allProductionComplete = $hasProduction && $productionOrders->every(fn ($productionOrder) => (bool) $productionOrder->completed_at);
            $completedProductionCount = $productionOrders->filter(fn ($productionOrder) => (bool) $productionOrder->completed_at)->count();
            $add(
                'production_completion',
                'Kho xác nhận hoàn tất lệnh sản xuất',
                $allProductionComplete,
                $this->latestTimestamp($productionOrders, 'completed_at'),
                $hasProduction ? "{$completedProductionCount}/{$productionOrders->count()} lệnh hoàn tất" : null,
                $hasProduction,
                false,
                $completedProductionCount > 0 && ! $allProductionComplete
            );

            $hasProducedBatches = $productionBatches->isNotEmpty();
            $allBatchesQaApproved = $hasProducedBatches && $productionBatches->every(fn ($batch) => (bool) $batch->qa_approved_at);
            $qaApprovedCount = $productionBatches->filter(fn ($batch) => (bool) $batch->qa_approved_at)->count();
            $add(
                'finished_batch_qa',
                'QA duyệt lô thành phẩm',
                $allBatchesQaApproved || ($hasProducedBatches && in_array($status, ['ready_to_ship', 'completed'], true)),
                $this->latestTimestamp($productionBatches, 'qa_approved_at'),
                $hasProducedBatches ? "{$qaApprovedCount}/{$productionBatches->count()} lô đã duyệt" : null,
                $hasProducedBatches,
                false,
                $qaApprovedCount > 0 && ! $allBatchesQaApproved
            );

            $allBatchesReceived = $hasProducedBatches && $productionBatches->every(fn ($batch) => (bool) $batch->warehouse_received_at);
            $receivedCount = $productionBatches->filter(fn ($batch) => (bool) $batch->warehouse_received_at)->count();
            $add(
                'finished_batch_receipt',
                'Kho nhập lô thành phẩm',
                $allBatchesReceived || ($hasProducedBatches && in_array($status, ['ready_to_ship', 'completed'], true)),
                $this->latestTimestamp($productionBatches, 'warehouse_received_at'),
                $hasProducedBatches ? "{$receivedCount}/{$productionBatches->count()} lô đã nhập kho" : null,
                $hasProducedBatches,
                false,
                $receivedCount > 0 && ! $allBatchesReceived
            );

            $labelItems = $items->filter(fn ($item) => $item->label_target_count > 0);
            $labelTarget = (int) $labelItems->sum(fn ($item) => $item->label_target_count);
            $labelPrinted = (int) $labelItems->sum(fn ($item) => $item->labelPrints->sum('copies_count'));
            $latestLabelPrint = $items->flatMap(fn ($item) => $item->labelPrints)->sortBy(fn ($print) => $print->created_at?->getTimestamp() ?? 0)->last();
            $labelTimestamp = $latestLabelPrint?->created_at;
            $add(
                'labels',
                'In đủ nhãn',
                $labelTarget === 0 || $labelPrinted >= $labelTarget || $status === 'completed',
                $labelTimestamp,
                $labelTarget > 0 ? number_format(min($labelPrinted, $labelTarget)).' / '.number_format($labelTarget).' nhãn' : null,
                $labelTarget > 0,
                false,
                $labelPrinted > 0 && $labelPrinted < $labelTarget,
                $latestLabelPrint?->printedBy?->name
            );

            $add(
                'production_packaging',
                'Sản xuất xác nhận đóng gói',
                (bool) $order->production_packaged_at || $status === 'completed',
                $order->production_packaged_at,
                null,
                $hasInternalLots,
                by: $order->productionPackagedBy?->name
            );

            $add(
                'warehouse_packaging',
                'Kho đóng hàng',
                (bool) $order->warehouse_packed_at || $status === 'completed',
                $order->warehouse_packed_at,
                null,
                true
            );

            $orderedQuantity = (float) $items->sum(fn ($item) => (float) $item->quantity);
            $shippedQuantity = (float) $items->sum(fn ($item) => (float) $item->actual_quantity);
            $add(
                'shipment',
                'Kho xác nhận gửi hàng',
                (bool) $order->warehouse_confirmed_at || $status === 'completed',
                $order->warehouse_confirmed_at,
                number_format($shippedQuantity, 2).' / '.number_format($orderedQuantity, 2).' đơn vị đã gửi'
            );
        }

        $activeKey = $this->activeStage($order, $productionOrders, $hasInternalLots, $items);

        return collect($stages)->map(function (array $stage) use ($activeKey, $status): array {
            // A completed order is only marked completed after shipment. Older orders may
            // lack individual stage timestamps, so use the final order status as the
            // source of truth instead of leaving those stages as "pending".
            if ($status === 'completed' && $stage['applicable'] && ! $stage['done']) {
                $stage['done'] = true;
                $stage['detail'] = 'Hoàn tất theo trạng thái đơn hàng';
            }

            $stage['state'] = match (true) {
                ! $stage['applicable'] => 'skipped',
                $stage['rejected'] => 'rejected',
                $stage['done'] => 'completed',
                $stage['key'] === $activeKey => 'active',
                $stage['started'] => 'active',
                default => 'pending',
            };

            return $stage;
        })->all();
    }

    private function activeStage(Order $order, Collection $productionOrders, bool $hasInternalLots, Collection $items): ?string
    {
        return match ($order->status) {
            'draft', 'pending_sales_approval', 'sales_rejected' => 'sales_approval',
            'pending_material_issue' => 'raw_material_issue',
            'pending_warehouse_check' => 'warehouse_stock',
            'pending_qa' => 'qa_lots',
            'pending_planning' => 'production_plan',
            'pending_production_approval' => 'production_plan',
            'waiting_production' => 'materials_issue',
            'processing' => $productionOrders->contains(fn ($productionOrder) => ! $productionOrder->materials_issued_at) ? 'materials_issue' : 'production_output',
            'waiting_finished_goods_receipt' => $productionOrders->contains(fn ($productionOrder) => $productionOrder->status === 'completed') || $order->items->flatMap(fn ($item) => $item->lotAllocations)->contains(fn ($allocation) => $allocation->finishedBatch && ! $allocation->finishedBatch->warehouse_received_at) ? 'finished_batch_receipt' : 'production_completion',
            'ready_to_ship' => $this->readyToShipStage($order, $hasInternalLots, $items),
            default => null,
        };
    }

    private function readyToShipStage(Order $order, bool $hasInternalLots, Collection $items): string
    {
        $labelTarget = (int) $items->sum(fn ($item) => $item->label_target_count);
        $labelPrinted = (int) $items->sum(fn ($item) => $item->labelPrints->sum('copies_count'));

        if ($labelTarget > 0 && $labelPrinted < $labelTarget) {
            return 'labels';
        }
        if ($hasInternalLots && ! $order->production_packaged_at) {
            return 'production_packaging';
        }
        if (! $order->warehouse_packed_at) {
            return 'warehouse_packaging';
        }

        return 'shipment';
    }

    private function latestTimestamp(Collection $models, string $attribute)
    {
        return $models->pluck($attribute)
            ->filter()
            ->sortBy(fn ($date) => $date->getTimestamp())
            ->last();
    }
}
