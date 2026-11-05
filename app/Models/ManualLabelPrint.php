<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelPrint extends Model
{
    protected $fillable = [
        'label_type', 'traditional_medicine_name', 'product_name', 'batch', 'scientific_name',
        'mfg_date', 'part_used', 'exp_date', 'origin', 'net_weight', 'registration', 'standard',
        'storage', 'qr_content', 'include_packaging_control', 'packaging_controller', 'packaging_performer',
        'watermark', 'copies_count', 'delivered_count', 'note', 'order_code', 'printed_by',
    ];

    protected $casts = [
        'traditional_medicine_name' => 'boolean',
        'include_packaging_control' => 'boolean',
        'watermark' => 'boolean',
    ];

    public function printedBy()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function deliveries()
    {
        return $this->hasMany(ManualLabelDelivery::class)->latest('delivered_at');
    }

    public function getRemainingCountAttribute(): int
    {
        return max(0, $this->copies_count - $this->delivered_count);
    }

    public function toLabelArray(): array
    {
        return [
            'label_type' => $this->label_type,
            'traditional_medicine_name' => $this->traditional_medicine_name,
            'product' => $this->product_name,
            'batch' => $this->batch,
            'scientific' => $this->scientific_name,
            'mfg' => $this->mfg_date,
            'part' => $this->part_used,
            'exp' => $this->exp_date,
            'origin' => $this->origin,
            'weight' => $this->net_weight,
            'registration' => $this->registration,
            'standard' => $this->standard,
            'storage' => $this->storage,
            'include_packaging_control' => $this->include_packaging_control,
            'packaging_controller' => $this->packaging_controller,
            'packaging_performer' => $this->packaging_performer,
            'qr_url' => $this->qr_content ?: null,
        ];
    }
}
