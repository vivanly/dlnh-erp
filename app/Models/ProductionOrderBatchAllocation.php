<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderBatchAllocation extends Model
{
    protected $fillable = [
        'production_order_id',
        'production_finished_batch_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function productionFinishedBatch()
    {
        return $this->belongsTo(ProductionFinishedBatch::class);
    }
}
