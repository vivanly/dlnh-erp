<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductionFinishedBatch extends Model
{
    public function scopeAwaitingQualityReport(Builder $query): void
    {
        $query->where('status', 'active')
            ->whereNotNull('warehouse_received_at')
            ->where(fn ($batch) => $batch->whereNull('qc_test_report_file')->orWhere('qc_result', 'failed'));
    }

    public function scopeAwaitingWarehouseReceipt(Builder $query): void
    {
        $query->whereIn('status', ['pending_qa', 'active'])
            ->where(function ($batch) {
                $batch->where('pending_warehouse_quantity', '>', 0)
                    ->orWhere(function ($independent) {
                        $independent->whereNull('production_order_id')
                            ->whereRaw('planned_quantity > COALESCE((SELECT SUM(quantity) FROM inventory_movements WHERE inventory_movements.production_finished_batch_id = production_finished_batches.id AND inventory_movements.direction = ?), CASE WHEN warehouse_received_at IS NOT NULL THEN initial_quantity ELSE 0 END)', ['in']);
                    });
            });
    }

    protected $fillable = [
        'production_order_id',
        'batch_number_assigned_at',
        'batch_number_assigned_by',
        'product_id',
        'origin',
        'ppcb_id',
        'license_number',
        'batch_number',
        'provisional_batch_number',
        'planned_quantity',
        'initial_quantity',
        'current_quantity',
        'pending_warehouse_quantity',
        'unit',
        'mfg_date',
        'exp_date',
        'status',
        'received_by',
        'qa_approved_at',
        'qa_approved_by',
        'qc_test_report',
        'qc_date',
        'qc_test_report_file',
        'qc_result',
        'warehouse_received_at',
        'warehouse_received_by',
    ];

    protected $casts = [
        'initial_quantity' => 'decimal:4',
        'planned_quantity' => 'decimal:4',
        'current_quantity' => 'decimal:4',
        'pending_warehouse_quantity' => 'decimal:4',
        'mfg_date' => 'date',
        'exp_date' => 'date',
        'batch_number_assigned_at' => 'datetime',
        'qa_approved_at' => 'datetime',
        'qc_date' => 'date',
        'warehouse_received_at' => 'datetime',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function batchNumberAssignedBy()
    {
        return $this->belongsTo(User::class, 'batch_number_assigned_by');
    }

    public function orderAllocations()
    {
        return $this->hasMany(ProductionOrderBatchAllocation::class);
    }

    public function allocatedProductionOrders()
    {
        return $this->belongsToMany(ProductionOrder::class, 'production_order_batch_allocations')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ppcb()
    {
        return $this->belongsTo(Ppcb::class);
    }

    public function inputs()
    {
        return $this->hasMany(ProductionBatchInput::class);
    }

    public function salesOrderAllocations()
    {
        return $this->hasMany(SalesOrderLotAllocation::class);
    }

    public function traceabilityCode()
    {
        return $this->hasOne(TraceabilityLotCode::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function codeHistories()
    {
        return $this->hasMany(ProductionBatchCodeHistory::class);
    }
}
