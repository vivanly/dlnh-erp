<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelPrint;
use App\Models\ManualLabelProduct;
use App\Models\ManualLabelProductionOrder;
use Illuminate\Http\Request;

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

    public function create()
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);

        return view('manual-labels.order-form', ['order' => new ManualLabelProductionOrder(['label_count' => 1]), 'names' => $this->productNames()]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        ManualLabelProductionOrder::create($this->validated($request));

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
        $order->update($this->validated($request, $order));

        return redirect()->route('manual-labels.orders')->with('success', 'Đã cập nhật lệnh sản xuất.');
    }

    public function destroy(ManualLabelProductionOrder $order)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        $order->delete();

        return back()->with('success', 'Đã xóa lệnh sản xuất ' . $order->label_code . '.');
    }

    private function productNames()
    {
        return ManualLabelProduct::orderBy('name')->pluck('name');
    }

    private function validated(Request $request, ?ManualLabelProductionOrder $order = null): array
    {
        return $request->validate([
            'label_code' => ['required', 'string', 'max:50', \Illuminate\Validation\Rule::unique('manual_label_production_orders', 'label_code')->ignore($order?->id)],
            'order_date' => ['nullable', 'date'],
            'production_order' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'batch' => ['nullable', 'string', 'max:100'],
            'mfg_date' => ['nullable', 'date'],
            'exp_date' => ['nullable', 'date'],
            'pack_spec' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'string', 'max:100'],
            'label_count' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);
    }
}
