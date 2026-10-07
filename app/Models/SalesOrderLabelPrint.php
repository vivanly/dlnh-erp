<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderLabelPrint extends Model
{
    protected $fillable = [
        'order_item_id',
        'sales_order_lot_allocation_id',
        'copies_count',
        'print_kind',
        'reason',
        'printed_by',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function allocation()
    {
        return $this->belongsTo(SalesOrderLotAllocation::class, 'sales_order_lot_allocation_id');
    }

    public function printedBy()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}