<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Tạo mã lô nội bộ</h2>
            <a href="{{ $sourceProductionOrder ? route('qa.production-output-lots.index') : route('qa.internal-lots.index') }}" class="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Quay lại</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-3xl px-2">
            @if(session('error'))<div class="mb-3 border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())
                <div class="mb-3 border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">
                    <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('qa.internal-lots.store') }}" class="space-y-4 border border-slate-300 bg-white p-4">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @php
                        $productClassifications = $products->pluck('classification')->filter(fn ($classification) => filled($classification))->unique()->sort()->values();
                        $hasUnclassifiedProducts = $products->contains(fn ($product) => blank($product->classification));
                        $selectedProductId = old('product_id', $sourceProductionOrder?->product_id);
                        $selectedProduct = $products->firstWhere('id', $selectedProductId);
                    @endphp
                    @if($sourceProductionOrder)
                        <div class="border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900 sm:col-span-2">
                            Tạo lô cho lệnh <strong class="font-mono">{{ $sourceProductionOrder->production_code }}</strong> · {{ $sourceProductionOrder->product->name }} · Kế hoạch còn {{ number_format((float) $sourceProductionOrder->pending_finished_quantity, 4) }} {{ $sourceProductionOrder->unit }}.
                            Lô vẫn được tạo độc lập; Kho sẽ chọn lô này khi nhập thành phẩm.
                        </div>
                    @endif
                    <label class="text-xs font-semibold text-slate-700">Lọc theo phân loại
                        <select id="product-classification-filter" class="mt-1 w-full border-slate-300 text-xs">
                            <option value="">Tất cả phân loại</option>
                            @foreach($productClassifications as $classification)
                                <option value="{{ $classification }}" @selected($selectedProduct && $selectedProduct->classification === $classification)>{{ $classification }}</option>
                            @endforeach
                            @if($hasUnclassifiedProducts)
                                <option value="__unclassified__" @selected($selectedProduct && blank($selectedProduct->classification))>Chưa phân loại</option>
                            @endif
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Sản phẩm
                        <select id="internal-lot-product" name="product_id" required class="mt-1 w-full border-slate-300 text-xs">
                            <option value="">Chọn sản phẩm</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-classification="{{ $product->classification ?? '' }}" data-origin="{{ $product->origin ?? '' }}" {{ (string) $selectedProductId === (string) $product->id ? 'selected' : '' }}>{{ $product->classification ? $product->classification . ' · ' : '' }}{{ $product->sku ? $product->sku . ' · ' : '' }}{{ $product->name }} · {{ $product->unit }}{{ $product->origin ? ' · ' . $product->origin : '' }}</option>
                            @endforeach
                        </select>
                        <span id="product-filter-count" class="mt-1 block text-[10px] font-normal text-slate-500" aria-live="polite"></span>
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Số lô nội bộ
                        <input name="batch_number" value="{{ old('batch_number') }}" required maxlength="255" class="mt-1 w-full border-slate-300 font-mono text-xs" autocomplete="off">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Nguồn gốc sản phẩm
                        <input id="internal-lot-origin" value="{{ $selectedProduct?->origin }}" readonly class="mt-1 w-full border-slate-300 bg-slate-50 text-xs" aria-live="polite">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">PPCB
                        <select name="ppcb_id" class="mt-1 w-full border-slate-300 text-xs">
                            <option value="">Không chọn PPCB</option>
                            @foreach($ppcbs as $ppcb)
                                <option value="{{ $ppcb->id }}" {{ (string) old('ppcb_id') === (string) $ppcb->id ? 'selected' : '' }}>{{ $ppcb->ma }} · {{ $ppcb->ten_ppcb }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Số lượng dự kiến có thể phân bổ / nhập kho
                        <input type="number" name="planned_quantity" value="{{ old('planned_quantity', $sourceProductionOrder?->pending_finished_quantity) }}" min="0.0001" step="0.0001" required class="mt-1 w-full border-slate-300 text-right font-mono text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Ngày sản xuất
                        <input type="date" name="mfg_date" value="{{ old('mfg_date') }}" class="mt-1 w-full border-slate-300 text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Hạn sử dụng
                        <input type="date" name="exp_date" value="{{ old('exp_date') }}" class="mt-1 w-full border-slate-300 text-xs">
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Số giấy phép
                        <input name="license_number" value="{{ old('license_number') }}" maxlength="255" class="mt-1 w-full border-slate-300 text-xs">
                    </label>
                </div>
                <p class="border-t border-slate-200 pt-3 text-[10px] text-slate-500">QA tạo mã lô độc lập với lệnh sản xuất. Khi Kho chốt sản lượng của lệnh kế hoạch tháng, Kho chọn lô này để ghi nhận sản lượng và nối dấu vết nguyên liệu. Lượng dự kiến có thể được phân bổ cho đơn ngay; Kho nhập số lượng thực tế theo từng lần, không vượt lượng dự kiến.</p>
                <div class="flex justify-end border-t border-slate-200 pt-3">
                    <button type="submit" class="bg-emerald-700 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-800">Tạo lô</button>
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

                if (!classificationFilter || !productSelect || !productCount || !originInput) return;

                const placeholder = productSelect.options[0].cloneNode(true);
                const products = Array.from(productSelect.options).slice(1).map(option => option.cloneNode(true));

                const updateOrigin = () => {
                    originInput.value = productSelect.selectedOptions[0]?.dataset.origin ?? '';
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
                    productSelect.value = visibleProducts.some(option => option.value === selectedProductId)
                        ? selectedProductId
                        : '';
                    productCount.textContent = `${visibleProducts.length} sản phẩm`;
                    updateOrigin();
                };

                classificationFilter.addEventListener('change', filterProducts);
                productSelect.addEventListener('change', updateOrigin);
                filterProducts();
            })();
        </script>
    @endpush
</x-app-layout>
