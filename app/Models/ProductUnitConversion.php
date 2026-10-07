<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnitConversion extends Model
{
    protected $fillable = [
        'product_id',
        'unit',
        'to_base_factor',
    ];

    protected $casts = [
        'to_base_factor' => 'decimal:10',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}