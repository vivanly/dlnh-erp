<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelDelivery;
use App\Models\ManualLabelPrint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualLabelController extends Controller
{
    public const LABEL_TYPES = [
        'vt' => 'Vị thuốc cổ truyền',
        'dl_n' => 'Dược liệu trong nước',
        'dl_b' => 'Dược liệu nhập khẩu',
    ];

    private function authorizePrinting(): void
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
    }

    public function create()
    {
        $this->authorizePrinting();

        return view('manual-labels.create', [
            'labelTypes' => self::LABEL_TYPES,
            'hasSources' => \App\Models\ManualLabelProductionOrder::exists(),
            'prefill' => $this->prefillFromProductionOrder(),
        ]);
    }

    private function prefillFromProductionOrder(): array
    {
        if (! request()->filled('order')) {
            return [];
        }

        return \App\Models\ManualLabelProductionOrder::findOrFail(request()->query('order'))->toFormArray();
    }
    public function store(Request $request)
    {
        $this->authorizePrinting();
        $data = $request->validate([
            'label_type' => ['required', 'in:' . implode(',', array_keys(self::LABEL_TYPES))],
            'traditional_medicine_name' => ['nullable', 'boolean'],
            'product_name' => ['required', 'string', 'max:255'],
            'batch' => ['required', 'string', 'max:100'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'mfg_date' => ['nullable', 'string', 'max:32'],
            'part_used' => ['nullable', 'string', 'max:255'],
            'exp_date' => ['nullable', 'string', 'max:32'],
            'origin' => ['nullable', 'string', 'max:255'],
            'net_weight' => ['nullable', 'string', 'max:64'],
            'registration' => ['nullable', 'string', 'max:255'],
            'standard' => ['nullable', 'string', 'max:255'],
            'storage' => ['nullable', 'string', 'max:255'],
            'qr_content' => ['nullable', 'string', 'max:1000'],
            'watermark' => ['nullable', 'boolean'],
            'copies_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:255'],
            'order_code' => ['nullable', 'string', 'max:32'],
            'label_code' => ['nullable', 'string', 'max:32'],
        ]);

        $data['traditional_medicine_name'] = $data['label_type'] === 'vt' && $request->boolean('traditional_medicine_name');
        $data['watermark'] = $request->boolean('watermark');
        $data['printed_by'] = auth()->id();

        $print = ManualLabelPrint::create($data);

        return $this->renderPrint($print, true);
    }

    public function reprint(ManualLabelPrint $manualLabel)
    {
        $this->authorizePrinting();

        return $this->renderPrint($manualLabel, true);
    }

    public function history(Request $request)
    {
        $this->authorizePrinting();
        $q = trim((string) $request->query('q'));
        $status = (string) $request->query('status');
        $like = '%' . addcslashes($q, '%_\\') . '%';

        $prints = ManualLabelPrint::with(['printedBy', 'deliveries.deliveredBy'])
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('product_name', 'like', $like)->orWhere('batch', 'like', $like)
                ->orWhere('order_code', 'like', $like)->orWhere('label_code', 'like', $like)->orWhere('customer', 'like', $like)))
            ->when($status === 'stock', fn ($w) => $w->whereRaw('copies_count - delivered_count + returned_count > 0'))
            ->when($status === 'empty', fn ($w) => $w->whereRaw('copies_count - delivered_count + returned_count <= 0'))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $printed = (int) ManualLabelPrint::sum('copies_count');
        $delivered = (int) ManualLabelPrint::sum('delivered_count');
        $returned = (int) ManualLabelPrint::sum('returned_count');

        return view('manual-labels.history', [
            'prints' => $prints,
            'labelTypes' => self::LABEL_TYPES,
            'q' => $q,
            'status' => $status,
            'totals' => ['printed' => $printed, 'delivered' => $delivered, 'returned' => $returned, 'stock' => max(0, $printed - $delivered + $returned)],
        ]);
    }

    public function deliver(Request $request, ManualLabelPrint $manualLabel)
    {
        $this->authorizePrinting();
        $data = $request->validate([
            'kind' => ['nullable', 'in:out,return'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'customer' => ['nullable', 'string', 'max:255'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $kind = $data['kind'] ?? 'out';

        DB::transaction(function () use ($manualLabel, $data, $kind) {
            $locked = ManualLabelPrint::whereKey($manualLabel->id)->lockForUpdate()->firstOrFail();
            $limit = $kind === 'out' ? $locked->remaining_count : $locked->returnable_count;
            if ($data['quantity'] > $limit) {
                throw ValidationException::withMessages([
                    'quantity' => $kind === 'out'
                        ? "Số lượng xuất vượt số nhãn tồn ({$limit})."
                        : "Số lượng trả vượt số nhãn đã xuất chưa trả ({$limit}).",
                ]);
            }
            ManualLabelDelivery::create([
                'manual_label_print_id' => $locked->id,
                'quantity' => $data['quantity'],
                'kind' => $kind,
                'received_by' => $data['received_by'] ?? null,
                'note' => $data['note'] ?? null,
                'delivered_by' => auth()->id(),
                'delivered_at' => now(),
            ]);
            $locked->increment($kind === 'out' ? 'delivered_count' : 'returned_count', $data['quantity']);
            if (! empty($data['customer'])) {
                $locked->update(['customer' => $data['customer']]);
            }
        });

        return redirect()->route('manual-labels.history')->with('success', $kind === 'out' ? 'Đã ghi nhận xuất nhãn.' : 'Đã ghi nhận nhãn trả.');
    }
    public function destroy(ManualLabelPrint $manualLabel)
    {
        $this->authorizePrinting();
        $manualLabel->delete();

        return redirect()->route('manual-labels.history')->with('success', 'Đã xóa lịch sử in nhãn.');
    }

    private function renderPrint(ManualLabelPrint $print, bool $autoPrint)
    {
        return response()->view('labels.print', [
            'printTitle' => 'In nhãn tay #' . $print->id,
            'labels' => array_fill(0, $print->copies_count, $print->toLabelArray()),
            'watermark' => $print->watermark,
            'autoPrint' => $autoPrint,
        ]);
    }
}
