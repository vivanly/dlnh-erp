<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionFinishedBatch extends Model
{
    protected $fillable = [
        'production_order_id',
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
        'qa_approved_at' => 'datetime',
        'qc_date' => 'date',
        'warehouse_received_at' => 'datetime',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
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