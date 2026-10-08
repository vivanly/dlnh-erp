<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialLot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'qc_at' => 'datetime',
    ];

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function getCatalogItemAttribute()
    {
        return RawMaterial::find($this->material_id);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'QC đạt - đã vào tồn',
            'rejected' => 'QC không đạt',
            default => $this->coa_file && $this->batch_number ? 'Chờ QC xác nhận' : 'Chờ QA cập nhật lô/COA',
        };
    }
}
