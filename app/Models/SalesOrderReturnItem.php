<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderReturnItem extends Model
{
    protected $fillable = [
        'sales_order_return_id',
        'order_item_id',
        'sales_order_lot_allocation_id',
        'production_finished_batch_id',
        'supplier_batch_id',
        'quantity',
        'status',
        'inspection_note',
        'inspected_by',
        'inspected_at',
        'rework_production_order_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'inspected_at' => 'datetime',
    ];

    public function salesOrderReturn()
    {
        return $this->belongsTo(SalesOrderReturn::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function allocation()
    {
        return $this->belongsTo(SalesOrderLotAllocation::class, 'sales_order_lot_allocation_id');
    }

    public function productionFinishedBatch()
    {
        return $this->belongsTo(ProductionFinishedBatch::class);
    }

    public function supplierBatch()
    {
        return $this->belongsTo(SupplierBatch::class);
    }

    public function inspectedBy()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function reworkProductionOrder()
    {
        return $this->belongsTo(ProductionOrder::class, 'rework_production_order_id');
    }
}
