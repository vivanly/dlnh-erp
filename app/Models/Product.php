<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'part_used',
        'origin',
        'unit',
        'classification',
        'sku',
        'gtin',
        'scientific_name',
        'scientific_name_reference',
        'note',
    ];
}