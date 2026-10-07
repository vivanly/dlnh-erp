<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionMonthlyPlanLine extends Model
{
    protected $fillable = [
        'production_monthly_plan_id',
        'product_id',
        'product_bom_id',
        'ppcb_id',
        'planned_quantity',
        'unit',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
    ];

    public function plan()
    {
        return $this->belongsTo(ProductionMonthlyPlan::class, 'production_monthly_plan_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ppcb()
    {
        return $this->belongsTo(Ppcb::class);
    }

    public function bom()
    {
        return $this->belongsTo(ProductBom::class, 'product_bom_id');
    }

    public function productionOrder()
    {
        return $this->hasOne(ProductionOrder::class);
    }
}
