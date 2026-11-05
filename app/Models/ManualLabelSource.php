<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelSource extends Model
{
    protected $fillable = ['kind', 'item_code', 'name', 'scientific_name', 'part_used', 'origin_code', 'origin', 'registration', 'batch', 'mfg_date', 'exp_date'];

    protected $casts = ['mfg_date' => 'date', 'exp_date' => 'date'];

    public function toFormArray(): array
    {
        $origin = strtoupper(trim((string) $this->origin_code));
        $kind = strtoupper(trim((string) $this->kind));

        return [
            'id' => $this->id,
            'label' => $this->name . ' — ' . $this->batch,
            'label_type' => $kind === 'VT' ? 'vt' : ($origin === 'B' || ($origin === '' && $this->origin && ! str_contains(mb_strtolower($this->origin), 'việt')) ? 'dl_b' : 'dl_n'),
            'product_name' => $this->name,
            'batch' => $this->batch,
            'mfg_date' => $this->mfg_date?->format('d/m/Y'),
            'exp_date' => $this->exp_date?->format('d/m/Y'),
            'scientific_name' => $this->scientific_name,
            'part_used' => $this->part_used,
            'registration' => $this->registration,
            'origin' => $this->origin ?: match (true) {
                $origin === 'B' => 'Trung Quốc',
                $origin === 'N' => 'Việt Nam',
                default => (string) $this->origin_code,
            },
        ];
    }
}
