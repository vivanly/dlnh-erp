<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturnOrder extends Model
{
    protected $fillable = [
        'return_code',
        'supplier_id',
        'purchase_order_id',
        'supplier_batch_id',
        'material_type',
        'material_id',
        'purchase_order_item_id',
        'quantity',
        'unit',
        'reason',
        'qc_test_report',
        'qc_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'qc_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function getCatalogItemAttribute()
    {
        return match ($this->material_type) {
            'raw_material' => RawMaterial::find($this->material_id),
            'accessory' => Accessory::find($this->material_id),
            default => null,
        };
    }

    public function supplierBatch()
    {
        return $this->belongsTo(SupplierBatch::class);
    }
}