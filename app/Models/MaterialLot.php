<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialLot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'qc_at' => 'datetime',
        'mfg_date' => 'date',
        'exp_date' => 'date',
        'stock_received_at' => 'datetime',
    ];

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function materialReceipt()
    {
        return $this->belongsTo(MaterialReceipt::class);
    }

    public function inspections()
    {
        return $this->hasMany(MaterialLotInspection::class);
    }

    public function supplierReturns()
    {
        return $this->hasMany(SupplierReturnOrder::class);
    }

    public function qcBy()
    {
        return $this->belongsTo(User::class, 'qc_by');
    }

    public function stockReceiver()
    {
        return $this->belongsTo(User::class, 'stock_received_by');
    }

    public function needsQa(): bool
    {
        return $this->material_type === 'raw_material'
            && (! $this->coa_file || ! $this->batch_number || ! $this->mfg_date || ! $this->exp_date);
    }

    public function getCatalogItemAttribute()
    {
        return CatalogItem::findByType($this->material_type, (int) $this->material_id);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'QC đạt - đã vào tồn',
            'qc_passed_pending_receipt' => 'QC đạt - chờ Kho nhập chính thức',
            'rejected' => 'QC không đạt',
            'pending_reinspection' => 'Chờ QC kiểm tra lại',
            default => $this->needsQa() ? 'Chờ QA cập nhật lô/COA' : 'Chờ QC xác nhận',
        };
    }
}
