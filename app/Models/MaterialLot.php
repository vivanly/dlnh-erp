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

    public function needsQa(): bool
    {
        return $this->material_type === 'raw_material' && (! $this->batch_number || ! $this->coa_file);
    }

    public function getCatalogItemAttribute()
    {
        return $this->material_type === 'accessory' ? Accessory::find($this->material_id) : RawMaterial::find($this->material_id);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'QC đạt - đã vào tồn',
            'rejected' => 'QC không đạt',
            default => $this->needsQa() ? 'Chờ QA cập nhật lô/COA' : 'Chờ QC xác nhận',
        };
    }
}
