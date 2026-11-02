<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\Accessory;
use App\Models\Excipient;
use App\Models\PackagingMaterial;

class ProductBomItem extends Model
{
    protected $fillable = [
        'product_bom_id',
        'component_product_id',
        'component_type',
        'component_id',
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

    public function component()
    {
        return $this->morphTo('component', 'component_type', 'component_id');
    }

    public function getComponentTypeLabelAttribute(): string
    {
        return match ($this->component_type) {
            Product::class => 'Sản phẩm',
            RawMaterial::class => 'Dược liệu thô',
            Accessory::class => 'Phụ liệu',
            Excipient::class => 'Tá dược',
            PackagingMaterial::class => 'Bao bì',
            default => 'Nguyên liệu',
        };
    }
}