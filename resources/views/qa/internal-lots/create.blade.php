<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-700">QA · Quản lý lô</p>
                <h2 class="mt-1 text-sm font-bold uppercase tracking-wide text-slate-800">Tạo mã lô nội bộ</h2>
            </div>
            <a href="{{ $sourceProductionOrder ? route('qa.production-output-lots.index') : route('qa.internal-lots.index') }}" class="shrink-0 border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">Quay lại</a>
        </div>
    </x-slot>

    <div class="py-5 sm:py-7">
        <div class="mx-auto max-w-4xl px-3 sm:px-6">
            @if(session('error'))
                <div class="mb-4 border-l-4 border-rose-600 bg-rose-50 p-3 text-sm text-rose-900">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 border-l-4 border-rose-600 bg-rose-50 p-3 text-sm text-rose-900">
                    <p class="font-semibold">Vui lòng kiểm tra lại thông tin:</p>
                    <ul class="mt-1 list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @php
                $productClassifications = $products->pluck('classification')->filter(fn ($classification) => filled($classification))->unique()->sort()->values();
                $hasUnclassifiedProducts = $products->contains(fn ($product) => blank($product->classification));
                $selectedProductId = old('product_id', $sourceProductionOrder?->product_id);
                $selectedProduct = $products->firstWhere('id', $selectedProductId);
            @endphp

            <form method="POST" action="{{ route('qa.internal-lots.store') }}" class="overflow-hidden border border-slate-200 bg-white shadow-sm">
                @csrf
                @if($sourceProductionOrder)
                    <div class="border-b border-blue-200 bg-blue-50 px-4 py-4 sm:px-6">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700" aria-hidden="true">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold uppercase tracking-wide text-blue-900">Đang tạo lô từ lệnh sản xuất</p>
                                <p class="mt-1 text-sm text-blue-900"><span class="font-mono font-bold">{{ $sourceProductionOrder->production_code }}</span><span class="mx-1 text-blue-400">·</span>{{ $sourceProductionOrder->product->name }}</p>
                                <p class="mt-1 text-xs text-blue-800">Sản lượng chưa phân bổ: <strong>{{ number_format((float) $sourceProductionOrder->pending_finished_quantity, 4) }} {{ $sourceProductionOrder->unit }}</strong>. Có thể gộp sản lượng từ nhiều lệnh cùng sản phẩm vào một lô.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-6 p-4 sm:p-6">
                    <section>
                        <div class="mb-3 border-b border-slate-100 pb-2">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Thông tin sản phẩm và lô</h3>
                            <p class="mt-1 text-xs text-slate-500">Chọn đúng sản phẩm trước khi khai báo số lô và thông tin truy xuất.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="text-xs font-semibold text-slate-700">Lọc theo phân loại
                                <select id="product-classification-filter" class="mt-1.5 w-full border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Tất cả phân loại</option>
                                    @foreach($productClassifications as $classification)
                                        <option value="{{ $classification }}" @selected($selectedProduct && $selectedProduct->classification === $classification)>{{ $classification }}</option>
                                    @endforeach
                                    @if($hasUnclassifiedProducts)
                                        <option value="__unclassified__" @selected($selectedProduct && blank($selectedProduct->classification))>Chưa phân loại</option>
                                    @endif
                                </select>
                            </label>
                            <label class="text-xs font-semibold text-slate-700">Sản phẩm <span class="text-rose-600">*</span>
                                <select id="internal-lot-product" name="product_id" required class="mt-1.5 w-full border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Chọn sản phẩm</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-classification="{{ $product->classification ?? '' }}" data-origin="{{ $product->origin ?? '' }}" data-unit="{{ $product->unit }}" @selected((string) $selectedProductId === (string) $product->id)>{{ $product->classification ? $product->classification . ' · ' : '' }}{{ $product->sku ? $product->sku . ' · ' : '' }}{{ $product->name }} · {{ $product->unit }}{{ $product->origin ? ' · ' . $product->origin : '' }}</option>
                                    @endforeach
                                </select>
                                <span id="product-filter-count" class="mt-1 block text-[11px] font-normal text-slate-500" aria-live="polite"></span>
                            </label>
                            <label class="text-xs font-semibold text-slate-700">Số lô nội bộ <span class="text-rose-600">*</span>
                                <input name="batch_number" value="{{ old('batch_number') }}" required maxlength="255" autocomplete="off" placeholder="Nhập hoặc quét mã lô" class="mt-1.5 w-full border-slate-300 font-mono text-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                            <label class="text-xs font-semibold text-slate-700">Nguồn gốc sản phẩm
                                <input id="internal-lot-origin" value="{{ $selectedProduct?->origin }}" readonly class="mt-1.5 w-full border-slate-300 bg-slate-50 text-sm text-slate-600" aria-live="polite">
                            </label>
                            <label class="text-xs font-semibold text-slate-700">PPCB
                                <select name="ppcb_id" class="mt-1.5 w-full border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Không chọn PPCB</option>
                                    @foreach($ppcbs as $ppcb)
                                        <option value="{{ $ppcb->id }}" @selected((string) old('ppcb_id') === (string) $ppcb->id)>{{ $ppcb->ma }} · {{ $ppcb->ten_ppcb }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-semibold text-slate-700">Số lượng dự kiến phân bổ / nhập kho <span class="text-rose-600">*</span>
                                <div class="mt-1.5 flex">
                                    <input type="number" name="planned_quantity" value="{{ old('planned_quantity', $sourceProductionOrder?->pending_finished_quantity) }}" min="0.0001" step="0.0001" required class="w-full min-w-0 border-slate-300 text-right font-mono text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span id="internal-lot-unit" class="inline-flex items-center border border-l-0 border-slate-300 bg-slate-50 px-3 text-xs text-slate-600">{{ $selectedProduct?->unit ?? 'Đơn vị' }}</span>
                                </div>
                            </label>
                            <label class="text-xs font-semibold text-slate-700">Ngày sản xuất
                                <input type="date" name="mfg_date" value="{{ old('mfg_date') }}" class="mt-1.5 w-full border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                            <label class="text-xs font-semibold text-slate-700">Hạn sử dụng
                                <input type="date" name="exp_date" value="{{ old('exp_date') }}" class="mt-1.5 w-full border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                            <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Số giấy phép
                                <input name="license_number" value="{{ old('license_number') }}" maxlength="255" class="mt-1.5 w-full border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                        </div>
                    </section>

                    <section class="border border-slate-200">
                        <div class="flex flex-col gap-1 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-800">Phân bổ sản lượng từ lệnh sản xuất</h3>
                                <p class="mt-1 text-xs text-slate-500">Chỉ nhập số lượng cho các lệnh cùng sản phẩm. Có thể để trống nếu tạo lô độc lập.</p>
                            </div>
                            <span class="text-[11px] text-slate-500">Đơn vị: <strong id="allocation-unit">{{ $selectedProduct?->unit ?? '—' }}</strong></span>
                        </div>
                        <div class="max-h-64 overflow-y-auto px-4">
                            @forelse($availableProductionOrders as $availableOrder)
                                @php
                                    $defaultAllocation = $sourceProductionOrder && $sourceProductionOrder->id === $availableOrder->id
                                        ? $sourceProductionOrder->pending_finished_quantity
                                        : '';
                                    $allocationValue = old('production_order_allocations.'.$availableOrder->id, $defaultAllocation);
                                @endphp
                                <label data-production-order-row data-product-id="{{ $availableOrder->product_id }}" data-unit="{{ $availableOrder->unit }}" class="grid grid-cols-1 gap-2 border-b border-slate-100 py-3 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_10rem] sm:items-center">
                                    <span class="min-w-0 text-xs text-slate-700"><strong class="font-mono">{{ $availableOrder->production_code }}</strong><span class="mx-1 text-slate-300">·</span>{{ $availableOrder->product->name }}<span class="mt-1 block text-[11px] text-slate-500">Còn có thể phân bổ: {{ number_format((float) $availableOrder->pending_finished_quantity, 4) }} {{ $availableOrder->unit }}</span></span>
                                    <span class="flex items-center gap-2"><input type="number" name="production_order_allocations[{{ $availableOrder->id }}]" value="{{ $allocationValue }}" min="0.0001" max="{{ $availableOrder->pending_finished_quantity }}" step="0.0001" placeholder="Số lượng" aria-label="Số lượng phân bổ từ lệnh {{ $availableOrder->production_code }}" class="w-full border-slate-300 text-right font-mono text-sm focus:border-blue-500 focus:ring-blue-500"><span class="text-xs text-slate-500">{{ $availableOrder->unit }}</span></span>
                                </label>
                            @empty
                                <p class="py-5 text-center text-xs text-slate-500">Hiện không có lệnh sản xuất hoàn thành còn sản lượng để phân bổ.</p>
                            @endforelse
                        </div>
                    </section>

                    <p class="border-l-2 border-amber-400 bg-amber-50 px-3 py-2 text-xs leading-relaxed text-amber-900">Tổng sản lượng phân bổ không được vượt số dư của từng lệnh hoặc sức chứa của lô. Lô có thể nhận thêm sản lượng trước khi QA duyệt; kho nhập theo từng lần phân bổ.</p>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:justify-end sm:px-6">
                    <a href="{{ $sourceProductionOrder ? route('qa.production-output-lots.index') : route('qa.internal-lots.index') }}" class="border border-slate-300 bg-white px-4 py-2.5 text-center text-xs font-semibold text-slate-700 hover:bg-slate-100">Hủy</a>
                    <button type="submit" class="bg-emerald-700 px-5 py-2.5 text-xs font-bold uppercase tracking-wide text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Tạo lô nội bộ</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (() => {
                const classificationFilter = document.getElementById('product-classification-filter');
                const productSelect = document.getElementById('internal-lot-product');
                const productCount = document.getElementById('product-filter-count');
                const originInput = document.getElementById('internal-lot-origin');
                const unitLabel = document.getElementById('internal-lot-unit');
                const allocationUnit = document.getElementById('allocation-unit');
                const allocationRows = Array.from(document.querySelectorAll('[data-production-order-row]'));

                if (!classificationFilter || !productSelect || !productCount || !originInput) return;

                const placeholder = productSelect.options[0].cloneNode(true);
                const products = Array.from(productSelect.options).slice(1).map(option => option.cloneNode(true));

                const updateProductDetails = () => {
                    const selectedOption = productSelect.selectedOptions[0];
                    originInput.value = selectedOption?.dataset.origin ?? '';
                    if (unitLabel) unitLabel.textContent = selectedOption?.dataset.unit ?? 'Đơn vị';
                    allocationRows.forEach(row => {
                        const matchesProduct = row.dataset.productId === productSelect.value;
                        row.classList.toggle('hidden', !matchesProduct);
                        row.querySelector('input').disabled = !matchesProduct;
                    });
                    const visibleRow = allocationRows.find(row => !row.classList.contains('hidden'));
                    if (allocationUnit) allocationUnit.textContent = visibleRow?.dataset.unit ?? '—';
                };

                const filterProducts = () => {
                    const classification = classificationFilter.value;
                    const selectedProductId = productSelect.value;
                    const visibleProducts = products.filter(option => {
                        if (!classification) return true;
                        if (classification === '__unclassified__') return !option.dataset.classification;
                        return option.dataset.classification === classification;
                    });

                    productSelect.replaceChildren(placeholder.cloneNode(true), ...visibleProducts);
                    productSelect.value = visibleProducts.some(option => option.value === selectedProductId) ? selectedProductId : '';
                    productCount.textContent = `${visibleProducts.length} sản phẩm`;
                    updateProductDetails();
                };

                classificationFilter.addEventListener('change', filterProducts);
                productSelect.addEventListener('change', updateProductDetails);
                filterProducts();
            })();
        </script>
    @endpush
</x-app-layout>
