<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderLotAllocation extends Model
{
    protected $fillable = [
        'order_item_id',
        'supplier_batch_id',
        'production_finished_batch_id',
        'reserved_quantity',
        'shipped_quantity',
        'status',
        'label_printed_at',
        'label_printed_by',
    ];

    protected $casts = [
        'reserved_quantity' => 'decimal:4',
        'shipped_quantity' => 'decimal:4',
        'label_printed_at' => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function supplierBatch()
    {
        return $this->belongsTo(SupplierBatch::class);
    }

    public function finishedBatch()
    {
        return $this->belongsTo(ProductionFinishedBatch::class, 'production_finished_batch_id');
    }

    public function labelPrints()
    {
        return $this->hasMany(SalesOrderLabelPrint::class, 'sales_order_lot_allocation_id');
    }

}