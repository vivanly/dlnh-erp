<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrderMaterial extends Model
{
    protected $fillable = [
        'production_order_id',
        'product_bom_item_id',
        'product_id',
        'material_type',
        'material_id',
        'required_quantity',
        'issued_quantity',
        'consumed_quantity',
        'unit',
    ];

    protected $casts = [
        'required_quantity' => 'decimal:4',
        'issued_quantity' => 'decimal:4',
        'consumed_quantity' => 'decimal:4',
    ];

    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function bomItem()
    {
        return $this->belongsTo(ProductBomItem::class, 'product_bom_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getCatalogItemAttribute()
    {
        return match ($this->material_type) {
            'raw_material' => RawMaterial::find($this->material_id),
            'accessory' => Accessory::find($this->material_id),
            default => null,
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->product->name ?? $this->catalog_item->name ?? '---';
    }

    public function lots()
    {
        return $this->hasMany(ProductionMaterialLot::class);
    }
}