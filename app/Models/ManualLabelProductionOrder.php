<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelProductionOrder extends Model
{
    protected $fillable = ['label_code', 'order_date', 'production_order', 'name', 'batch', 'mfg_date', 'exp_date', 'pack_spec', 'quantity', 'label_count'];

    protected $casts = ['order_date' => 'date', 'mfg_date' => 'date', 'exp_date' => 'date'];

    public function product(): ?ManualLabelProduct
    {
        return ManualLabelProduct::where('name_key', ManualLabelProduct::key($this->name))->first();
    }

    public function netWeight(): ?string
    {
        $spec = trim((string) $this->pack_spec);

        return $spec === '' ? null : preg_replace('/(\d)\s*(kg|g)\b/iu', '$1 $2', mb_strtolower($spec));
    }

    public function toFormArray(?ManualLabelProduct $product = null): array
    {
        $product ??= $this->product();
        $isMedicine = $product ? mb_stripos((string) $product->category, 'vị thuốc') !== false : true;
        $origin = (string) $product?->origin;
        $type = $isMedicine ? 'vt' : (mb_stripos($origin, 'việt') !== false ? 'dl_n' : 'dl_b');

        return [
            'id' => $this->id,
            'label_code' => $this->label_code,
            'order_code' => $this->production_order,
            'label_type' => $type,
            'traditional_medicine_name' => $type === 'vt',
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
            'copies_count' => max(1, (int) $this->label_count),
        ];
    }
}
