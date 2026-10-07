<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'order_code',
        'customer_id',
        'order_type',
        'order_date',
        'delivery_date',
        'province_city',
        'contact_person',
        'notes',
        'status',
        'warehouse_confirmed_at',
        'warehouse_stock_checked_at',
        'warehouse_stock_checked_by',
        'warehouse_packed_at',
        'qa_confirmed_at',
        'sales_approved_at',
        'sales_approved_by',
        'sales_rejection_reason',
        'production_plan_approved_at',
        'production_plan_approved_by',
        'production_plan_rejection_reason',
        'production_packaged_at',
        'production_packaged_by',
    ];

    protected $casts = [
        'warehouse_confirmed_at' => 'datetime',
        'warehouse_stock_checked_at' => 'datetime',
        'warehouse_packed_at' => 'datetime',
        'qa_confirmed_at' => 'datetime',
        'sales_approved_at' => 'datetime',
        'production_plan_approved_at' => 'datetime',
        'production_packaged_at' => 'datetime',
    ];

    /**
     * Quan hệ: 1 Đơn hàng thuộc về 1 Khách hàng
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Quan hệ: 1 Đơn hàng có nhiều chi tiết sản phẩm / vị thuốc (order_items)
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'order_id');
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['pending_warehouse_check', 'pending_qa', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true);
    }

    public function isEditLockedAfterQa(): bool
    {
        return in_array($this->status, ['pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'pending_production_approval', 'processing', 'ready_to_ship', 'completed'], true);
    }

    public function salesApprovedBy()
    {
        return $this->belongsTo(User::class, 'sales_approved_by');
    }

    public function productionPlanApprovedBy()
    {
        return $this->belongsTo(User::class, 'production_plan_approved_by');
    }

    public function productionPackagedBy()
    {
        return $this->belongsTo(User::class, 'production_packaged_by');
    }

    public function warehouseStockCheckedBy()
    {
        return $this->belongsTo(User::class, 'warehouse_stock_checked_by');
    }

    public function getLotAssignmentStatusAttribute(): string
    {
        $items = $this->items;
        $assignedCount = $items->filter(function ($item) {
            $reservedQuantity = $item->lotAllocations->whereIn('status', ['reserved', 'shipped'])->sum(function ($allocation) {
                return (float) $allocation->reserved_quantity;
            });

            return $reservedQuantity >= (float) $item->quantity;
        })->count();

        if ($assignedCount === 0) {
            return 'Chờ QA Cập Nhật';
        }

        return $assignedCount === $items->count() ? 'Đã Có Số Lô' : 'QA đang Cập Nhật';
    }

}