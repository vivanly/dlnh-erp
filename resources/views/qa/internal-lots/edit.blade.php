<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div><h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Sửa thông tin lô nội bộ</h2><p class="mt-1 font-mono text-xs text-slate-500">{{ $productionFinishedBatch->batch_number }}</p></div>
            <a href="{{ route('qa.internal-lots.index') }}" class="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Quay lại quản lý lô</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-4xl px-2">
            @if(session('error'))<div class="mb-3 border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="mb-3 border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <form method="POST" action="{{ route('qa.internal-lots.update', $productionFinishedBatch) }}" class="space-y-4 border border-slate-300 bg-white p-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="text-xs font-semibold text-slate-700">Sản phẩm
                        <select name="product_id" {{ $canEditDefinition ? 'required' : 'disabled' }} class="mt-1 w-full border-slate-300 text-xs">
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ (string) old('product_id', $productionFinishedBatch->product_id) === (string) $product->id ? 'selected' : '' }}>{{ $product->sku ?: '---' }} · {{ $product->name }} · {{ $product->unit }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Số lô
                        <input name="batch_number" value="{{ old('batch_number', $productionFinishedBatch->batch_number) }}" required maxlength="255" class="mt-1 w-full border-slate-300 font-mono text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Nguồn gốc
                        <input name="origin" value="{{ old('origin', $productionFinishedBatch->origin) }}" maxlength="255" class="mt-1 w-full border-slate-300 text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">PPCB
                        <select name="ppcb_id" class="mt-1 w-full border-slate-300 text-xs">
                            <option value="">Không chọn PPCB</option>
                            @foreach($ppcbs as $ppcb)
                                <option value="{{ $ppcb->id }}" {{ (string) old('ppcb_id', $productionFinishedBatch->ppcb_id) === (string) $ppcb->id ? 'selected' : '' }}>{{ $ppcb->ma }} · {{ $ppcb->ten_ppcb }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Số lượng dự kiến ({{ $productionFinishedBatch->unit }})
                        <input type="number" name="planned_quantity" value="{{ old('planned_quantity', $productionFinishedBatch->planned_quantity) }}" min="0.0001" step="0.0001" {{ $canEditDefinition ? 'required' : 'disabled' }} class="mt-1 w-full border-slate-300 text-right font-mono text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Đơn vị
                        <input value="{{ $productionFinishedBatch->unit }}" readonly class="mt-1 w-full border-slate-200 bg-slate-50 text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Ngày sản xuất
                        <input type="date" name="mfg_date" value="{{ old('mfg_date', optional($productionFinishedBatch->mfg_date)->format('Y-m-d')) }}" class="mt-1 w-full border-slate-300 text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Hạn sử dụng
                        <input type="date" name="exp_date" value="{{ old('exp_date', optional($productionFinishedBatch->exp_date)->format('Y-m-d')) }}" class="mt-1 w-full border-slate-300 text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Số giấy phép
                        <input name="license_number" value="{{ old('license_number', $productionFinishedBatch->license_number) }}" maxlength="255" class="mt-1 w-full border-slate-300 text-xs">
                    </label>
                </div>

                <section class="border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 p-3">
                        <h3 class="text-xs font-bold uppercase text-slate-800">Lệnh sản xuất và sản lượng phân bổ</h3>
                        <p class="mt-1 text-[11px] text-slate-500">Điều chỉnh số lượng theo từng lệnh. Để trống hoặc nhập 0 nếu muốn gỡ lệnh khỏi lô. Tổng phân bổ không vượt sức chứa lô.</p>
                    </div>
                    @if($canEditProductionAllocations)
                        @php
                            $existingAllocations = $productionFinishedBatch->orderAllocations->keyBy('production_order_id');
                            $legacyOrderId = $existingAllocations->isEmpty() ? $productionFinishedBatch->production_order_id : null;
                        @endphp
                        <div class="max-h-72 divide-y divide-slate-100 overflow-y-auto px-3">
                            @forelse($availableProductionOrders as $order)
                                @php
                                    $allocation = $existingAllocations->get($order->id);
                                    $isLegacyAllocation = $legacyOrderId && (int) $legacyOrderId === (int) $order->id;
                                    $currentQuantity = $allocation?->quantity ?? ($isLegacyAllocation ? $productionFinishedBatch->initial_quantity : '');
                                    $maxQuantity = (float) $order->pending_finished_quantity + (float) ($currentQuantity ?: 0);
                                @endphp
                                <label data-edit-allocation-row data-product-id="{{ $order->product_id }}" class="grid grid-cols-1 gap-2 py-3 text-xs sm:grid-cols-[minmax(0,1fr)_10rem] sm:items-center">
                                    <span class="min-w-0 text-slate-700"><strong class="font-mono">{{ $order->production_code }}</strong><span class="mx-1 text-slate-300">·</span>{{ $order->product->name }}<span class="mt-1 block text-[11px] text-slate-500">Còn chưa phân bổ: {{ number_format((float) $order->pending_finished_quantity, 4) }} {{ $order->unit }}@if($currentQuantity) · Đang gán vào lô này: {{ number_format((float) $currentQuantity, 4) }} {{ $order->unit }}@endif</span></span>
                                    <span class="flex items-center gap-2"><input type="number" name="production_order_allocations[{{ $order->id }}]" value="{{ old('production_order_allocations.'.$order->id, $currentQuantity) }}" min="0" max="{{ $maxQuantity }}" step="0.0001" placeholder="Bỏ trống để gỡ" aria-label="Số lượng từ lệnh {{ $order->production_code }}" class="w-full border-slate-300 text-right font-mono text-xs"><span class="text-slate-500">{{ $order->unit }}</span></span>
                                </label>
                            @empty
                                <p class="py-4 text-center text-xs text-slate-500">Không có lệnh sản xuất hoàn thành còn sản lượng để phân bổ.</p>
                            @endforelse
                        </div>
                    @else
                        <div class="space-y-2 p-3">
                            @forelse($productionFinishedBatch->orderAllocations as $allocation)
                                <div class="flex flex-wrap items-center justify-between gap-2 border border-slate-200 p-2 text-xs"><span class="font-mono">{{ $allocation->productionOrder?->production_code }}</span><strong>{{ number_format((float) $allocation->quantity, 4) }} {{ $productionFinishedBatch->unit }}</strong></div>
                            @empty
                                @if($productionFinishedBatch->productionOrder)
                                    <div class="flex flex-wrap items-center justify-between gap-2 border border-slate-200 p-2 text-xs"><span class="font-mono">{{ $productionFinishedBatch->productionOrder->production_code }}</span><strong>{{ number_format((float) $productionFinishedBatch->initial_quantity, 4) }} {{ $productionFinishedBatch->unit }}</strong></div>
                                @else
                                    <p class="text-xs text-slate-600">Lô này chưa liên kết với lệnh sản xuất.</p>
                                @endif
                            @endforelse
                            <p class="border-l-2 border-amber-400 bg-amber-50 p-2 text-[11px] text-amber-900">Không thể sửa phân bổ sau khi QA duyệt hoặc Kho đã nhập lô, để bảo toàn lịch sử tồn kho và truy xuất.</p>
                        </div>
                    @endif
                </section>

                <section class="border border-slate-200 bg-slate-50 p-3">
                    <h3 class="text-[10px] font-bold uppercase text-slate-600">PKN do QC quản lý</h3>
                    <p class="mt-2 text-xs">Số phiếu: <strong class="font-mono">{{ $productionFinishedBatch->qc_test_report ?: 'Chưa có' }}</strong> · Ngày ra phiếu: {{ optional($productionFinishedBatch->qc_date)->format('d/m/Y') ?: '---' }} · Kết quả: {{ $productionFinishedBatch->qc_result === 'passed' ? 'Đạt' : ($productionFinishedBatch->qc_result === 'failed' ? 'Không đạt' : 'Chưa kiểm nghiệm') }}</p>
                    @if($productionFinishedBatch->qc_test_report_file)<a href="{{ route('private-documents.production-qc-report', $productionFinishedBatch) }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs font-semibold text-blue-700 underline">Xem tệp PKN</a>@endif
                </section>

                @unless($canEditDefinition)<p class="text-[10px] text-amber-700">Sản phẩm và lượng dự kiến bị khóa vì lô đã nhập kho hoặc phát sinh phân bổ/giao dịch.</p>@endunless
                <div class="flex justify-end border-t border-slate-200 pt-3"><button class="bg-blue-700 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-blue-800">Lưu thay đổi</button></div>
            </form>
        </div>
    </div>
    @if($canEditProductionAllocations)
        @push('scripts')
            <script>
                (() => {
                    const productSelect = document.querySelector('[name="product_id"]');
                    const allocationRows = Array.from(document.querySelectorAll('[data-edit-allocation-row]'));
                    if (!productSelect || !allocationRows.length) return;

                    const filterOrders = () => allocationRows.forEach(row => {
                        const matchesProduct = row.dataset.productId === productSelect.value;
                        row.classList.toggle('hidden', !matchesProduct);
                        row.querySelector('input').disabled = !matchesProduct;
                    });

                    productSelect.addEventListener('change', filterOrders);
                    filterOrders();
                })();
            </script>
        @endpush
    @endif
</x-app-layout>
