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
        $user = auth()->user();
        abort_unless($user && (
            $user->isExecutiveBoardMember()
            || $this->canQa()
            || $this->canQc()
            || $user->isWarehouseDepartment()
            || $user->isWarehouseManager()
        ), 403);

        $validated = $request->validate([
            'type' => ['nullable', 'in:raw_material,ingredient,accessory,excipient,packaging'],
            'status' => ['nullable', 'in:pending_qa,pending_reinspection,qc_passed_pending_receipt,active,rejected'],
        ]);
        $type = $validated['type'] ?? 'raw_material';
        $materialTypes = $type === 'raw_material'
            ? ['raw_material']
            : ['ingredient', 'accessory', 'excipient', 'packaging'];
        $catalogModel = \App\Models\CatalogItem::modelForType($type);
        $lots = MaterialLot::with([
            'purchaseOrderItem.purchaseOrder.supplier',
            'materialReceipt.receiver',
            'materialReceipt.preliminaryQa',
            'materialReceipt.preliminaryQc',
            'inspections.checker',
            'supplierReturns',
        ])
            ->where('material_type', $type)
            ->when(!empty($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when($request->filled('search'), function ($query) use ($request, $catalogModel) {
                $search = $request->search;
                $query->where(function ($q) use ($search, $catalogModel) {
                    $q->where('batch_number', 'like', "%{$search}%")
                        ->orWhereIn('material_id', $catalogModel::where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->select('id'));
                });
            })
            ->orderByRaw("FIELD(status, 'pending_qa', 'pending_reinspection', 'qc_passed_pending_receipt', 'active', 'rejected')")
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('material-lots.index', [
            'lots' => $lots,
            'type' => $type,
            'typeLabel' => \App\Models\CatalogItem::labelForType($type),
            'materialTypes' => $materialTypes,
            'canQa' => $this->canQa(),
            'canQc' => $this->canQc(),
        ]);
    }

    public function updateCoa(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQa(), 403, 'Chỉ QA mới được cập nhật COA.');
        if ($materialLot->material_type !== 'raw_material') {
            return back()->with('error', 'Chỉ dược liệu thô mới yêu cầu QA cập nhật COA.');
        }
        if (! in_array($materialLot->status, ['pending_qa', 'pending_reinspection'], true)) {
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
        if (! $path) {
            return back()->withInput()->with('error', 'Cần tải COA PDF của dược liệu thô lên trước khi chuyển QC.');
        }

        $materialLot->update([
            'batch_number' => trim($data['batch_number']),
            'mfg_date' => $data['mfg_date'],
            'exp_date' => $data['exp_date'],
            'coa_file' => $path,
        ]);
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'QA đã cập nhật số lô nhà cung cấp và COA. Lô chờ QC xác nhận.');
    }

    public function approve(MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được xác nhận chất lượng.');

        try {
            DB::transaction(function () use ($materialLot) {
                $lot = MaterialLot::with('purchaseOrderItem')->whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                if (! in_array($lot->status, ['pending_qa', 'pending_reinspection'], true)) {
                    throw new DomainException('Lô không còn chờ QC.');
                }
                if ($lot->needsQa()) {
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
                    'checked_at' => now(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'QC xác nhận đạt. Lô đã chuyển sang hàng chờ Kho lập phiếu nhập kho chính thức.');
    }

    public function reject(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được kết luận chất lượng.');
        $data = $request->validate(['qc_note' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($materialLot, $data) {
                $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                if (! in_array($lot->status, ['pending_qa', 'pending_reinspection'], true)) {
                    throw new DomainException('Lô không còn chờ QC.');
                }
                if ($lot->needsQa()) {
                    throw new DomainException('QA cần cập nhật COA trước khi QC kết luận.');
                }

                $lot->update(['status' => 'rejected', 'qc_by' => auth()->id(), 'qc_at' => now(), 'qc_note' => $data['qc_note']]);
                MaterialLotInspection::create([
                    'material_lot_id' => $lot->id,
                    'checked_by' => auth()->id(),
                    'result' => 'failed',
                    'note' => $data['qc_note'],
                    'checked_at' => now(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'QC kết luận không đạt; lô chưa vào tồn kho, có thể yêu cầu kiểm tra lại hoặc lập phiếu trả NCC.');
    }

    public function requestReinspection(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được yêu cầu kiểm tra lại.');
        $data = $request->validate(['note' => 'required|string|max:1000']);

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
                    'note' => $data['note'],
                    'checked_at' => now(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã chuyển lô về hàng chờ QC kiểm tra lại.');
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
            'materialReceipt.preliminaryQa',
            'materialReceipt.preliminaryQc',
            'qcBy',
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
            'materialReceipt.preliminaryQa',
            'materialReceipt.preliminaryQc',
        ]);

        return view('material-lots.stock-receipt', compact('materialLot'));
    }
}
