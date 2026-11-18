<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">{{ $standard->exists ? 'Sửa tiêu chuẩn chất lượng' : 'Thêm tiêu chuẩn chất lượng' }}</h2><a href="{{ route('product-quality-standards.index') }}" class="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Quay lại</a></div></x-slot>
    @php
        $selectedProductId = old('product_id', $standard->product_id);
        $selectedProduct = $products->first(fn ($product) => (string) $product->id === (string) $selectedProductId);
        $defaultClassification = $selectedProduct ? ($selectedProduct->classification ?: '__unclassified') : '';
        $selectedClassification = old('classification', $defaultClassification);
    @endphp
    <div class="py-2"><div class="max-w-4xl px-2">
        @if($errors->any())<div class="mb-2 border-l-4 border-rose-600 bg-rose-50 p-2 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $standard->exists ? route('product-quality-standards.update', $standard) : route('product-quality-standards.store') }}" class="space-y-2 border border-slate-300 bg-white p-3">
            @csrf @if($standard->exists) @method('PUT') @endif
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <label class="block text-xs font-semibold text-slate-700">Loại sản phẩm<select id="quality-standard-classification" name="classification" required class="mt-1 h-9 w-full border-slate-300 px-2 text-xs"><option value="">Chọn loại sản phẩm</option>@foreach($classifications as $code => $classification)<option value="{{ $code }}" @selected($selectedClassification === $code)>{{ $code }} · {{ $classification }}</option>@endforeach<option value="__unclassified" @selected($selectedClassification === '__unclassified')>Chưa phân loại</option></select></label>
                <label class="block text-xs font-semibold text-slate-700">Tên sản phẩm<select id="quality-standard-product" name="product_id" required class="mt-1 h-9 w-full border-slate-300 px-2 text-xs"><option value="">Chọn sản phẩm</option>@foreach($products as $product)<option value="{{ $product->id }}" data-classification="{{ $product->classification ?: '__unclassified' }}" @selected((string) $selectedProductId === (string) $product->id)>{{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}</option>@endforeach</select><span id="quality-standard-product-hint" class="mt-1 block text-[11px] font-normal text-slate-500" aria-live="polite"></span></label>
                <label class="block text-xs font-semibold text-slate-700">Loại tiêu chuẩn<input name="standard_type" value="{{ old('standard_type', $standard->standard_type) }}" required maxlength="255" class="mt-1 h-9 w-full border-slate-300 px-2 text-xs"></label>
                <label class="block text-xs font-semibold text-slate-700">Chỉ tiêu<input name="indicator" value="{{ old('indicator', $standard->indicator) }}" required maxlength="255" class="mt-1 h-9 w-full border-slate-300 px-2 text-xs"></label>
                <label class="block text-xs font-semibold text-slate-700">Yêu cầu<textarea name="requirement" required rows="2" class="mt-1 w-full border-slate-300 px-2 py-1 text-xs">{{ old('requirement', $standard->requirement) }}</textarea></label>
                <label class="block text-xs font-semibold text-slate-700">Phương pháp<textarea name="method" required rows="2" class="mt-1 w-full border-slate-300 px-2 py-1 text-xs">{{ old('method', $standard->method) }}</textarea></label>
                <label class="block text-xs font-semibold text-slate-700 sm:col-span-2">Ghi chú<textarea name="note" rows="2" class="mt-1 w-full border-slate-300 px-2 py-1 text-xs">{{ old('note', $standard->note) }}</textarea></label>
            </div>
            <div class="flex justify-end border-t border-slate-200 pt-2"><button class="bg-blue-700 px-4 py-2 text-xs font-bold uppercase text-white">Lưu</button></div>
        </form>
    </div></div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const classificationSelect = document.getElementById('quality-standard-classification');
            const productSelect = document.getElementById('quality-standard-product');
            const hint = document.getElementById('quality-standard-product-hint');
            if (!classificationSelect || !productSelect || !hint) return;

            const filterProducts = () => {
                const classification = classificationSelect.value;
                let availableProducts = 0;

                Array.from(productSelect.options).forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const visible = classification !== '' && option.dataset.classification === classification;
                    option.hidden = !visible;
                    option.disabled = !visible;
                    if (visible) availableProducts++;
                });

                if (productSelect.selectedOptions[0]?.disabled) productSelect.value = '';
                productSelect.disabled = classification === '' || availableProducts === 0;
                hint.textContent = classification === ''
                    ? 'Chọn loại sản phẩm để hiện danh sách phù hợp.'
                    : availableProducts === 0
                        ? 'Chưa có sản phẩm thuộc loại này.'
                        : `${availableProducts} sản phẩm phù hợp.`;
            };

            classificationSelect.addEventListener('change', filterProducts);
            filterProducts();
        });
    </script>
</x-app-layout>