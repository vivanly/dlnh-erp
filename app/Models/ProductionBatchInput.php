<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatchInput extends Model
{
    protected $fillable = [
        'production_finished_batch_id',
        'production_material_lot_id',
        'consumed_quantity',
        'unit',
    ];

    protected $casts = [
        'consumed_quantity' => 'decimal:4',
    ];

    public function finishedBatch()
    {
        return $this->belongsTo(ProductionFinishedBatch::class, 'production_finished_batch_id');
    }

    public function materialLot()
    {
        return $this->belongsTo(ProductionMaterialLot::class, 'production_material_lot_id');
    }
}