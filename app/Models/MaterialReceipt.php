<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialReceipt extends Model
{
    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function preliminaryQa()
    {
        return $this->belongsTo(User::class, 'preliminary_qa_by');
    }

    public function preliminaryQc()
    {
        return $this->belongsTo(User::class, 'preliminary_qc_by');
    }

    public function lots()
    {
        return $this->hasMany(MaterialLot::class);
    }

    public function movements()
    {
        return $this->hasMany(MaterialStockMovement::class);
    }
}
