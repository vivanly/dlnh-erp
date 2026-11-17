<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class CatalogItem extends Model
{
    private const TYPE_ALIASES = [
        'DLT' => 'raw_material',
        'NL' => 'ingredient',
        'PL' => 'accessory',
        'TD' => 'excipient',
        'BB' => 'packaging',
    ];

    public static function modelForType(string $type): ?string
    {
        return match (self::normalizeType($type)) {
            'raw_material' => RawMaterial::class,
            'ingredient' => Ingredient::class,
            'accessory' => Accessory::class,
            'excipient' => Excipient::class,
            'packaging' => PackagingMaterial::class,
            default => null,
        };
    }

    public static function normalizeType(string $type): string
    {
        if (isset(self::TYPE_ALIASES[$type])) {
            return self::TYPE_ALIASES[$type];
        }

        return $type;
    }

    public static function codeForType(string $type): string
    {
        return match ($type) {
            'DLT', 'raw_material' => 'DLT',
            'NL', 'ingredient' => 'NL',
            'PL', 'accessory' => 'PL',
            'TD', 'excipient' => 'TD',
            'BB', 'packaging' => 'BB',
            default => $type,
        };
    }

    public static function labelForType(string $type): string
    {
        return match (self::codeForType($type)) {
            'DLT' => 'Dược liệu thô',
            'NL' => 'Nguyên liệu',
            'PL' => 'Phụ liệu',
            'TD' => 'Tá dược',
            'BB' => 'Bao bì',
            default => 'Nguyên liệu',
        };
    }

    public static function findByType(string $type, int $id): ?Model
    {
        $model = self::modelForType($type);

        return $model ? $model::find($id) : null;
    }

    protected $fillable = [
        'name',
        'slug',
        'part_used',
        'origin',
        'unit',
        'classification',
        'sku',
        'scientific_name',
        'scientific_name_reference',
        'note',
    ];

    public static function uniqueSlug(string $name, ?string $sku = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'item';
        $skuSlug = Str::slug($sku ?? '');
        $exists = fn (string $slug) => static::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '<>', $ignoreId))
            ->exists();

        $slug = $base;
        if ($exists($slug) && $skuSlug !== '') {
            $slug = $base . '-' . $skuSlug;
        }

        $counter = 2;
        $candidate = $slug;
        while ($exists($candidate)) {
            $candidate = $slug . '-' . $counter++;
        }

        return $candidate;
    }
}
