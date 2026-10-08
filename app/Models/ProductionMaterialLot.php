<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMaterialLot extends Model
{
    protected $fillable = [
        'production_order_material_id',
        'supplier_batch_id',
        'batch_number',
        'allocated_quantity',
        'issued_quantity',
        'returned_quantity',
        'issued_at',
        'assigned_by',
    ];

    protected $casts = [
        'allocated_quantity' => 'decimal:4',
        'issued_quantity' => 'decimal:4',
        'returned_quantity' => 'decimal:4',
        'issued_at' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(ProductionOrderMaterial::class, 'production_order_material_id');
    }

    public function supplierBatch()
    {
        return $this->belongsTo(SupplierBatch::class);
    }

    public function finishedBatchInputs()
    {
        return $this->hasMany(ProductionBatchInput::class);
    }
}