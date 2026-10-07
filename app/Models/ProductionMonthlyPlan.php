<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMonthlyPlan extends Model
{
    protected $fillable = [
        'plan_month',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'plan_month' => 'date',
        'approved_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(ProductionMonthlyPlanLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
