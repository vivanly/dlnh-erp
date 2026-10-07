<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStorageMethod extends Model
{
    protected $fillable = ['product_id', 'storage_method', 'note'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}