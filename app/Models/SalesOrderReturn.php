<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderReturn extends Model
{
    protected $fillable = [
        'order_id',
        'received_by',
        'received_at',
        'status',
        'note',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(SalesOrderReturnItem::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
