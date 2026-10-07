<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductionFinishedBatch;
use App\Models\SalesOrderLabelPrint;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Models\TraceabilityLotCode;
use App\Models\TraceabilitySetting;
use App\Services\SalesOrderLabelWorkbook;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LabelController extends Controller
{
    private const LABEL_TYPES = [
        'vt' => 'Vị thuốc cổ truyền',
        'dl_n' => 'Dược liệu trong nước',
        'dl_b' => 'Dược liệu Trung Quốc',
    ];

    private function ensureProductionAccess(): void
    {
        $user = auth()->user();
        $allowed = $user && (
            (method_exists($user, 'isProductionDepartment') && $user->isProductionDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );

        abort_unless($allowed, 403);
    }

    private function userCanPrintLabels(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isProductionDepartment') && $user->isProductionDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    public function index()
    {
        abort_unless(auth()->check(), 403);

        $orders = Order::with(['customer', 'items.product', 'items.labelPrints', 'items.lotAllocations'])
            ->whereIn('status', ['pending_planning', 'waiting_production', 'processing', 'ready_to_ship', 'completed'])
            ->whereNotNull('qa_confirmed_at')
            ->whereHas('items.lotAllocations', fn ($query) => $query->whereIn('status', ['reserved', 'shipped']))
            ->latest()
            ->paginate($this->perPage(request()))
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order) {
            $itemsWithLots = $order->items->filter(fn (OrderItem $item) => $this->itemHasCompleteLotAllocation($item));
            $order->setAttribute('label_target_total', $itemsWithLots->sum(fn (OrderItem $item) => $item->label_target_count));
            $order->setAttribute('label_printed_total', $itemsWithLots->sum(fn (OrderItem $item) => $item->labelPrints->sum('copies_count')));
            $order->setAttribute('label_item_count', $itemsWithLots->count());
            $order->setAttribute('label_waiting_count', $order->items->filter(fn (OrderItem $item) => $item->lotAllocations->isNotEmpty())->count() - $itemsWithLots->count());
        });

        return view('labels.index', compact('orders'));
    }

    public function showOrder(Order $order)
    {
        abort_unless(auth()->check(), 403);
        $this->ensureLabelOrder($order);
        $order->load([
            'customer',
            'items.product',
            'items.labelPrints',
            'items.labelPrints.printedBy',
            'items.labelPrints.allocation.supplierBatch',
            'items.labelPrints.allocation.finishedBatch',
            'items.lotAllocations.labelPrints',
            'items.lotAllocations.supplierBatch',
            'items.lotAllocations.finishedBatch',
            'items.lotAllocations.supplierBatch.traceabilityCode',
            'items.lotAllocations.finishedBatch.traceabilityCode',
        ]);

        $rows = [];
        foreach ($order->items as $item) {
            $allocations = $item->lotAllocations
                ->filter(fn (SalesOrderLotAllocation $allocation) => in_array($allocation->status, ['reserved', 'shipped'], true) && $this->allocationBatch($allocation))
                ->values();
            if ($allocations->isEmpty()) {
                continue;
            }

            $targets = $this->distributeLabelTarget($item, $allocations);
            foreach ($allocations as $allocation) {
                $batch = $this->allocationBatch($allocation);
                $printed = (int) $allocation->labelPrints->sum('copies_count');
                $labelType = $this->labelType($item);
                $rows[] = [
                    'item' => $item,
                    'allocation' => $allocation,
                    'batch_code' => $this->batchCode($allocation, $batch),
                    'label_type_name' => self::LABEL_TYPES[$labelType],
                    'qa_approved' => $this->batchIsQaApproved($allocation, $batch),
                    'target' => $targets[$allocation->id] ?? 0,
                    'printed' => $printed,
                    'remaining' => max(0, ($targets[$allocation->id] ?? 0) - $printed),
                ];
            }
        }

        return view('labels.order', [
            'order' => $order,
            'rows' => collect($rows),
            'targetTotal' => collect($rows)->sum('target'),
            'printedTotal' => collect($rows)->sum('printed'),
            'history' => $order->items->flatMap(fn (OrderItem $item) => $item->labelPrints)->sortByDesc('created_at')->values(),
            'canPrintLabels' => $this->userCanPrintLabels(),
        ]);
    }

    public function printOrder(Request $request, Order $order, SalesOrderLabelWorkbook $workbookBuilder)
    {
        return $this->generateOrderLabels($request, $order, $workbookBuilder);
    }

    public function printOrderDirect(Request $request, Order $order, SalesOrderLabelWorkbook $workbookBuilder)
    {
        return $this->generateOrderLabels($request, $order, $workbookBuilder, true);
    }

    private function generateOrderLabels(Request $request, Order $order, SalesOrderLabelWorkbook $workbookBuilder, bool $html = false)
    {
        $this->ensureProductionAccess();
        $this->ensureLabelOrder($order);

        $validated = $request->validate([
            'additional' => 'nullable|array',
            'additional.*' => 'nullable|integer|min:0|max:1000',
            'additional_reason' => 'nullable|string|max:255',
            'copies' => 'nullable|array',
            'copies.*' => 'nullable|integer|min:0|max:1000',
            'standard' => 'nullable|array',
            'standard.*' => 'nullable|string|max:255',
            'storage' => 'nullable|array',
            'storage.*' => 'nullable|string|max:255',
            'preview' => 'nullable|boolean',
        ]);
        $preview = $html && (bool) ($validated['preview'] ?? false);

        $downloadPath = null;
        $printLabels = [];
        try {
            DB::transaction(function () use ($order, $validated, $workbookBuilder, $html, $preview, &$downloadPath, &$printLabels) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $this->ensureLabelOrder($lockedOrder);
                $items = OrderItem::with(['product.regulatoryDocuments', 'labelPrints', 'lotAllocations'])
                    ->where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->get();
                $allocations = SalesOrderLotAllocation::with([
                    'supplierBatch.traceabilityCode', 'finishedBatch.traceabilityCode', 'labelPrints',
                ])->whereIn('order_item_id', $items->modelKeys())
                    ->whereIn('status', ['reserved', 'shipped'])
                    ->lockForUpdate()
                    ->get()
                    ->groupBy('order_item_id');
                $submitted = collect($validated['additional'] ?? []);
                $validIds = $allocations->flatten()->pluck('id')->map(fn ($id) => (string) $id)->all();
                $copiesInput = collect($validated['copies'] ?? []);
                if (array_diff($copiesInput->keys()->merge($submitted->keys())->map(fn ($id) => (string) $id)->unique()->all(), $validIds)) {
                    throw new DomainException('Danh sách phân bổ lô in thêm không thuộc đơn hàng này.');
                }

                $reason = trim((string) ($validated['additional_reason'] ?? ''));
                $baseUrl = TraceabilitySetting::query()->firstOrCreate(
                    [],
                    ['base_url' => 'http://localhost/code=']
                )->base_url;
                $labels = [];
                foreach ($items as $item) {
                    $itemAllocations = $allocations->get($item->id, collect())
                        ->filter(fn (SalesOrderLotAllocation $allocation) => $this->allocationBatch($allocation))
                        ->values();
                    if ($itemAllocations->isEmpty()) {
                        continue;
                    }

                    $targets = $this->distributeLabelTarget($item, $itemAllocations);
                    foreach ($itemAllocations as $allocation) {
                        $batch = $this->allocationBatch($allocation);
                        $target = $targets[$allocation->id] ?? 0;
                        $printed = (int) $allocation->labelPrints->sum('copies_count');
                        $requiredCopies = max(0, $target - $printed);
                        $additionalCopies = (int) ($submitted->get((string) $allocation->id) ?? 0);
                        if ($copiesInput->get((string) $allocation->id) !== null) {
                            $requested = (int) $copiesInput->get((string) $allocation->id);
                            if ($target <= 0) {
                                $requiredCopies = $requested;
                                $additionalCopies = 0;
                            } else {
                                $additionalCopies = max(0, $requested - $requiredCopies);
                                $requiredCopies = min($requested, $requiredCopies);
                            }
                        }

                        if ($additionalCopies > 0 && $reason === '') {
                            throw new DomainException('Cần ghi lý do khi yêu cầu in bổ sung nhãn.');
                        }
                        if ($requiredCopies === 0 && $additionalCopies === 0) {
                            continue;
                        }
                        if (!$this->batchIsQaApproved($allocation, $batch) && $printed === 0) {
                            throw new DomainException("Lô {$this->batchCode($allocation, $batch)} chưa được QA cho phép để in nhãn.");
                        }
                        if ($requiredCopies > 0 && (!$this->batchIsQaApproved($allocation, $batch) || ($batch->exp_date && $batch->exp_date < today()))) {
                            throw new DomainException("Lô {$this->batchCode($allocation, $batch)} không còn đủ điều kiện QA/HSD để in nhãn chuẩn.");
                        }

                        $registrationDocument = $item->product->regulatoryDocuments
                            ->whereIn('document_type', ['cong_bo', 'dang_ky'])
                            ->sortByDesc('document_date')
                            ->first()
                            ?? $item->product->regulatoryDocuments
                                ->where('document_type', 'gpnk')
                                ->sortByDesc('document_date')
                                ->first();
                        $traceabilityCode = $allocation->supplierBatch?->traceabilityCode
                            ?? $allocation->finishedBatch?->traceabilityCode;
                        if (!$traceabilityCode && $allocation->supplierBatch) {
                            $traceabilityCode = TraceabilityLotCode::query()->firstOrCreate(
                                ['supplier_batch_id' => $allocation->supplierBatch->id],
                                ['trace_code' => 'NCC-' . $allocation->supplierBatch->id]
                            );
                        } elseif (!$traceabilityCode && $allocation->finishedBatch) {
                            $traceabilityCode = TraceabilityLotCode::query()->firstOrCreate(
                                ['production_finished_batch_id' => $allocation->finishedBatch->id],
                                ['trace_code' => 'TP-' . $allocation->finishedBatch->id]
                            );
                        }

                        $label = [
                            'label_type' => $this->labelType($item),
                            'product' => $item->product->name,
                            'batch' => $this->batchCode($allocation, $batch),
                            'scientific' => $item->product->scientific_name,
                            'mfg' => $this->formatDate($batch->mfg_date),
                            'part' => $item->product->part_used,
                            'exp' => $this->formatDate($batch->exp_date),
                            'origin' => $item->product->origin,
                            'weight' => $this->netWeight($item),
                            'registration' => $registrationDocument?->document_number ?? '',
                            'standard' => trim((string) ($validated['standard'][$allocation->id] ?? '')),
                            'storage' => trim((string) ($validated['storage'][$allocation->id] ?? '')),
                            'qr_url' => $baseUrl && $traceabilityCode?->trace_code
                                ? $baseUrl . $traceabilityCode->trace_code
                                : null,
                        ];

                        if ($requiredCopies > 0) {
                            if (!$preview) {
                                SalesOrderLabelPrint::create([
                                    'order_item_id' => $item->id,
                                    'sales_order_lot_allocation_id' => $allocation->id,
                                    'copies_count' => $requiredCopies,
                                    'print_kind' => 'required',
                                    'printed_by' => auth()->id(),
                                ]);
                            }
                            $labels = [...$labels, ...array_fill(0, $requiredCopies, $label)];
                        }
                        if ($additionalCopies > 0) {
                            if (!$preview) {
                                SalesOrderLabelPrint::create([
                                    'order_item_id' => $item->id,
                                    'sales_order_lot_allocation_id' => $allocation->id,
                                    'copies_count' => $additionalCopies,
                                    'print_kind' => 'additional',
                                    'reason' => $reason,
                                    'printed_by' => auth()->id(),
                                ]);
                            }
                            $labels = [...$labels, ...array_fill(0, $additionalCopies, $label)];
                        }
                        if (!$preview) {
                            $allocation->update(['label_printed_at' => now(), 'label_printed_by' => auth()->id()]);
                        }
                    }
                }

                if ($labels === []) {
                    throw new DomainException('Đơn này đã in đủ nhãn; nhập số lượng in bổ sung và lý do nếu cần in thêm.');
                }
                if ($html) {
                    $printLabels = $labels;
                } else {
                    $downloadPath = $workbookBuilder->create($labels);
                }
            });
        } catch (DomainException $exception) {
            if ($downloadPath && is_file($downloadPath)) {
                @unlink($downloadPath);
            }

            if ($html) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            if ($downloadPath && is_file($downloadPath)) {
                @unlink($downloadPath);
            }
            report($exception);

            if ($html) {
                return response()->json(['message' => 'Không tạo được nhãn để in. Vui lòng thử lại.'], 500);
            }

            return back()->withInput()->with('error', 'Không tạo được file nhãn Excel. Vui lòng kiểm tra mẫu Nhan.xlsx.');
        }

        if ($html) {
            return response()->view('labels.print', ['order' => $order, 'labels' => $printLabels]);
        }

        $safeOrderCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', $order->order_code);

        return response()->download($downloadPath, 'Nhan-' . $safeOrderCode . '.xlsx')->deleteFileAfterSend(true);
    }

    private function ensureLabelOrder(Order $order): void
    {
        abort_unless(in_array($order->status, ['pending_planning', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) && $order->qa_confirmed_at, 404);
    }

    private function itemHasCompleteLotAllocation(OrderItem $item): bool
    {
        $allocations = $item->lotAllocations->whereIn('status', ['reserved', 'shipped']);

        return $allocations->isNotEmpty()
            && $allocations->sum(fn ($allocation) => (float) $allocation->reserved_quantity) + 0.0001 >= (float) $item->quantity;
    }

    private function allocationBatch(SalesOrderLotAllocation $allocation): SupplierBatch|ProductionFinishedBatch|null
    {
        return $allocation->supplierBatch ?? $allocation->finishedBatch;
    }

    private function batchCode(SalesOrderLotAllocation $allocation, SupplierBatch|ProductionFinishedBatch $batch): string
    {
        return $batch->batch_number;
    }

    private function batchIsQaApproved(SalesOrderLotAllocation $allocation, SupplierBatch|ProductionFinishedBatch $batch): bool
    {
        return $batch->status === 'active'
            || ($batch instanceof ProductionFinishedBatch && $batch->status === 'pending_qa');
    }

    private function distributeLabelTarget(OrderItem $item, Collection $allocations): array
    {
        $target = $item->label_target_count;
        $totalQuantity = $allocations->sum(fn ($allocation) => max(0, (float) $allocation->reserved_quantity));
        if ($target <= 0 || $totalQuantity <= 0) {
            return $allocations->mapWithKeys(fn ($allocation) => [$allocation->id => 0])->all();
        }

        $shares = $allocations->map(function ($allocation) use ($target, $totalQuantity) {
            $exact = $target * max(0, (float) $allocation->reserved_quantity) / $totalQuantity;

            return ['id' => $allocation->id, 'count' => (int) floor($exact), 'remainder' => $exact - floor($exact)];
        });
        $remaining = $target - $shares->sum('count');
        foreach ($shares->sortByDesc('remainder')->take($remaining) as $share) {
            $shares = $shares->map(fn ($candidate) => $candidate['id'] === $share['id']
                ? [...$candidate, 'count' => $candidate['count'] + 1]
                : $candidate);
        }

        return $shares->mapWithKeys(fn ($share) => [$share['id'] => $share['count']])->all();
    }

    private function labelType(OrderItem $item): string
    {
        $classification = Str::upper(Str::ascii(trim((string) $item->product->classification)));
        if (str_contains($classification, 'VT') || str_contains($classification, 'VI THUOC')) {
            return 'vt';
        }

        $origin = Str::lower(Str::ascii((string) $item->product->origin));
        if (str_contains($origin, 'trung quoc') || str_contains($origin, 'china')) {
            return 'dl_b';
        }

        return 'dl_n';
    }

    private function netWeight(OrderItem $item): string
    {
        $packaging = trim((string) $item->packaging_spec);
        if ($packaging === '') {
            return (string) ($item->product->unit ?? '');
        }

        return preg_match('/[a-z\x{00C0}-\x{024F}]/iu', $packaging)
            ? $packaging
            : $packaging . ($item->product->unit ? ' ' . $item->product->unit : '');
    }

    private function formatDate($date): string
    {
        return $date ? date('d/m/Y', strtotime((string) $date)) : '';
    }
}