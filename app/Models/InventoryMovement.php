<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'product_id',
        'supplier_batch_id',
        'production_finished_batch_id',
        'movement_type',
        'direction',
        'quantity',
        'unit',
        'reference_type',
        'reference_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}