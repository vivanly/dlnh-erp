<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelProduct extends Model
{
    protected $fillable = ['name_key', 'name', 'category', 'scientific_name', 'standard', 'origin', 'part_used', 'registration'];

    public static function key(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }
}
