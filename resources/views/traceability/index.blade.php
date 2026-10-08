<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Truy vết lô</h2>
                <p class="mt-1 text-xs text-slate-500">Theo dõi chuỗi: nguyên liệu → lệnh SX → thành phẩm → đơn bán / giao hàng</p>
            </div>
        </div>
    </x-slot>

    <div class="py-3 px-2">
        <div class="max-w-none w-full space-y-3">
            <form method="GET" action="{{ route('traceability.index') }}" class="bg-white border border-slate-300 p-3 flex flex-col sm:flex-row gap-2">
                <input type="search" name="search" value="{{ old('search', $search) }}" placeholder="Nhập số lô NCC, lô nội bộ hoặc lô thành phẩm..." class="w-full text-xs border-slate-300 rounded-none py-1.5" />
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold uppercase hover:bg-slate-700">Tra cứu</button>
            </form>

            @if($search === '')
                <div class="bg-white border border-slate-300 p-6 text-sm text-slate-600 text-center">
                    Chưa có số lô nào để truy vết. Nhập số lô ở thanh trên để xem chuỗi sản xuất và giao hàng.
                </div>
            @elseif(!$traceBatch)
                <div class="bg-rose-50 border border-rose-200 p-4 text-sm text-rose-700">
                    Không tìm thấy số lô này trong hệ thống.
                </div>
            @else
                @php
                    $lotCode = $traceBatch->batch_number;
                    $lotTypeLabel = match($traceabilityType) {
                        'supplier' => 'Lô nhà cung cấp',
                        default => str_contains(strtoupper((string) $traceBatch->product->classification), 'VT')
                            ? 'Lô nội bộ · vị thuốc'
                            : 'Lô nội bộ · dược liệu sơ chế',
                    };
                    $lotUnit = $traceabilityType === 'finished'
                        ? $traceBatch->unit
                        : ($traceBatch->product->unit ?? '');
                @endphp
                <div class="space-y-3">
                    <section class="bg-white border border-slate-300 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <span class="inline-block px-2 py-1 bg-blue-50 border border-blue-200 text-[10px] font-bold uppercase text-blue-700">{{ $lotTypeLabel }}</span>
                                <h3 class="mt-2 text-base font-bold font-mono text-slate-900">{{ $lotCode }}</h3>
                                <p class="mt-1 text-xs text-slate-600">{{ $traceBatch->product->name ?? '---' }} · {{ $lotUnit }}</p>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-xs">
                                <div><dt class="text-slate-500">Tồn hiện tại</dt><dd class="font-mono font-bold">{{ number_format((float) $traceBatch->current_quantity, 4) }} {{ $lotUnit }}</dd></div>
                                <div><dt class="text-slate-500">Trạng thái</dt><dd class="font-semibold">{{ $traceBatch->status }}</dd></div>
                                <div><dt class="text-slate-500">Ngày sản xuất</dt><dd>{{ $traceBatch->mfg_date ? date('d/m/Y', strtotime($traceBatch->mfg_date)) : '---' }}</dd></div>
                                <div><dt class="text-slate-500">Hạn sử dụng</dt><dd>{{ $traceBatch->exp_date ? date('d/m/Y', strtotime($traceBatch->exp_date)) : '---' }}</dd></div>
                            </dl>
                        </div>
                    </section>

                    @if($traceabilityType === 'finished' && ($traceBatch->provisional_batch_number !== $traceBatch->batch_number || $traceBatch->codeHistories->isNotEmpty()))
                        <section class="bg-white border border-slate-300 p-4">
                            <h3 class="text-xs font-bold uppercase text-slate-700">Lịch sử mã lô</h3>
                            <p class="mt-2 text-xs">Mã dự trù: <span class="font-mono font-semibold">{{ $traceBatch->provisional_batch_number ?: '---' }}</span> · Mã hiện tại: <span class="font-mono font-semibold">{{ $traceBatch->batch_number }}</span></p>
                            @if($traceBatch->codeHistories->isNotEmpty())
                                <div class="mt-2 overflow-x-auto"><table class="w-full text-left text-xs">
                                    <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500"><th class="py-2 pr-3">Mã cũ</th><th class="py-2 pr-3">Mã mới</th><th class="py-2">Thời điểm / QA</th></tr></thead>
                                    <tbody class="divide-y divide-slate-100">@foreach($traceBatch->codeHistories as $history)<tr><td class="py-2 pr-3 font-mono">{{ $history->old_batch_number }}</td><td class="py-2 pr-3 font-mono">{{ $history->new_batch_number }}</td><td class="py-2">{{ $history->created_at?->format('d/m/Y H:i') }} · {{ $history->changedBy->name ?? '---' }}</td></tr>@endforeach</tbody>
                                </table></div>
                            @endif
                        </section>
                    @endif

                    @if($traceabilityType === 'finished' && $traceBatch->qc_test_report)
                        <section class="bg-white border border-slate-300 p-4">
                            <h3 class="text-xs font-bold uppercase text-slate-700">Phiếu kiểm nghiệm QC</h3>
                            <p class="mt-2 text-xs">Số phiếu: <strong class="font-mono">{{ $traceBatch->qc_test_report }}</strong> · Ngày: {{ optional($traceBatch->qc_date)->format('d/m/Y') ?? '---' }}</p>
                            @if($traceBatch->qc_test_report_file)
                                <a href="{{ route('private-documents.production-qc-report', $traceBatch) }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 border border-slate-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50" aria-label="Xem tệp phiếu kiểm nghiệm {{ $traceBatch->qc_test_report }}">
                                    Xem / tải tệp PKN
                                </a>
                            @endif
                        </section>
                    @endif

                    @if($productionOrders->isNotEmpty())
                        <section class="bg-white border border-slate-300">
                            <div class="p-3 border-b border-slate-200"><h3 class="text-xs font-bold uppercase text-slate-700">Lệnh sản xuất sử dụng lô</h3></div>
                            <div class="overflow-x-auto"><table class="w-full text-left text-xs">
                                <thead><tr class="bg-slate-50 text-[10px] uppercase text-slate-600"><th class="p-2.5">Mã lệnh / Đơn bán</th><th class="p-2.5">Sản phẩm</th><th class="p-2.5 text-right">Kế hoạch / Thực tế</th><th class="p-2.5">Trạng thái</th></tr></thead>
                                <tbody class="divide-y divide-slate-200">@foreach($productionOrders as $productionOrder)<tr><td class="p-2.5 font-mono font-semibold">{{ $productionOrder->production_code }}<span class="block text-[10px] font-sans text-slate-500">{{ $productionOrder->order->order_code ?? '---' }}</span></td><td class="p-2.5">{{ $productionOrder->product->name ?? '---' }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $productionOrder->planned_quantity, 4) }} / {{ $productionOrder->actual_quantity !== null ? number_format((float) $productionOrder->actual_quantity, 4) : '---' }} {{ $productionOrder->unit }}</td><td class="p-2.5">{{ $productionOrder->status }}</td></tr>@endforeach</tbody>
                            </table></div>
                        </section>
                    @endif

                    @if($materialInputs->isNotEmpty())
                        <section class="bg-white border border-slate-300">
                            <div class="p-3 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2"><h3 class="text-xs font-bold uppercase text-slate-700">Lô nguyên liệu NCC cấu thành lô thành phẩm</h3><span class="text-xs text-slate-600">Tổng tiêu hao: <strong class="font-mono">{{ number_format((float) $materialInputs->sum('consumed_quantity'), 4) }}</strong></span></div>
                            <div class="overflow-x-auto"><table class="w-full text-left text-xs">
                                <thead><tr class="bg-slate-50 text-[10px] uppercase text-slate-600"><th class="p-2.5">Lô NCC</th><th class="p-2.5">Phiếu nhập / PO</th><th class="p-2.5">Nhà cung cấp</th><th class="p-2.5">Nguyên liệu</th><th class="p-2.5 text-right">Lượng đã tiêu hao</th></tr></thead>
                                <tbody class="divide-y divide-slate-200">@foreach($materialInputs as $input)
                                    @php
                                        $supplierBatch = $input->materialLot?->supplierBatch;
                                        $receiptItem = $supplierBatch?->goodsReceiptItem;
                                        $receipt = $receiptItem?->goodsReceipt;
                                        $purchaseOrder = $receipt?->purchaseOrder ?? $receiptItem?->purchaseOrderItem?->purchaseOrder;
                                    @endphp
                                    <tr>
                                        <td class="p-2.5 font-mono font-semibold">{{ $supplierBatch->batch_number ?? $input->materialLot?->batch_number ?? '---' }}</td>
                                        <td class="p-2.5 font-mono">{{ $receipt?->receipt_code ?? '---' }}<span class="block text-[10px] font-sans text-slate-500">{{ $purchaseOrder?->po_number ?? 'Chưa có PO' }}</span></td>
                                        <td class="p-2.5">{{ $purchaseOrder?->supplier?->name ?? '---' }}</td>
                                        <td class="p-2.5">{{ $input->materialLot?->material?->display_name ?? $supplierBatch?->product?->name ?? '---' }}</td>
                                        <td class="p-2.5 text-right font-mono">{{ number_format((float) $input->consumed_quantity, 4) }} {{ $input->unit }}</td>
                                    </tr>
                                @endforeach</tbody>
                            </table></div>
                        </section>
                    @endif

                    @if($finishedBatches->isNotEmpty())
                        <section class="bg-white border border-slate-300">
                            <div class="p-3 border-b border-slate-200"><h3 class="text-xs font-bold uppercase text-slate-700">Lô thành phẩm nhập kho</h3></div>
                            <div class="overflow-x-auto"><table class="w-full text-left text-xs">
                                <thead><tr class="bg-slate-50 text-[10px] uppercase text-slate-600"><th class="p-2.5">Mã lô TP</th><th class="p-2.5">Sản phẩm</th><th class="p-2.5 text-right">Ban đầu / Tồn</th><th class="p-2.5">Hạn sử dụng</th></tr></thead>
                                <tbody class="divide-y divide-slate-200">@foreach($finishedBatches as $batch)<tr><td class="p-2.5 font-mono font-semibold">{{ $batch->batch_number }}</td><td class="p-2.5">{{ $batch->product->name ?? '---' }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $batch->initial_quantity, 4) }} / {{ number_format((float) $batch->current_quantity, 4) }} {{ $batch->unit }}</td><td class="p-2.5">{{ $batch->exp_date ? date('d/m/Y', strtotime($batch->exp_date)) : '---' }}</td></tr>@endforeach</tbody>
                            </table></div>
                        </section>
                    @endif

                    @if($salesAllocations->isNotEmpty())
                        <section class="bg-white border border-slate-300">
                            <div class="p-3 border-b border-slate-200"><h3 class="text-xs font-bold uppercase text-slate-700">Đơn bán và các đợt giao</h3></div>
                            <div class="overflow-x-auto"><table class="w-full text-left text-xs">
                                <thead><tr class="bg-slate-50 text-[10px] uppercase text-slate-600"><th class="p-2.5">Đơn / Khách hàng</th><th class="p-2.5">Sản phẩm</th><th class="p-2.5 text-right">Giữ / Đã giao</th><th class="p-2.5">Trạng thái</th></tr></thead>
                                <tbody class="divide-y divide-slate-200">@foreach($salesAllocations as $allocation)<tr><td class="p-2.5 font-mono font-semibold">{{ $allocation->orderItem->order->order_code ?? '---' }}<span class="block text-[10px] font-sans text-slate-500">{{ $allocation->orderItem->order->customer->name ?? '---' }}</span></td><td class="p-2.5">{{ $allocation->orderItem->product->name ?? '---' }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $allocation->reserved_quantity, 4) }} / {{ number_format((float) $allocation->shipped_quantity, 4) }}</td><td class="p-2.5">{{ $allocation->status === 'replaced' ? 'Đã thay lô' : $allocation->status }}<span class="block text-[10px] text-slate-500">{{ $allocation->updated_at?->format('d/m/Y H:i') }}</span></td></tr>@endforeach</tbody>
                            </table></div>
                        </section>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
