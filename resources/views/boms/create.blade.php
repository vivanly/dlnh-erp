<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Tạo định mức BOM phiên bản mới</h2>
            <a href="{{ route('boms.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase hover:bg-slate-300">Quay lại</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if($errors->any())
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">
                    <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('boms.store') }}" class="space-y-3" id="bom-form">
                @csrf
                <section class="bg-white border border-slate-300 p-4">
                    <h3 class="mb-3 border-b border-slate-200 pb-2 text-xs font-bold uppercase text-slate-700">Thành phẩm và sản lượng đầu ra</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                        <label class="block font-semibold text-slate-700">Thành phẩm
                            <select name="product_id" required class="mt-1 w-full text-xs border-slate-300 rounded-none">
                                <option value="">Chọn sản phẩm</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-unit="{{ $product->unit }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }} · {{ $product->sku }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block font-semibold text-slate-700">Sản lượng định mức
                            <input type="number" name="output_quantity" value="{{ old('output_quantity', 100) }}" min="0.0001" step="0.0001" required class="mt-1 w-full text-xs border-slate-300 rounded-none">
                        </label>
                        <label class="block font-semibold text-slate-700">Đơn vị đầu ra
                            <input type="text" name="output_unit" value="{{ old('output_unit') }}" placeholder="Ví dụ: kg" required class="mt-1 w-full text-xs border-slate-300 rounded-none">
                        </label>
                        <label class="block font-semibold text-slate-700">ĐVT gốc trên mỗi ĐVT nhập
                            <input type="number" name="output_to_base_factor" value="{{ old('output_to_base_factor', 1) }}" min="0.0000000001" step="any" class="mt-1 w-full text-xs border-slate-300 rounded-none">
                        </label>
                        <label class="block font-semibold text-slate-700">Tỷ lệ thu hồi (%)
                            <input type="number" name="yield_percent" value="{{ old('yield_percent', 100) }}" min="0.00001" max="100" step="0.001" required class="mt-1 w-full text-xs border-slate-300 rounded-none">
                        </label>
                        <label class="block font-semibold text-slate-700 sm:col-span-2 lg:col-span-3">Ghi chú
                            <input type="text" name="notes" value="{{ old('notes') }}" class="mt-1 w-full text-xs border-slate-300 rounded-none">
                        </label>
                    </div>
                </section>

                <section class="bg-white border border-slate-300">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-300 bg-slate-100 p-3">
                        <h3 class="text-xs font-bold uppercase text-slate-700">Nguyên liệu tiêu hao cho sản lượng định mức</h3>
                        <button type="button" id="add-bom-item" class="px-3 py-1.5 bg-blue-600 text-xs font-bold uppercase text-white hover:bg-blue-700">Thêm nguyên liệu</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-xs">
                            <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-600"><th class="p-2">Nguyên liệu</th><th class="p-2 w-36">Lượng</th><th class="p-2 w-32">Đơn vị</th><th class="p-2 w-40">ĐVT gốc / ĐVT nhập</th><th class="p-2 w-20"></th></tr></thead>
                            <tbody id="bom-items">
                                <tr class="bom-item-row border-b border-slate-100">
                                    <td class="p-2"><select name="items[0][component_product_id]" required class="w-full text-xs border-slate-300 rounded-none"><option value="">Chọn nguyên liệu</option>@foreach($products as $product)<option value="{{ $product->id }}" data-unit="{{ $product->unit }}">{{ $product->name }} · {{ $product->sku }}</option>@endforeach</select></td>
                                    <td class="p-2"><input type="number" name="items[0][quantity]" min="0.0001" step="0.0001" required class="w-full text-xs border-slate-300 rounded-none"></td>
                                    <td class="p-2"><input type="text" name="items[0][unit]" placeholder="kg" required class="w-full text-xs border-slate-300 rounded-none"></td>
                                    <td class="p-2"><input type="number" name="items[0][to_base_factor]" min="0.0000000001" step="any" placeholder="1 nếu cùng ĐVT gốc" class="w-full text-xs border-slate-300 rounded-none"></td>
                                    <td class="p-2 text-center"><button type="button" class="remove-bom-item text-rose-600" aria-label="Xóa nguyên liệu">Xóa</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-xs font-bold uppercase text-white hover:bg-emerald-700">Lưu phiên bản BOM</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.getElementById('bom-items');
            document.getElementById('add-bom-item').addEventListener('click', function () {
                const index = tbody.querySelectorAll('.bom-item-row').length;
                const row = tbody.querySelector('.bom-item-row').cloneNode(true);
                row.querySelectorAll('[name]').forEach(function (field) {
                    field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                    if (field.tagName === 'SELECT') field.selectedIndex = 0;
                    else field.value = '';
                });
                tbody.append(row);
            });
            tbody.addEventListener('click', function (event) {
                if (event.target.classList.contains('remove-bom-item') && tbody.querySelectorAll('.bom-item-row').length > 1) {
                    event.target.closest('.bom-item-row').remove();
                    tbody.querySelectorAll('.bom-item-row').forEach(function (row, index) {
                        row.querySelectorAll('[name]').forEach(function (field) {
                            field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
                        });
                    });
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
