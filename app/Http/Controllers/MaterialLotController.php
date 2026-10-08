<?php

namespace App\Http\Controllers;

use App\Models\MaterialLot;
use App\Models\MaterialStockMovement;
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
        abort_unless($user && ($this->canQa() || $this->canQc() || $user->isWarehouseDepartment() || $user->isWarehouseManager()), 403);

        $lots = MaterialLot::with('purchaseOrderItem.purchaseOrder.supplier')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('batch_number', 'like', "%{$search}%")
                        ->orWhereIn('material_id', \App\Models\RawMaterial::where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->pluck('id'))
                        ->orWhereIn('material_id', \App\Models\Accessory::where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")->pluck('id'));
                });
            })
            ->orderByRaw("FIELD(status, 'pending_qa', 'active', 'rejected')")
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('material-lots.index', [
            'lots' => $lots,
            'canQa' => $this->canQa(),
            'canQc' => $this->canQc(),
        ]);
    }

    public function updateCoa(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQa(), 403, 'Chỉ QA mới được cập nhật số lô NCC và COA.');
        if ($materialLot->status !== 'pending_qa') {
            return back()->with('error', 'Lô đã được QC kết luận, không thể sửa.');
        }
        if ($materialLot->material_type !== 'raw_material') {
            return back()->with('error', 'Phụ liệu không có số lô NCC/COA; chỉ cần QC xác nhận.');
        }

        $data = $request->validate([
            'batch_number' => 'required|string|max:255',
            'mfg_date' => 'required|date',
            'exp_date' => 'required|date|after_or_equal:mfg_date',
            'coa_file' => 'nullable|mimes:pdf|max:10240',
        ]);

        $oldPath = $materialLot->coa_file;
        $path = $oldPath;
        if ($request->hasFile('coa_file')) {
            $path = $request->file('coa_file')->store('material-coas', 'local');
        }
        if (! $path) {
            return back()->withInput()->with('error', 'Cần tải COA PDF lên trước khi chuyển QC.');
        }

        $materialLot->update([
            'batch_number' => $data['batch_number'],
            'mfg_date' => $data['mfg_date'],
            'exp_date' => $data['exp_date'],
            'coa_file' => $path,
        ]);
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'QA đã cập nhật số lô NCC và COA. Lô chờ QC xác nhận chất lượng.');
    }

    public function approve(MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được xác nhận chất lượng.');

        try {
            DB::transaction(function () use ($materialLot) {
                $lot = MaterialLot::with('purchaseOrderItem')->whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                if ($lot->status !== 'pending_qa') {
                    throw new DomainException('Lô không còn chờ QC.');
                }
                if ($lot->needsQa()) {
                    throw new DomainException('QA chưa cập nhật đủ số lô NCC và COA.');
                }

                MaterialStockMovement::create([
                    'material_type' => $lot->material_type,
                    'material_id' => $lot->material_id,
                    'movement_type' => 'RECEIVE_PURCHASE',
                    'direction' => 'in',
                    'quantity' => $lot->quantity,
                    'unit' => $lot->unit,
                    'batch_number' => $lot->batch_number ?: null,
                    'mfg_date' => $lot->mfg_date,
                    'exp_date' => $lot->exp_date,
                    'purchase_order_item_id' => $lot->purchase_order_item_id,
                    'user_id' => auth()->id(),
                ]);
                $lot->update(['status' => 'active', 'qc_by' => auth()->id(), 'qc_at' => now()]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'QC xác nhận đạt, lô NCC đã vào tồn kho.');
    }

    public function reject(Request $request, MaterialLot $materialLot)
    {
        abort_unless($this->canQc(), 403, 'Chỉ QC mới được kết luận chất lượng.');
        $data = $request->validate(['qc_note' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($materialLot, $data) {
                $lot = MaterialLot::whereKey($materialLot->id)->lockForUpdate()->firstOrFail();
                if ($lot->status !== 'pending_qa') {
                    throw new DomainException('Lô không còn chờ QC.');
                }

                MaterialStockMovement::create([
                    'material_type' => $lot->material_type,
                    'material_id' => $lot->material_id,
                    'movement_type' => 'REJECT_PURCHASE',
                    'direction' => 'none',
                    'quantity' => $lot->quantity,
                    'unit' => $lot->unit,
                    'batch_number' => $lot->batch_number,
                    'purchase_order_item_id' => $lot->purchase_order_item_id,
                    'user_id' => auth()->id(),
                    'note' => $data['qc_note'],
                ]);
                $lot->update(['status' => 'rejected', 'qc_by' => auth()->id(), 'qc_at' => now(), 'qc_note' => $data['qc_note']]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'QC kết luận không đạt, lô không vào tồn kho.');
    }

    public function coa(MaterialLot $materialLot)
    {
        $user = auth()->user();
        abort_unless($user && ($this->canQa() || $this->canQc() || $user->isWarehouseDepartment()), 403);
        abort_unless($materialLot->coa_file && Storage::disk('local')->exists($materialLot->coa_file), 404);

        return Storage::disk('local')->response($materialLot->coa_file);
    }
}
