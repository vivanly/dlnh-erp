<?php

namespace App\Http\Controllers;

use App\Models\MaterialLot;
use App\Models\MaterialLotInspection;
use App\Models\MaterialStockMovement;
use App\Models\SupplierReturnOrder;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MaterialLotController extends Controller
{
    private function canQa(): bool
    {
        $user = auth()->user();

        return $user && ($user->isQADepartment() || $user->isITDepartment());
    }

    private function canQc(): bool
    {
        $user = auth()->user();

        return $user && ($user->isQCDepartment() || $user->isITDepartment());
    }

    public function index(Request $request)
    {
        return $this->materialLotsIndex($request, 'qc');
    }

    public function qaIndex(Request $request)
    {
        abort_unless($this->canQa(), 403, 'Chỉ QA mới được cập nhật COA.');

        return $this->materialLotsIndex($request, 'qa');
    }

    public function qcIndex(Request $request)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được xác định chất lượng.');

        return $this->materialLotsIndex($request, 'qc');
    }

    private function materialLotsIndex(Request $request, string $stage)
    {
        $user = auth()->user();
        abort_unless($user && ($stage === 'qa'
            ? $this->canQa()
            : (
            $user->isExecutiveBoardMember()
            || $this->canQa()
            || $this->canQc()
            || $user->isWarehouseDepartment()
            || $user->isWarehouseManager()
            )), 403);

        $validated = $request->validate([
            'type' => $stage === 'qa' ? ['prohibited'] : ['nullable', 'in:raw_material,ingredient,accessory,excipient,packaging'],
            'status' => $stage === 'qa' ? ['prohibited'] : ['nullable', 'in:pending_qc_inspection,pending_reinspection,qc_passed_pending_receipt,active,rejected'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $type = $stage === 'qa' ? 'raw_material' : ($validated['type'] ?? 'raw_material');
        $materialTypes = $type === 'raw_material'
            ? ['raw_material']
            : ['ingredient', 'accessory', 'excipient', 'packaging'];
        $catalogModel = \App\Models\CatalogItem::modelForType($type);
        $lots = MaterialLot::with([
            'purchaseOrderItem.purchaseOrder.supplier',
            'materialReceipt.receiver',
            'inspections.checker',
            'supplierReturns',
        ])
            ->where('material_type', $type)
            ->when($stage === 'qa', fn ($query) => $query->where('status', 'pending_qa'))
            ->when($stage === 'qc', fn ($query) => $query
                ->whereIn('status', ['pending_qc_inspection', 'pending_reinspection', 'rejected', 'qc_passed_pending_receipt', 'active'])
                ->when(!empty($validated['status']), fn ($statusQuery) => $statusQuery->where('status', $validated['status'])))
            ->when(!empty($validated['search']), function ($query) use ($validated, $catalogModel) {
                $search = $validated['search'];
                $query->where(function ($q) use ($search, $catalogModel) {
                    $q->where('batch_number', 'like', "%{$search}%")
                        ->orWhereIn('material_id', $catalogModel::where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->select('id'));
                });
            })
            ->orderByRaw("FIELD(status, 'pending_qc_inspection', 'pending_reinspection', 'rejected', 'qc_passed_pending_receipt', 'active')")
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('material-lots.index', [
            'lots' => $lots,
            'type' => $type,
            'stage' => $stage,
            'typeLabel' => \App\Models\CatalogItem::labelForType($type),
            'materialTypes' => $materialTypes,
            'canQa' => $this->canQa(),
            'canQc' => $this->canQc(),
        ]);
    }

    /**
     * Display temporary storage lots
     */
    public function temporaryStorage(Request $request)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isWarehouseDepartment() || $user->isWarehouseManager() || $user->isQCDepartment() || $user->isITDepartment()), 403);

        $validated = $request->validate([
            'type' => ['nullable', 'in:raw_material,ingredient,accessory,excipient,packaging'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $type = $validated['type'] ?? null;
        $search = $validated['search'] ?? null;

        $query = MaterialLot::where('status', 'temporary_stored')
            ->with(['catalogItem', 'purchaseOrderItem.purchaseOrder.supplier', 'temporaryStoredBy', 'materialReceipt']);

        if ($type) {
            $query->where('material_type', $type);
        }

        if ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->whereHas('catalogItem', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('sku', 'like', "%$search%");
                })->orWhereHas('purchaseOrderItem.purchaseOrder.supplier', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%");
                });
            });
        }

        $lots = $query->paginate(15);

        return view('material-lots.temporary-storage', compact('lots'));
    }

    /**
     * Move lot from temporary storage to QA review or QC inspection
     */
    public function moveFromTemporaryStorage(MaterialLot $materialLot)
    {
        $user = auth()->user();
        abort_unless($user && ($user->isWarehouseDepartment() || $user->isWarehouseManager()), 403, 'Chỉ Kho mới được chuyển lô từ kho tạm.');

        try {
            DB::transaction(function () use ($materialLot) {
                $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                
                if ($lot->status !== 'temporary_stored') {
                    throw new DomainException('Chỉ lô trong kho tạm mới có thể được chuyển sang kiểm tra.');
                }

                // Nếu là dược liệu thô → chờ QA cập nhật
                // Nếu là vật tư khác → chờ QC kiểm tra
                $newStatus = $lot->material_type === 'raw_material' 
                    ? 'pending_qa' 
                    : 'pending_qc_inspection';

                $lot->update(['status' => $newStatus]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã chuyển lô từ kho tạm sang kiểm tra.');
    }

    public function updateCoa(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQa(), 403, 'Chỉ QA mới được cập nhật COA.');
        if ($materialLot->material_type !== 'raw_material') {
            return back()->with('error', 'Chỉ dược liệu thô mới yêu cầu QA cập nhật COA.');
        }
        if ($materialLot->status !== 'pending_qa') {
            return back()->with('error', 'Lô đã được QC kết luận, không thể sửa.');
        }

        $data = $request->validate([
            'batch_number' => ['required', 'string', 'max:255'],
            'mfg_date' => ['required', 'date'],
            'exp_date' => ['required', 'date', 'after_or_equal:mfg_date'],
            'coa_file' => 'nullable|mimes:pdf|max:10240',
        ]);

        $oldPath = $materialLot->coa_file;
        $path = $oldPath;
        if ($request->hasFile('coa_file')) {
            $path = $request->file('coa_file')->store('material-coas', 'local');
        }
        if (!$path) {
            return back()->withInput()->with('error', 'Cần tải COA PDF của dược liệu thô lên trước khi chuyển QC.');
        }

        try {
            DB::transaction(function () use ($materialLot, $data, $path, $oldPath) {
                $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                
                $lot->update([
                    'batch_number' => trim($data['batch_number']),
                    'mfg_date' => $data['mfg_date'],
                    'exp_date' => $data['exp_date'],
                    'coa_file' => $path,
                    'status' => 'pending_qc_inspection',
                    'qa_reviewed_at' => now(),
                    'qa_reviewed_by' => auth()->id(),
                ]);
                
                if ($oldPath && $oldPath !== $path) {
                    Storage::disk('local')->delete($oldPath);
                }

                MaterialLotInspection::create([
                    'material_lot_id' => $lot->id,
                    'checked_by' => auth()->id(),
                    'result' => 'qa_reviewed',
                    'inspection_stage' => 'qa_review',
                    'detailed_findings' => 'QA đã cập nhật COA và thông tin lô',
                    'checked_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Lỗi khi cập nhật COA: ' . $e->getMessage());
        }

        return back()->with('success', 'QA đã cập nhật số lô nhà cung cấp và COA. Lô chờ QC xác nhận.');
    }

    public function recordInspection(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được xác nhận chất lượng.');

        return $this->saveQcInspection($request, $materialLot);
    }

    public function approve(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được xác nhận chất lượng.');

        return $this->saveQcInspection($request, $materialLot, 'passed');
    }

    public function reject(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được kết luận chất lượng.');

        return $this->saveQcInspection($request, $materialLot, 'failed');
    }

    private function saveQcInspection(Request $request, MaterialLot $materialLot, ?string $forcedResult = null)
    {
        if (!in_array($materialLot->status, ['pending_qc_inspection', 'pending_reinspection'], true)) {
            return back()->with('error', 'Lô chưa ở trạng thái chờ QC kiểm tra.');
        }
        if ($materialLot->material_type === 'raw_material' && $materialLot->needsQa()) {
            return back()->with('error', 'QA cần cập nhật đầy đủ COA và thông tin lô trước khi QC kết luận.');
        }

        $rules = [
            'result' => [$forcedResult ? 'sometimes' : 'required', 'in:passed,failed'],
            'qc_note' => ['nullable', 'string', 'max:1000'],
            'detailed_findings' => ['nullable', 'string', 'max:2000'],
        ];
        if ($materialLot->material_type === 'raw_material') {
            $rules += [
                'qc_test_report' => ['required', 'string', 'max:255'],
                'qc_date' => ['required', 'date'],
                'qc_test_report_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            ];
        }

        $data = $request->validate($rules);
        $result = $forcedResult ?? $data['result'];
        if ($result === 'failed' && empty($data['qc_note']) && empty($data['detailed_findings'])) {
            return back()->withErrors(['qc_note' => 'Vui lòng nhập lý do QC xác định không đạt.'])->withInput();
        }

        $reportPath = null;
        if ($materialLot->material_type === 'raw_material') {
            $reportPath = $request->file('qc_test_report_file')->store('material-qc-reports', 'local');
            if (! $reportPath) {
                throw new RuntimeException('Không lưu được phiếu kiểm nghiệm QC.');
            }
        }

        try {
            DB::transaction(function () use ($materialLot, $data, $result, $reportPath) {
                $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                if (!in_array($lot->status, ['pending_qc_inspection', 'pending_reinspection'], true)) {
                    throw new DomainException('Lô không còn ở trạng thái chờ QC kiểm tra.');
                }
                if ($lot->material_type === 'raw_material' && $lot->needsQa()) {
                    throw new DomainException('QA cần cập nhật COA trước khi QC kết luận.');
                }

                $lot->update([
                    'status' => $result === 'passed' ? 'qc_passed_pending_receipt' : 'rejected',
                    'qc_by' => auth()->id(),
                    'qc_at' => now(),
                    'qc_note' => $data['qc_note'] ?? ($data['detailed_findings'] ?? null),
                ]);

                MaterialLotInspection::create([
                    'material_lot_id' => $lot->id,
                    'checked_by' => auth()->id(),
                    'result' => $result,
                    'inspection_stage' => 'qc_inspection',
                    'note' => $data['qc_note'] ?? null,
                    'detailed_findings' => $data['detailed_findings'] ?? ($data['qc_note'] ?? null),
                    'report_number' => $data['qc_test_report'] ?? null,
                    'report_date' => $data['qc_date'] ?? null,
                    'report_file' => $reportPath,
                    'checked_at' => now(),
                ]);
            });
        } catch (DomainException $exception) {
            if ($reportPath) {
                Storage::disk('local')->delete($reportPath);
            }

            return back()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            if ($reportPath) {
                Storage::disk('local')->delete($reportPath);
            }

            throw $exception;
        }

        return back()->with('success', $result === 'passed'
            ? 'QC xác nhận đạt. Lô sẵn sàng được nhập kho chính thức.'
            : 'QC xác định không đạt. Có thể yêu cầu kiểm tra lại hoặc lập phiếu trả nhà cung cấp.');
    }

    public function requestReinspection(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được yêu cầu kiểm tra lại.');
        $data = $request->validate([
            'note' => 'required|string|max:1000',
            'corrective_action' => 'nullable|string|max:2000',
        ]);

        try {
            DB::transaction(function () use ($materialLot, $data) {
                $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                
                if ($lot->status !== 'rejected') {
                    throw new DomainException('Chỉ lô QC không đạt mới có thể đưa đi kiểm tra lại.');
                }
                
                if (SupplierReturnOrder::where('material_lot_id', $lot->id)->exists()) {
                    throw new DomainException('Lô đã có phiếu trả NCC; không thể chuyển sang kiểm tra lại.');
                }

                $lot->update(['status' => 'pending_reinspection']);
                
                MaterialLotInspection::create([
                    'material_lot_id' => $lot->id,
                    'checked_by' => auth()->id(),
                    'result' => 'reinspection_requested',
                    'inspection_stage' => 'qc_inspection',
                    'detailed_findings' => $data['note'],
                    'corrective_action' => $data['corrective_action'] ?? 'Yêu cầu kiểm tra lại',
                    'checked_at' => now(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã chuyển lô về trạng thái chờ QC kiểm tra lại.');
    }

    public function readyForStockReceipt()
    {
        $user = auth()->user();
        abort_unless($user && (
            $user->isWarehouseDepartment()
            || $user->isWarehouseManager()
            || $user->isITDepartment()
        ), 403);

        $lots = MaterialLot::with([
            'purchaseOrderItem.purchaseOrder.supplier',
            'qcBy',
            'inspections',
        ])
            ->where('status', 'qc_passed_pending_receipt')
            ->orderBy('qc_at')
            ->paginate(30);

        return view('material-lots.ready-for-receipt', compact('lots'));
    }

    public function postStockReceipt(MaterialLot $materialLot)
    {
        $user = auth()->user();
        abort_unless($user && (
            $user->isWarehouseDepartment()
            || $user->isWarehouseManager()
            || $user->isITDepartment()
        ), 403, 'Chỉ Kho mới được lập phiếu nhập kho chính thức.');

        try {
            DB::transaction(function () use ($materialLot) {
                $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                if ($lot->status !== 'qc_passed_pending_receipt') {
                    throw new DomainException('Chỉ lô QC đạt và đang chờ Kho mới được nhập kho.');
                }
                if ($lot->needsQa()) {
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
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã lập phiếu nhập kho chính thức và cộng tồn vật tư.');
    }

    public function coa(MaterialLot $materialLot)
    {
        $user = auth()->user();
        abort_unless($user && (
            $user->isExecutiveBoardMember()
            || $this->canQa()
            || $this->canQc()
            || $user->isWarehouseDepartment()
        ), 403);
        abort_unless($materialLot->coa_file && Storage::disk('local')->exists($materialLot->coa_file), 404);

        return Storage::disk('local')->response($materialLot->coa_file);
    }

    public function inspectionReport(MaterialLotInspection $inspection)
    {
        $user = auth()->user();
        abort_unless($user && (
            $user->isExecutiveBoardMember()
            || $this->canQa()
            || $this->canQc()
            || $user->isWarehouseDepartment()
            || $user->isWarehouseManager()
        ), 403);
        abort_unless(
            $inspection->materialLot?->material_type === 'raw_material'
                && $inspection->report_file
                && Storage::disk('local')->exists($inspection->report_file),
            404
        );

        return Storage::disk('local')->response($inspection->report_file);
    }

    public function stockReceipt(MaterialLot $materialLot)
    {
        $user = auth()->user();
        abort_unless($user && (
            $user->isExecutiveBoardMember()
            || $this->canQa()
            || $this->canQc()
            || $user->isWarehouseDepartment()
            || $user->isWarehouseManager()
        ), 403);
        abort_unless($materialLot->status === 'active' && $materialLot->stock_receipt_code, 404);

        $materialLot->load([
            'purchaseOrderItem.purchaseOrder.supplier',
            'materialReceipt.receiver',
            'inspections',
        ]);

        return view('material-lots.stock-receipt', compact('materialLot'));
    }
}
