<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRegulatoryDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'document_type',
        'document_number',
        'document_date',
        'document_form',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
