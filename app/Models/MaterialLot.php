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
        'temporary_stored_at' => 'datetime',
        'qa_reviewed_at' => 'datetime',
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

    public function qaReviewedBy()
    {
        return $this->belongsTo(User::class, 'qa_reviewed_by');
    }

    public function temporaryStoredBy()
    {
        return $this->belongsTo(User::class, 'temporary_stored_by');
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
            'temporary_stored' => 'Đã nhập kho tạm',
            'pending_qa' => $this->material_type === 'raw_material' ? 'Chờ QA cập nhật COA' : 'Chờ QC xác nhận',
            'qa_reviewed' => 'QA đã cập nhật, chờ QC',
            'pending_qc_inspection' => 'Chờ QC kiểm tra',
            'qc_passed_pending_receipt' => 'QC đạt - chờ Kho nhập chính thức',
            'active' => 'QC đạt - đã vào tồn',
            'pending_reinspection' => 'Chờ QC kiểm tra lại',
            'rejected' => 'QC không đạt',
            default => 'Chờ xử lý',
        };
    }
}
