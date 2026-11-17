<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelPrint;
use App\Models\ManualLabelProductionOrder;
use App\Services\ManualLabelOrders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ManualLabelBatchController extends Controller
{
    public function create(Request $request)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        $code = trim((string) $request->query('order'));
        $orders = $code === '' ? collect() : ManualLabelProductionOrder::where('production_order', $code)->orderBy('id')->get();
        $printed = ManualLabelPrint::whereIn('label_code', $orders->pluck('label_code'))
            ->selectRaw('label_code, SUM(copies_count) as total')->groupBy('label_code')->pluck('total', 'label_code');
        $rows = $orders->map(function ($order) use ($printed) {
            try {
                $calculation = ManualLabelOrders::calculate($order->quantity, $order->pack_spec);
                $error = null;
            } catch (ValidationException $e) {
                $calculation = null;
                $error = $e->getMessage();
            }
            $product = $order->product();
            $problem = ManualLabelOrders::printProblem($order, $product);
            if ($problem) {
                $error = trim(($error ? $error . ' ' : '') . $problem);
            }
            $done = (int) ($printed[$order->label_code] ?? 0);

            return ['order' => $order, 'template' => $order->toFormArray($product), 'calculation' => $calculation, 'error' => $error,
                'printed' => $done, 'remaining' => max(0, ($calculation['count'] ?? 0) - $done)];
        });

        return view('manual-labels.batch-print', [
            'code' => $code, 'rows' => $rows,
            'ready' => Schema::hasTable('manual_label_sequences')
                && Schema::hasColumn('manual_label_production_orders', 'label_type')
                && Schema::hasColumn('manual_label_production_orders', 'traditional_medicine_name')
                && Schema::hasColumn('manual_label_production_orders', 'qr_content')
                && Schema::hasColumn('manual_label_production_orders', 'storage'),
            'orderCodes' => ManualLabelProductionOrder::whereNotNull('production_order')->where('production_order', '<>', '')
                ->distinct()->orderByDesc('production_order')->pluck('production_order'),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
        ManualLabelOrders::ensureReady();
        $data = $request->validate([
            'production_order' => ['required', 'string', 'max:64'],
            'counts' => ['required', 'array'],
            'counts.*' => ['required', 'integer', 'min:0', 'max:1000'],
            'include_partial' => ['required', 'in:0,1'],
            'allow_extra' => ['nullable', 'in:1'],
        ]);

        $labels = DB::transaction(function () use ($data) {
            $orders = ManualLabelProductionOrder::where('production_order', $data['production_order'])
                ->orderBy('id')->lockForUpdate()->get();
            if ($orders->isEmpty() || $orders->count() !== count($data['counts'])
                || $orders->contains(fn ($order) => ! array_key_exists($order->id, $data['counts']))) {
                throw ValidationException::withMessages(['counts' => 'Danh sách dòng đã thay đổi. Vui lòng tải lại và kiểm tra.']);
            }

            $total = 0;
            $items = [];
            foreach ($orders as $order) {
                $product = $order->product();
                $problem = ManualLabelOrders::printProblem($order, $product);
                if ($problem) {
                    throw ValidationException::withMessages(['counts' => "Mã {$order->label_code}: {$problem}"]);
                }
                try {
                    $calculation = ManualLabelOrders::calculate($order->quantity, $order->pack_spec);
                } catch (ValidationException $e) {
                    throw ValidationException::withMessages(['counts' => "Mã {$order->label_code}: " . $e->getMessage()]);
                }
                $count = (int) $data['counts'][$order->id];
                $remaining = max(0, $calculation['count'] - ManualLabelOrders::printed($order));
                if ($count > $remaining && empty($data['allow_extra'])) {
                    throw ValidationException::withMessages(['counts' => "Mã {$order->label_code}: số in vượt {$remaining} nhãn còn thiếu. Hãy xác nhận in vượt."]);
                }
                $items[] = ['order' => $order, 'product' => $product, 'count' => $count];
                $total += $count;
            }
            if ($total === 0 || $total > 1000) {
                throw ValidationException::withMessages(['counts' => 'Chọn từ 1 đến 1.000 nhãn mỗi lần in.']);
            }
            $remove = $data['include_partial'] === '0' ? $total % 4 : 0;
            if ($remove === $total) {
                throw ValidationException::withMessages(['counts' => 'Không có tờ đủ 4 nhãn. Hãy chọn in tờ lẻ.']);
            }
            for ($i = count($items) - 1; $i >= 0 && $remove > 0; $i--) {
                $cut = min($remove, $items[$i]['count']);
                $items[$i]['count'] -= $cut;
                $remove -= $cut;
            }

            $labels = [];
            foreach ($items as $item) {
                if (! $item['count']) {
                    continue;
                }
                $form = $item['order']->toFormArray($item['product']);
                unset($form['id']);
                $form['copies_count'] = $item['count'];
                $form['printed_by'] = auth()->id();
                $print = ManualLabelPrint::create($form);
                foreach (range(1, $item['count']) as $_) {
                    $labels[] = $print->toLabelArray();
                }
            }

            return $labels;
        });

        return response()->view('labels.print', [
            'printTitle' => 'In nhãn theo lệnh ' . $data['production_order'],
            'labels' => $labels, 'watermark' => false, 'autoPrint' => true, 'columnFirst' => true,
        ]);
    }
}
