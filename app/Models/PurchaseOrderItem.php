<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
    ];

    // Quan hệ ngược lại với Đơn mua hàng
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // Quan hệ với Vị thuốc / Dược liệu
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function goodsReceiptItems()
    {
        return $this->hasMany(GoodsReceiptItem::class, 'purchase_order_item_id');
    }
}