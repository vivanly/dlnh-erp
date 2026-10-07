<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierBatch extends Model
{
    protected $table = 'supplier_batches';

    protected $fillable = [
        'goods_receipt_item_id',
        'product_id',
        'batch_number',
        'initial_quantity',
        'current_quantity',
        'mfg_date',
        'exp_date',
        'coa_file',
        'status',
    ];

    protected $casts = [
        'initial_quantity' => 'decimal:4',
        'current_quantity' => 'decimal:4',
        'mfg_date' => 'date',
        'exp_date' => 'date',
    ];

    // Mối quan hệ ngược lại với GoodsReceiptItem
    public function goodsReceiptItem()
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    // Mối quan hệ với Product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function productionMaterialLots()
    {
        return $this->hasMany(ProductionMaterialLot::class);
    }

    public function salesOrderAllocations()
    {
        return $this->hasMany(SalesOrderLotAllocation::class);
    }

    public function traceabilityCode()
    {
        return $this->hasOne(TraceabilityLotCode::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}