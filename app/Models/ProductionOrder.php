<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    protected $fillable = [
        'production_code',
        'order_id',
        'order_item_id',
        'production_monthly_plan_line_id',
        'product_bom_id',
        'product_id',
        'planned_quantity',
        'provisional_batch_number',
        'actual_quantity',
        'pending_finished_quantity',
        'unit',
        'yield_rate',
        'status',
        'created_by',
        'materials_issued_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'actual_quantity' => 'decimal:4',
        'pending_finished_quantity' => 'decimal:4',
        'yield_rate' => 'decimal:5',
        'materials_issued_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function monthlyPlanLine()
    {
        return $this->belongsTo(ProductionMonthlyPlanLine::class, 'production_monthly_plan_line_id');
    }

    public function bom()
    {
        return $this->belongsTo(ProductBom::class, 'product_bom_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function materials()
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function finishedBatches()
    {
        return $this->hasMany(ProductionFinishedBatch::class);
    }
}