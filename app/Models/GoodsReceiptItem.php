<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    protected $fillable = [
        'goods_receipt_id', 
        'purchase_order_item_id', 
        'herb_id', 
        'ordered_quantity', 
        'received_quantity', 
        'returned_quantity', 
        'return_reason'
    ];

    public function herb()
    {
        return $this->belongsTo(Product::class, 'herb_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
    
    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    // Thêm quan hệ này để liên kết với các lô gốc của QA
    public function supplierBatches()
    {
        return $this->hasMany(SupplierBatch::class, 'goods_receipt_item_id');
    }
}