<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TraceabilityLotCode extends Model
{
    protected $fillable = [
        'supplier_batch_id',
        'production_finished_batch_id',
        'trace_code',
    ];
}
