<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialLotInspection extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checked_at' => 'datetime',
        'report_date' => 'date',
    ];

    public function materialLot()
    {
        return $this->belongsTo(MaterialLot::class);
    }

    public function checker()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
