<?php

namespace App\Services;

use App\Models\ProductionMaterialLot;
use App\Models\SalesOrderLotAllocation;
use Illuminate\Support\Collection;

class SupplierBatchAvailability
{
    public function addTo(Collection $batches, array $excludedMaterialIds = []): void
    {
        $batchIds = $batches->modelKeys();
        if ($batchIds === []) {
            return;
        }

        $salesReservations = SalesOrderLotAllocation::query()
            ->whereIn('supplier_batch_id', $batchIds)
            ->where('status', 'reserved')
            ->selectRaw('supplier_batch_id, COALESCE(SUM(reserved_quantity - shipped_quantity), 0) as reserved_quantity')
            ->groupBy('supplier_batch_id')
            ->pluck('reserved_quantity', 'supplier_batch_id');

        $productionReservations = ProductionMaterialLot::query()
            ->whereIn('supplier_batch_id', $batchIds)
            ->when($excludedMaterialIds !== [], fn ($query) => $query->whereNotIn('production_order_material_id', $excludedMaterialIds))
            ->whereHas('material.productionOrder', fn ($query) => $query->whereIn('status', ['planned', 'materials_assigned', 'released']))
            ->selectRaw('supplier_batch_id, COALESCE(SUM(allocated_quantity - issued_quantity), 0) as reserved_quantity')
            ->groupBy('supplier_batch_id')
            ->pluck('reserved_quantity', 'supplier_batch_id');

        foreach ($batches as $batch) {
            $reserved = (float) $salesReservations->get($batch->id, 0)
                + (float) $productionReservations->get($batch->id, 0);
            $isExpired = $batch->exp_date && $batch->exp_date < today();
            $qaApproved = $batch->status === 'active';
            $available = $qaApproved && !$isExpired
                ? max(0, (float) $batch->current_quantity - $reserved)
                : 0.0;
            $lowThreshold = (float) $batch->initial_quantity * 0.1;

            $batch->setAttribute('reserved_quantity', $reserved);
            $batch->setAttribute('available_quantity', $available);
            $batch->setAttribute('availability_status', match (true) {
                !$qaApproved => 'Chờ QA',
                $isExpired => 'Hết hạn',
                $available <= 0 => 'Đã giữ hết',
                $lowThreshold > 0 && $available <= $lowThreshold => 'Sắp hết',
                default => 'Còn khả dụng',
            });
        }
    }
}