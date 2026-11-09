<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductionOrder extends Model
{
    public function scopeAwaitingBatchAssignment(Builder $query): void
    {
        $query->where('status', 'released')
            ->whereNull('materials_issued_at')
            ->whereDoesntHave('materials.lots', fn ($lots) => $lots->where('issued_quantity', '>', 0))
            ->whereDoesntHave('finishedBatches')
            ->whereDoesntHave('allocatedFinishedBatches');
    }

    public function scopeReadyForMaterialIssue(Builder $query): void
    {
        $query->where('status', 'released')
            ->whereNotNull('provisional_batch_number')
            ->whereHas('finishedBatches', fn ($batches) => $batches
                ->where('status', 'planned')
                ->whereColumn('production_finished_batches.batch_number', 'production_orders.provisional_batch_number'));
    }

    protected $fillable = [
        'production_code',
        'order_id',
        'order_item_id',
        'production_monthly_plan_line_id',
        'product_bom_id',
        'product_id',
        'planned_quantity',
        'provisional_batch_number',
        'actual_quantity',
        'pending_finished_quantity',
        'unit',
        'yield_rate',
        'status',
        'created_by',
        'materials_issued_at',
        'production_reported_at',
        'production_reported_by',
        'warehouse_confirmed_at',
        'warehouse_confirmed_by',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'pending_finished_quantity' => 'decimal:4',
        'yield_rate' => 'decimal:5',
        'materials_issued_at' => 'datetime',
        'production_reported_at' => 'datetime',
        'warehouse_confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function monthlyPlanLine()
    {
        return $this->belongsTo(ProductionMonthlyPlanLine::class, 'production_monthly_plan_line_id');
    }

    public function bom()
    {
        return $this->belongsTo(ProductBom::class, 'product_bom_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function materials()
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function finishedBatches()
    {
        return $this->hasMany(ProductionFinishedBatch::class);
    }

    public function hasPreassignedBatch(): bool
    {
        return (bool) $this->provisional_batch_number
            && $this->finishedBatches->contains(fn ($batch) => $batch->status === 'planned' && $batch->batch_number === $this->provisional_batch_number);
    }

    public function batchAllocations()
    {
        return $this->hasMany(ProductionOrderBatchAllocation::class);
    }

    public function allocatedFinishedBatches()
    {
        return $this->belongsToMany(ProductionFinishedBatch::class, 'production_order_batch_allocations')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
