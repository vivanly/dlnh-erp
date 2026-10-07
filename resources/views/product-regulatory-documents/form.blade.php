@if ($errors->any())
    <div class="mb-4 p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">
        <ul class="list-disc pl-4 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST" class="space-y-4">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="product-classification-filter" class="block text-sm font-semibold text-slate-700">Lọc theo phân loại</label>
        <select id="product-classification-filter" class="mt-1 block w-full border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
            <option value="">Tất cả phân loại</option>
            @foreach($classifications as $classification)
                <option value="{{ $classification }}">{{ $classification }}</option>
            @endforeach
            <option value="__unclassified">Chưa phân loại</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700">Sản phẩm <span class="text-rose-600">*</span></label>
        <select id="product-select" name="product_id" required class="mt-1 block w-full border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
            <option value="">-- Chọn sản phẩm --</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" data-classification="{{ $product->classification ?? '' }}" @selected((string) old('product_id', $document?->product_id ?? ($selectedProductId ?? null)) === (string) $product->id)>
                    {{ $product->name }} - {{ $product->sku }} ({{ $product->classification ?: 'Chưa phân loại' }})
                </option>
            @endforeach
        </select>
    </div>

    <script>
        (() => {
            const classificationFilter = document.getElementById('product-classification-filter');
            const productSelect = document.getElementById('product-select');

            classificationFilter.addEventListener('change', () => {
                const selectedClassification = classificationFilter.value;

                Array.from(productSelect.options).forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    const optionClassification = option.dataset.classification || '__unclassified';
                    const visible = !selectedClassification || optionClassification === selectedClassification;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (productSelect.selectedOptions.length
                    && productSelect.selectedOptions[0].disabled) {
                    productSelect.value = '';
                }
            });
        })();
    </script>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-700">Loại hồ sơ <span class="text-rose-600">*</span></label>
            <select name="document_type" required class="mt-1 block w-full border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
                <option value="">-- Chọn loại --</option>
                <option value="cong_bo" @selected(old('document_type', $document?->document_type) === 'cong_bo')>Công bố</option>
                <option value="dang_ky" @selected(old('document_type', $document?->document_type) === 'dang_ky')>Đăng ký</option>
                <option value="gpnk" @selected(old('document_type', $document?->document_type) === 'gpnk')>Giấy phép nhập khẩu (GPNK)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700">Loại hình / cơ quan cấp <span class="text-rose-600">*</span></label>
            <input type="text" name="document_form" value="{{ old('document_form', $document?->document_form) }}" list="document-forms" required class="mt-1 block w-full border-slate-300 rounded-none focus:border-blue-600 focus:ring-0" placeholder="Ví dụ: Tự công bố">
            <datalist id="document-forms">
                <option value="Tự công bố">
                <option value="Sở Y tế công bố">
                <option value="Số tự đăng ký">
                <option value="Số lấy của đơn vị khác">
                <option value="Giấy phép nhập khẩu">
            </datalist>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700">Số hồ sơ <span class="text-rose-600">*</span></label>
            <input type="text" name="document_number" value="{{ old('document_number', $document?->document_number) }}" required class="mt-1 block w-full border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700">Ngày cấp hồ sơ <span class="text-rose-600">*</span></label>
            <input type="date" name="document_date" value="{{ old('document_date', $document?->document_date?->format('Y-m-d')) }}" required class="mt-1 block w-full border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700">Ghi chú</label>
        <textarea name="note" rows="3" class="mt-1 block w-full border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">{{ old('note', $document?->note) }}</textarea>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <a href="{{ route('product-regulatory-documents.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 text-sm font-semibold">Hủy</a>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Lưu hồ sơ</button>
    </div>
</form>
