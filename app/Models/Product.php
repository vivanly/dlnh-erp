<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public const CLASSIFICATIONS = [
        'DL' => 'Dược Liệu',
        'DLSC' => 'Dược Liệu Sơ Chế',
        'VTCT' => 'Vị Thuốc Cổ Truyền',
        'VT' => 'Vị Thuốc',
        'TCT' => 'Thuốc Cổ Truyền',
        'TPCN' => 'Thực Phẩm Chức Năng',
    ];

    private const CLASSIFICATION_ALIASES = [
        'Dược Liệu' => 'DL',
        'Dược Liệu Sơ Chế' => 'DLSC',
        'DS' => 'DLSC',
        'Vị Thuốc Cổ Truyền' => 'VTCT',
        'Vị Thuốc' => 'VT',
        'Thuốc Cổ Truyền' => 'TCT',
        'Thực Phẩm Chức Năng' => 'TPCN',
    ];

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

    public static function classificationCode(string $classification): string
    {
        return self::CLASSIFICATION_ALIASES[$classification] ?? $classification;
    }

    public static function classificationValues(): array
    {
        return array_values(array_unique([
            ...array_keys(self::CLASSIFICATIONS),
            ...array_values(self::CLASSIFICATIONS),
            ...array_keys(self::CLASSIFICATION_ALIASES),
        ]));
    }

    public static function classificationLabel(string $classification): string
    {
        return self::CLASSIFICATIONS[self::classificationCode($classification)] ?? $classification;
    }

    public function setClassificationAttribute(?string $value): void
    {
        $this->attributes['classification'] = $value === null ? null : self::classificationCode($value);
    }

    public function getClassificationLabelAttribute(): string
    {
        return self::classificationLabel((string) $this->classification);
    }

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