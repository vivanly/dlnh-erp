<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelCatalog extends Model
{
    protected $table = 'manual_label_catalog';

    protected $fillable = ['name_key', 'name', 'scientific_name', 'part_used', 'origin'];

    public static function key(string $name): string
    {
        $name = preg_replace('/\s*\(.*?\)/u', '', $name);

        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }
}
