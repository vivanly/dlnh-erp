<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualLabelDelivery extends Model
{
    protected $fillable = ['manual_label_print_id', 'quantity', 'kind', 'received_by', 'note', 'delivered_by', 'delivered_at'];

    protected $casts = ['delivered_at' => 'datetime'];

    public function print()
    {
        return $this->belongsTo(ManualLabelPrint::class, 'manual_label_print_id');
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }
}
