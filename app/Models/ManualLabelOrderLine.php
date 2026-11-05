<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelOrderLine extends Model
{
    protected $fillable = [
        'order_code', 'order_type', 'customer', 'customer_type', 'received_date', 'delivery_date', 'line_no', 'name', 'unit',
        'origin_code', 'quantity', 'pack_weight', 'pack_count', 'processing', 'note', 'batch', 'mfg_date', 'exp_date',
    ];

    protected $casts = ['received_date' => 'date', 'mfg_date' => 'date', 'exp_date' => 'date'];

    public function labelType(): string
    {
        $origin = strtoupper(trim((string) $this->origin_code));
        if (strtoupper((string) $this->order_type) === 'VT') {
            return 'vt';
        }

        return $origin === 'B' ? 'dl_b' : 'dl_n';
    }

    public function netWeight(): string
    {
        if ($this->pack_weight === null) {
            return '';
        }

        return rtrim(rtrim(number_format((float) $this->pack_weight, 3, '.', ''), '0'), '.') . ' ' . (strtolower((string) $this->unit) ?: 'kg');
    }
}
