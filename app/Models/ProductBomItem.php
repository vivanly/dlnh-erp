<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBomItem extends Model
{
    protected $fillable = [
        'product_bom_id',
        'component_product_id',
        'quantity',
        'unit',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function bom()
    {
        return $this->belongsTo(ProductBom::class, 'product_bom_id');
    }

    public function componentProduct()
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }
}