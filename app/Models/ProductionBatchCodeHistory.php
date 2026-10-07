<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionBatchCodeHistory extends Model
{
    protected $fillable = [
        'production_finished_batch_id',
        'old_batch_number',
        'new_batch_number',
        'changed_by',
    ];

    public function productionFinishedBatch()
    {
        return $this->belongsTo(ProductionFinishedBatch::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}