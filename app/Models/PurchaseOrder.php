<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number',
        'supplier_id',
        'user_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'warehouse_confirmed_at',
        'subtotal',
        'tax_amount',
        'grand_total',
        'notes',
    ];

    protected $casts = [
        'warehouse_confirmed_at' => 'datetime',
    ];

    // Quan hệ với Nhà cung cấp
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // Quan hệ với Nhân sự/User tạo đơn
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ với danh sách vị thuốc / chi tiết đơn mua hàng
    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }
}