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
            'hasSources' => \App\Models\ManualLabelSource::exists(),
            'prefill' => $this->prefillFromOrderLine(),
        ]);
    }

    private function prefillFromOrderLine(): array
    {
        if (! request()->filled('line')) {
            return [];
        }

        $line = \App\Models\ManualLabelOrderLine::findOrFail(request()->query('line'));
        $info = \App\Models\ManualLabelCatalog::where('name_key', \App\Models\ManualLabelCatalog::key($line->name))->first();

        return [
            'label_type' => $line->labelType(),
            'product_name' => $line->name,
            'scientific_name' => $info?->scientific_name,
            'part_used' => $info?->part_used,
            'batch' => $line->batch,
            'mfg_date' => $line->mfg_date?->format('d/m/Y'),
            'exp_date' => $line->exp_date?->format('d/m/Y'),
            'origin' => $info?->origin ?: match (strtoupper(trim((string) $line->origin_code))) {
                'B' => 'Trung Quốc',
                'N' => 'Việt Nam',
                default => (string) $line->origin_code,
            },
            'net_weight' => $line->netWeight() ?: null,
            'registration' => \App\Models\ManualLabelSource::where('batch', $line->batch)->whereNotNull('registration')->value('registration'),
            'copies_count' => max(1, (int) $line->pack_count),
            'order_code' => $line->order_code,
        ];
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

    public function history()
    {
        $this->authorizePrinting();
        $prints = ManualLabelPrint::with(['printedBy', 'deliveries.deliveredBy'])
            ->latest()
            ->paginate($this->perPage(request()))
            ->withQueryString();

        $totals = [
            'printed' => (int) ManualLabelPrint::sum('copies_count'),
            'delivered' => (int) ManualLabelPrint::sum('delivered_count'),
        ];

        return view('manual-labels.history', [
            'prints' => $prints,
            'labelTypes' => self::LABEL_TYPES,
            'totals' => $totals,
        ]);
    }

    public function deliver(Request $request, ManualLabelPrint $manualLabel)
    {
        $this->authorizePrinting();
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'received_by' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($manualLabel, $data) {
            $locked = ManualLabelPrint::whereKey($manualLabel->id)->lockForUpdate()->firstOrFail();
            if ($data['quantity'] > $locked->remaining_count) {
                throw ValidationException::withMessages([
                    'quantity' => "Số lượng giao vượt số nhãn còn lại ({$locked->remaining_count}).",
                ]);
            }
            ManualLabelDelivery::create([
                'manual_label_print_id' => $locked->id,
                'quantity' => $data['quantity'],
                'received_by' => $data['received_by'] ?? null,
                'note' => $data['note'] ?? null,
                'delivered_by' => auth()->id(),
                'delivered_at' => now(),
            ]);
            $locked->increment('delivered_count', $data['quantity']);
        });

        return redirect()->route('manual-labels.history')->with('success', 'Đã ghi nhận giao nhãn.');
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
