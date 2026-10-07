<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductQualityStandard extends Model
{
    protected $fillable = ['product_id', 'standard_type', 'indicator', 'requirement', 'method', 'note'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}