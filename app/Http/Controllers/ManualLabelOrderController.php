<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelPrint;
use App\Models\ManualLabelProduct;
use App\Models\ManualLabelProductionOrder;
use App\Services\ManualLabelOrders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualLabelOrderController extends Controller
{
    public static function searchQuery(string $q)
    {
        $like = '%' . addcslashes($q, '%_\\') . '%';

        return ManualLabelProductionOrder::query()->where(function ($w) use ($like) {
            $w->where('label_code', 'like', $like)
                ->orWhere('production_order', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('batch', 'like', $like);
        });
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        $q = trim((string) $request->query('q'));

        $orders = ManualLabelProductionOrder::query()
            ->when($q !== '', fn () => self::searchQuery($q))
            ->orderByDesc('order_date')->orderByDesc('id')
            ->paginate($this->perPage($request))->withQueryString();

        $printed = ManualLabelPrint::whereIn('label_code', $orders->pluck('label_code'))
            ->selectRaw('label_code, SUM(copies_count) as total')->groupBy('label_code')->pluck('total', 'label_code');
        $products = ManualLabelProduct::whereIn('name_key', $orders->map(fn ($o) => ManualLabelProduct::key($o->name))->unique())
            ->get()->keyBy('name_key');

        return view('manual-labels.orders', [
            'q' => $q,
            'orders' => $orders,
            'printed' => $printed,
            'products' => $products,
            'hasData' => ManualLabelProductionOrder::exists(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);

        return view('manual-labels.order-form', [
            'order' => new ManualLabelProductionOrder(['production_order' => trim((string) $request->query('production_order'))]),
            'names' => $this->productNames(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        $data = $request->validate([
            'production_order' => ['required', 'string', 'max:64'],
            'order_date' => ['nullable', 'date'],
            'lines' => ['required', 'array', 'min:1', 'max:200'],
            'lines.*.name' => ['required', 'string', 'max:255'],
            'lines.*.batch' => ['required', 'string', 'max:100'],
            'lines.*.mfg_date' => ['nullable', 'date'],
            'lines.*.exp_date' => ['nullable', 'date', 'after_or_equal:lines.*.mfg_date'],
            'lines.*.pack_spec' => ['required', 'string', 'max:64'],
            'lines.*.quantity' => ['required', 'string', 'max:64'],
            'lines.*.label_type' => ['required', 'in:' . implode(',', array_keys(ManualLabelController::LABEL_TYPES))],
            'lines.*.traditional_medicine_name' => ['nullable', 'boolean'],
            'lines.*.qr_content' => ['nullable', 'string', 'max:1000'],
            'lines.*.storage' => ['nullable', 'string', 'max:255'],
        ]);
        foreach ($data['lines'] as $index => $line) {
            $normalized = ManualLabelOrders::normalizeWeightInputs($line);
            if ($normalized['quantity'] === null || $normalized['pack_spec'] === null) {
                throw ValidationException::withMessages([
                    "lines.{$index}.quantity" => 'Dòng ' . ($index + 1) . ': nhập SL và QCĐG bằng số kg, ví dụ 1, 2 hoặc 0.5.',
                ]);
            }
            $data['lines'][$index] = $normalized;
        }
        foreach ($data['lines'] as $index => $line) {
            try {
                ManualLabelOrders::calculate($line['quantity'], $line['pack_spec']);
            } catch (ValidationException $e) {
                throw ValidationException::withMessages(["lines.{$index}.quantity" => "Dòng " . ($index + 1) . ': ' . $e->getMessage()]);
            }
        }
        DB::transaction(function () use ($data) {
            foreach ($data['lines'] as $line) {
                $count = ManualLabelOrders::calculate($line['quantity'], $line['pack_spec'])['count'];
                $line['traditional_medicine_name'] = $line['label_type'] === 'vt' && ! empty($line['traditional_medicine_name']);
                $line += ['storage' => ManualLabelProductionOrder::DEFAULT_STORAGE];
                ManualLabelProductionOrder::create($line + [
                    'production_order' => $data['production_order'],
                    'order_date' => $data['order_date'] ?? null,
                    'label_code' => ManualLabelOrders::nextCode(),
                    'label_count' => $count,
                ]);
            }
        });

        return redirect()->route('manual-labels.orders')->with('success', 'Đã thêm lệnh sản xuất.');
    }

    public function edit(ManualLabelProductionOrder $order)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);

        return view('manual-labels.order-form', ['order' => $order, 'names' => $this->productNames()]);
    }

    public function update(Request $request, ManualLabelProductionOrder $order)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        ManualLabelOrders::ensureReady();
        $data = $this->validated($request);
        $data = ManualLabelOrders::normalizeWeightInputs($data);
        if ($data['quantity'] === null || $data['pack_spec'] === null) {
            throw ValidationException::withMessages(['quantity' => 'Nhập SL và QCĐG bằng số kg, ví dụ 1, 2 hoặc 0.5.']);
        }
        $data['traditional_medicine_name'] = $data['label_type'] === 'vt' && $request->boolean('traditional_medicine_name');
        $count = ManualLabelOrders::calculate($data['quantity'], $data['pack_spec'])['count'];
        DB::transaction(function () use ($order, $data, $count) {
            $locked = ManualLabelProductionOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $previousTemplate = $locked->toFormArray();
            $locked->fill($data + ['label_count' => $count]);
            $templateChanged = $previousTemplate['label_type'] !== $data['label_type']
                || $previousTemplate['traditional_medicine_name'] !== $data['traditional_medicine_name'];
            if (($templateChanged || $locked->isDirty(['production_order', 'name', 'batch', 'mfg_date', 'exp_date', 'pack_spec']))
                && ManualLabelPrint::where('label_code', $locked->label_code)->exists()) {
                throw ValidationException::withMessages(['name' => 'Không thể thay đổi thông tin nhãn đã có lịch sử in.']);
            }
            $locked->save();
        });

        return redirect()->route('manual-labels.orders')->with('success', 'Đã cập nhật lệnh sản xuất.');
    }

    public function destroy(ManualLabelProductionOrder $order)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        DB::transaction(function () use ($order) {
            $locked = ManualLabelProductionOrder::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (ManualLabelPrint::where('label_code', $locked->label_code)->exists()) {
                throw ValidationException::withMessages(['order' => 'Không thể xóa mã nhãn đã có lịch sử in.']);
            }
            $locked->delete();
        });

        return back()->with('success', 'Đã xóa lệnh sản xuất ' . $order->label_code . '.');
    }

    private function productNames()
    {
        return ManualLabelProduct::orderBy('name')->pluck('name');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'order_date' => ['nullable', 'date'],
            'production_order' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'batch' => ['required', 'string', 'max:100'],
            'mfg_date' => ['nullable', 'date'],
            'exp_date' => ['nullable', 'date', 'after_or_equal:mfg_date'],
            'pack_spec' => ['required', 'string', 'max:64'],
            'quantity' => ['required', 'string', 'max:64'],
            'label_type' => ['required', 'in:' . implode(',', array_keys(ManualLabelController::LABEL_TYPES))],
            'traditional_medicine_name' => ['nullable', 'boolean'],
            'qr_content' => ['nullable', 'string', 'max:1000'],
            'storage' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
