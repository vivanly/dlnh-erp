<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelProductionOrder extends Model
{
    public const DEFAULT_STORAGE = 'Để nơi khô ráo, thoáng mát, tránh mối mọt, nấm mốc.';

    protected $fillable = ['label_code', 'order_date', 'production_order', 'name', 'batch', 'mfg_date', 'exp_date', 'pack_spec', 'quantity', 'label_count', 'label_type', 'traditional_medicine_name', 'qr_content', 'storage'];

    protected $casts = ['order_date' => 'date', 'mfg_date' => 'date', 'exp_date' => 'date', 'traditional_medicine_name' => 'boolean'];

    public function product(): ?ManualLabelProduct
    {
        return ManualLabelProduct::where('name_key', ManualLabelProduct::key($this->name))->first();
    }

    public function netWeight(): ?string
    {
        return \App\Services\ManualLabelOrders::normalizeWeight($this->pack_spec);
    }

    public function toFormArray(?ManualLabelProduct $product = null): array
    {
        $product ??= $this->product();
        $isMedicine = $product ? mb_stripos((string) $product->category, 'vị thuốc') !== false : true;
        $origin = (string) $product?->origin;
        $type = $this->label_type ?: ($isMedicine ? 'vt' : (mb_stripos($origin, 'việt') !== false ? 'dl_n' : 'dl_b'));

        return [
            'id' => $this->id,
            'label_code' => $this->label_code,
            'order_code' => $this->production_order,
            'label_type' => $type,
            'traditional_medicine_name' => $type === 'vt' && ($this->traditional_medicine_name ?? true),
            'product_name' => $this->name,
            'scientific_name' => $product?->scientific_name,
            'part_used' => $product?->part_used,
            'batch' => $this->batch,
            'mfg_date' => $this->mfg_date?->format('d/m/Y'),
            'exp_date' => $this->exp_date?->format('d/m/Y'),
            'origin' => $origin ?: null,
            'net_weight' => $this->netWeight(),
            'registration' => $product?->registration,
            'standard' => $product?->standard,
            'storage' => $this->storage,
            'qr_content' => $this->qr_content,
            'copies_count' => max(1, (int) $this->label_count),
        ];
    }
}
