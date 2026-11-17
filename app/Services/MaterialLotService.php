<?php

namespace App\Services;

use App\Models\MaterialLot;
use App\Models\MaterialLotInspection;
use App\Models\MaterialStockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;

class MaterialLotService
{
    /**
     * Store material in temporary warehouse
     */
    public function storeInTemporaryWarehouse(MaterialLot $materialLot, string $locationCode, ?string $note = null): void
    {
        DB::transaction(function () use ($materialLot, $locationCode, $note) {
            $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
            
            if (!in_array($lot->status, ['pending_qa', 'pending_qc_inspection'], true)) {
                throw new DomainException('Chỉ lô chờ QA/QC mới có thể nhập kho tạm.');
            }

            $lot->update([
                'status' => 'temporary_stored',
                'temporary_location_code' => $locationCode,
                'temporary_stored_at' => now(),
                'temporary_stored_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Update COA for raw material (QA action)
     */
    public function updateCoa(MaterialLot $materialLot, array $data): void
    {
        DB::transaction(function () use ($materialLot, $data) {
            $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();

            if ($lot->material_type !== 'raw_material') {
                throw new DomainException('Chỉ dược liệu thô mới yêu cầu QA cập nhật COA.');
            }

            if (!in_array($lot->status, ['temporary_stored', 'pending_qa', 'pending_reinspection'], true)) {
                throw new DomainException('Lô đã được QC kết luận, không thể sửa.');
            }

            $lot->update([
                'batch_number' => $data['batch_number'] ?? $lot->batch_number,
                'mfg_date' => $data['mfg_date'] ?? $lot->mfg_date,
                'exp_date' => $data['exp_date'] ?? $lot->exp_date,
                'coa_file' => $data['coa_file'] ?? $lot->coa_file,
                'status' => 'qa_reviewed',
                'qa_reviewed_at' => now(),
                'qa_reviewed_by' => auth()->id(),
            ]);

            MaterialLotInspection::create([
                'material_lot_id' => $lot->id,
                'checked_by' => auth()->id(),
                'result' => 'qa_reviewed',
                'inspection_stage' => 'qa_review',
                'checked_at' => now(),
            ]);
        });
    }

    /**
     * QC approve material lot
     */
    public function approveLot(MaterialLot $materialLot, ?string $note = null): void
    {
        DB::transaction(function () use ($materialLot, $note) {
            $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lot->status, ['temporary_stored', 'qa_reviewed', 'pending_qa', 'pending_reinspection'], true)) {
                throw new DomainException('Lô không ở trạng thái cho phép QC xác nhận.');
            }

            if ($lot->material_type === 'raw_material' && $lot->needsQa()) {
                throw new DomainException('QA chưa cập nhật đủ COA và thông tin bắt buộc của dược liệu thô.');
            }

            $lot->update([
                'status' => 'qc_passed_pending_receipt',
                'qc_by' => auth()->id(),
                'qc_at' => now(),
            ]);

            MaterialLotInspection::create([
                'material_lot_id' => $lot->id,
                'checked_by' => auth()->id(),
                'result' => 'passed',
                'inspection_stage' => 'qc_inspection',
                'detailed_findings' => $note ?? 'QC xác nhận đạt tiêu chuẩn chất lượng',
                'checked_at' => now(),
            ]);
        });
    }

    /**
     * QC reject material lot
     */
    public function rejectLot(MaterialLot $materialLot, string $qcNote, ?string $detailedFindings = null): void
    {
        DB::transaction(function () use ($materialLot, $qcNote, $detailedFindings) {
            $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();

            if (!in_array($lot->status, ['temporary_stored', 'qa_reviewed', 'pending_qa', 'pending_reinspection'], true)) {
                throw new DomainException('Lô không ở trạng thái cho phép QC kết luận.');
            }

            if ($lot->material_type === 'raw_material' && $lot->needsQa()) {
                throw new DomainException('QA cần cập nhật COA trước khi QC kết luận.');
            }

            $lot->update([
                'status' => 'rejected',
                'qc_by' => auth()->id(),
                'qc_at' => now(),
                'qc_note' => $qcNote,
            ]);

            MaterialLotInspection::create([
                'material_lot_id' => $lot->id,
                'checked_by' => auth()->id(),
                'result' => 'failed',
                'inspection_stage' => 'qc_inspection',
                'detailed_findings' => $detailedFindings ?? $qcNote,
                'checked_at' => now(),
            ]);
        });
    }

    /**
     * Request reinspection for rejected lot
     */
    public function requestReinspection(MaterialLot $materialLot, string $note): void
    {
        DB::transaction(function () use ($materialLot, $note) {
            $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();

            if ($lot->status !== 'rejected') {
                throw new DomainException('Chỉ lô QC không đạt mới có thể đưa đi kiểm tra lại.');
            }

            $lot->update(['status' => 'pending_reinspection']);

            MaterialLotInspection::create([
                'material_lot_id' => $lot->id,
                'checked_by' => auth()->id(),
                'result' => 'reinspection_requested',
                'inspection_stage' => 'qc_inspection',
                'detailed_findings' => $note,
                'corrective_action' => 'Yêu cầu kiểm tra lại',
                'checked_at' => now(),
            ]);
        });
    }

    /**
     * Post material to main stock (after QC approval)
     */
    public function postStockReceipt(MaterialLot $materialLot): void
    {
        DB::transaction(function () use ($materialLot) {
            $lot = MaterialLot::with('purchaseOrderItem')->whereKey($materialLot->id)->lockForUpdate()->firstOrFail();

            if ($lot->status !== 'qc_passed_pending_receipt') {
                throw new DomainException('Chỉ lô QC đạt và đang chờ Kho mới được nhập kho chính thức.');
            }

            if ($lot->material_type === 'raw_material' && $lot->needsQa()) {
                throw new DomainException('Không thể nhập kho vì hồ sơ QA/COA của lô chưa đầy đủ.');
            }

            MaterialStockMovement::create([
                'material_type' => $lot->material_type,
                'material_id' => $lot->material_id,
                'movement_type' => 'RECEIVE_PURCHASE',
                'direction' => 'in',
                'quantity' => $lot->quantity,
                'unit' => $lot->unit,
                'batch_number' => $lot->material_type === 'raw_material' ? $lot->batch_number : null,
                'mfg_date' => $lot->material_type === 'raw_material' ? $lot->mfg_date : null,
                'exp_date' => $lot->material_type === 'raw_material' ? $lot->exp_date : null,
                'purchase_order_item_id' => $lot->purchase_order_item_id,
                'material_receipt_id' => $lot->material_receipt_id,
                'material_lot_id' => $lot->id,
                'user_id' => auth()->id(),
            ]);

            $lot->update([
                'status' => 'active',
                'stock_receipt_code' => 'IN-MAT-' . now()->format('Ymd') . '-' . str_pad((string) $lot->id, 6, '0', STR_PAD_LEFT),
                'stock_received_by' => auth()->id(),
                'stock_received_at' => now(),
            ]);
        });
    }

    /**
     * Get summary statistics for a material lot
     */
    public function getSummary(MaterialLot $materialLot): array
    {
        return [
            'id' => $materialLot->id,
            'status_label' => $materialLot->status_label,
            'material_type' => $materialLot->material_type,
            'quantity' => $materialLot->quantity,
            'unit' => $materialLot->unit,
            'batch_number' => $materialLot->batch_number,
            'mfg_date' => $materialLot->mfg_date?->format('Y-m-d'),
            'exp_date' => $materialLot->exp_date?->format('Y-m-d'),
            'temporary_location' => $materialLot->temporary_location_code,
            'inspection_count' => $materialLot->inspections()->count(),
            'created_at' => $materialLot->created_at?->format('Y-m-d H:i'),
            'is_qa_complete' => !$materialLot->needsQa(),
        ];
    }
}
