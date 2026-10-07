<?php

namespace App\Http\Controllers;

use App\Models\ProductionFinishedBatch;
use App\Models\SupplierBatch;
use App\Models\TraceabilityLotCode;
use App\Models\TraceabilitySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TraceabilityManagementController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $canManageTraceability = $this->canManageTraceability();
        $filter = $request->query('type', 'all');
        if (!in_array($filter, ['all', 'supplier', 'finished'], true)) {
            $filter = 'all';
        }
        $perPage = (int) $request->query('per_page', 100);
        if (!in_array($perPage, [100, 500, 1000, 5000, 10000, 50000], true)) {
            $perPage = 100;
        }

        $supplierLots = DB::table('supplier_batches')
            ->join('products', 'products.id', '=', 'supplier_batches.product_id')
            ->selectRaw("'supplier' as lot_type, supplier_batches.id as lot_id, supplier_batches.batch_number, products.name as product_name, products.unit, supplier_batches.current_quantity, supplier_batches.status, supplier_batches.mfg_date, supplier_batches.exp_date");
        $finishedLots = DB::table('production_finished_batches')
            ->join('products', 'products.id', '=', 'production_finished_batches.product_id')
            ->selectRaw("'finished' as lot_type, production_finished_batches.id as lot_id, production_finished_batches.batch_number, products.name as product_name, production_finished_batches.unit, production_finished_batches.current_quantity, production_finished_batches.status, production_finished_batches.mfg_date, production_finished_batches.exp_date");

        $allLots = $filter === 'supplier'
            ? $supplierLots
            : ($filter === 'finished' ? $finishedLots : $supplierLots->unionAll($finishedLots));
        $lots = DB::query()->fromSub($allLots, 'all_lots')
            ->orderBy('batch_number')->orderBy('lot_type')
            ->paginate($perPage)->withQueryString();

        $setting = TraceabilitySetting::query()->first()
            ?? new TraceabilitySetting(['base_url' => 'http://localhost/code=']);

        $lotIds = $lots->getCollection()->pluck('lot_id');
        $codes = TraceabilityLotCode::query()
            ->where(function ($query) use ($lotIds) {
                $query->whereIn('supplier_batch_id', $lotIds)
                    ->orWhereIn('production_finished_batch_id', $lotIds);
            })
            ->get()
            ->keyBy(fn (TraceabilityLotCode $code) => $code->supplier_batch_id
                ? 'supplier-' . $code->supplier_batch_id
                : 'finished-' . $code->production_finished_batch_id);

        return view('traceability.manage', compact('lots', 'setting', 'codes', 'filter', 'perPage', 'canManageTraceability'));
    }

    public function updateBaseUrl(Request $request)
    {
        $this->authorizeAccess();
        $validated = $request->validate([
            'base_url' => ['nullable', 'string', 'max:500', 'url:http,https'],
        ]);

        $setting = TraceabilitySetting::query()->firstOrCreate([]);
        $setting->update(['base_url' => trim((string) ($validated['base_url'] ?? '')) ?: null]);

        return back()->with('status', 'Đã cập nhật đường dẫn truy xuất.');
    }

    public function deleteBaseUrl()
    {
        $this->authorizeAccess();
        $setting = TraceabilitySetting::query()->firstOrCreate([]);
        $setting->update(['base_url' => null]);

        return back()->with('status', 'Đã xóa đường dẫn truy xuất.');
    }

    public function updateLotCode(Request $request, string $type, int $batch)
    {
        $this->authorizeAccess();
        abort_unless(in_array($type, ['supplier', 'finished'], true), 404);
        $this->ensureBatchExists($type, $batch);
        $validated = $request->validate([
            'trace_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('traceability_lot_codes', 'trace_code')->ignore(
                    TraceabilityLotCode::query()
                        ->when($type === 'supplier', fn ($query) => $query->where('supplier_batch_id', $batch))
                        ->when($type === 'finished', fn ($query) => $query->where('production_finished_batch_id', $batch))
                        ->value('id')
                ),
            ],
        ]);

        $lookup = $type === 'supplier'
            ? ['supplier_batch_id' => $batch]
            : ['production_finished_batch_id' => $batch];
        TraceabilityLotCode::query()->updateOrCreate(
            $lookup,
            ['trace_code' => trim((string) ($validated['trace_code'] ?? '')) ?: null]
        );

        return back()->with('status', 'Đã cập nhật chuỗi truy xuất cho lô.');
    }

    public function deleteLotCode(string $type, int $batch)
    {
        $this->authorizeAccess();
        abort_unless(in_array($type, ['supplier', 'finished'], true), 404);
        $this->ensureBatchExists($type, $batch);

        $lookup = $type === 'supplier'
            ? ['supplier_batch_id' => $batch]
            : ['production_finished_batch_id' => $batch];
        TraceabilityLotCode::query()->updateOrCreate($lookup, ['trace_code' => null]);

        return back()->with('status', 'Đã xóa chuỗi truy xuất của lô.');
    }

    private function ensureBatchExists(string $type, int $batch): void
    {
        abort_unless(
            $type === 'supplier'
                ? SupplierBatch::query()->whereKey($batch)->exists()
                : ProductionFinishedBatch::query()->whereKey($batch)->exists(),
            404
        );
    }

    private function authorizeAccess(): void
    {
        abort_unless($this->canManageTraceability(), 403);
    }

    private function canManageTraceability(): bool
    {
        $user = auth()->user();

        return $user && ($user->isITDepartment() || $user->isQADepartment());
    }
}
