<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'name',
        'slug',
        'part_used',
        'ppcb_id',
        'origin',
        'unit',
        'classification',
        'sku',
        'gtin',
        'scientific_name',
        'scientific_name_reference',
        'note',
    ];

    public static function uniqueSlug(string $name, ?string $sku = null, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'product';
        $skuSlug = Str::slug($sku ?? '');
        $slug = $baseSlug;
        $suffix = $skuSlug !== '' ? '-' . $skuSlug : '';
        $counter = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists()) {
            $slug = $baseSlug . $suffix;
            if ($suffix === '' || static::query()
                ->where('slug', $slug)
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists()) {
                $slug = $baseSlug . $suffix . '-' . $counter++;
            }
        }

        return $slug;
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function boms()
    {
        return $this->hasMany(ProductBom::class);
    }

    public function unitConversions()
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    public function ppcb()
    {
        return $this->belongsTo(Ppcb::class);
    }

    public function regulatoryDocuments()
    {
        return $this->hasMany(ProductRegulatoryDocument::class);
    }

    public function qualityStandards()
    {
        return $this->hasMany(ProductQualityStandard::class);
    }

    public function storageMethods()
    {
        return $this->hasMany(ProductStorageMethod::class);
    }
}