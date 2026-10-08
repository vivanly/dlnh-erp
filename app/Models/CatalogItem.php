<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class CatalogItem extends Model
{
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
