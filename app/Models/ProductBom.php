<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBom extends Model
{
    protected $fillable = [
        'product_id',
        'version',
        'output_quantity',
        'output_unit',
        'yield_rate',
        'is_active',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'output_quantity' => 'decimal:4',
        'yield_rate' => 'decimal:5',
        'is_active' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function items()
    {
        return $this->hasMany(ProductBomItem::class)->with('componentProduct');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}