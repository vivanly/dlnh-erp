@php
    $existingLines = old('lines', isset($plan) ? $plan->lines->map(fn ($line) => [
        'product_id' => $line->product_id,
        'ppcb_id' => $line->ppcb_id,
        'planned_quantity' => $line->planned_quantity,
        'notes' => $line->notes,
    ])->all() : [['product_id' => '', 'ppcb_id' => '', 'planned_quantity' => '', 'notes' => '']]);
@endphp

<form method="POST" action="{{ $action }}" class="border border-slate-300 bg-white p-4">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif
    <div class="mb-4 grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <label class="text-xs font-semibold text-slate-700">Tháng kế hoạch
            <input type="month" name="plan_month" lang="vi" required value="{{ old('plan_month', isset($plan) ? $plan->plan_month->format('Y-m') : now()->format('Y-m')) }}" class="mt-1 w-full border-slate-300 text-xs">
        </label>
        <div class="self-end text-xs text-slate-500">Kế hoạch nhập sản lượng thủ công. Chọn sản phẩm, PPCB, sản lượng và ghi chú; duyệt kế hoạch sẽ tạo lệnh sản xuất độc lập với đơn bán và không phụ thuộc BOM.</div>
    </div>

    @if($errors->any())
        <div class="mb-3 border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">
            <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mb-2 flex items-center justify-between">
        <h3 class="text-xs font-bold uppercase text-slate-700">Sản phẩm và sản lượng dự kiến</h3>
        <button type="button" class="add-plan-line border border-blue-300 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50">Thêm sản phẩm</button>
    </div>
    <div class="plan-lines space-y-2">
        @foreach($existingLines as $index => $line)
            <div class="plan-line grid items-end gap-2 border border-slate-200 p-3" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));">
                <label class="text-[11px] font-semibold text-slate-600">Sản phẩm
                    <select name="lines[{{ $index }}][product_id]" required class="mt-1 w-full border-slate-300 text-xs">
                        <option value="">Chọn sản phẩm</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ (string) ($line['product_id'] ?? '') === (string) $product->id ? 'selected' : '' }}>{{ $product->sku ? $product->sku . ' · ' : '' }}{{ $product->name }} · {{ $product->classification ?: 'Chưa phân loại' }} · {{ $product->unit }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-[11px] font-semibold text-slate-600">PPCB
                    <select name="lines[{{ $index }}][ppcb_id]" class="mt-1 w-full border-slate-300 text-xs">
                        <option value="">Chọn PPCB</option>
                        @foreach($ppcbs as $ppcb)
                            <option value="{{ $ppcb->id }}" {{ (string) ($line['ppcb_id'] ?? '') === (string) $ppcb->id ? 'selected' : '' }}>{{ $ppcb->ma ? $ppcb->ma . ' · ' : '' }}{{ $ppcb->ten_ppcb }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-[11px] font-semibold text-slate-600">Sản lượng
                    <input type="number" name="lines[{{ $index }}][planned_quantity]" min="0.0001" step="0.0001" required value="{{ $line['planned_quantity'] ?? '' }}" class="mt-1 w-full border-slate-300 text-right font-mono text-xs">
                </label>
                <label class="text-[11px] font-semibold text-slate-600">Ghi chú
                    <input name="lines[{{ $index }}][notes]" maxlength="1000" value="{{ $line['notes'] ?? '' }}" class="mt-1 w-full border-slate-300 text-xs">
                </label>
                <button type="button" class="remove-plan-line justify-self-start border border-rose-200 px-2 py-1.5 text-xs text-rose-700 hover:bg-rose-50">Xóa</button>
            </div>
        @endforeach
    </div>
    <template id="plan-line-template">
        <div class="plan-line grid items-end gap-2 border border-slate-200 p-3" style="grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));">
            <label class="text-[11px] font-semibold text-slate-600">Sản phẩm
                <select data-name="product_id" required class="mt-1 w-full border-slate-300 text-xs">
                    <option value="">Chọn sản phẩm</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->sku ? $product->sku . ' · ' : '' }}{{ $product->name }} · {{ $product->classification ?: 'Chưa phân loại' }} · {{ $product->unit }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[11px] font-semibold text-slate-600">PPCB
                <select data-name="ppcb_id" class="mt-1 w-full border-slate-300 text-xs">
                    <option value="">Chọn PPCB</option>
                    @foreach($ppcbs as $ppcb)
                        <option value="{{ $ppcb->id }}">{{ $ppcb->ma ? $ppcb->ma . ' · ' : '' }}{{ $ppcb->ten_ppcb }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-[11px] font-semibold text-slate-600">Sản lượng
                <input data-name="planned_quantity" type="number" min="0.0001" step="0.0001" required class="mt-1 w-full border-slate-300 text-right font-mono text-xs">
            </label>
            <label class="text-[11px] font-semibold text-slate-600">Ghi chú
                <input data-name="notes" maxlength="1000" class="mt-1 w-full border-slate-300 text-xs">
            </label>
            <button type="button" class="remove-plan-line justify-self-start border border-rose-200 px-2 py-1.5 text-xs text-rose-700 hover:bg-rose-50">Xóa</button>
        </div>
    </template>
    <div class="mt-4 flex justify-end border-t border-slate-200 pt-3">
        <button class="bg-blue-700 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-blue-800">{{ $method === 'PUT' ? 'Lưu thay đổi' : 'Lưu dự thảo' }}</button>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('click', function (event) {
        if (event.target.closest('.add-plan-line')) {
            const container = document.querySelector('.plan-lines');
            const template = document.querySelector('#plan-line-template');
            const index = container.querySelectorAll('.plan-line').length;
            const row = template.content.firstElementChild.cloneNode(true);
            row.querySelectorAll('[data-name]').forEach(function (field) {
                field.name = 'lines[' + index + '][' + field.dataset.name + ']';
            });
            container.append(row);
        }
        if (event.target.closest('.remove-plan-line')) {
            const container = document.querySelector('.plan-lines');
            if (container.querySelectorAll('.plan-line').length > 1) {
                event.target.closest('.plan-line').remove();
                container.querySelectorAll('.plan-line').forEach(function (row, index) {
                    row.querySelectorAll('select, input').forEach(function (field) {
                        const fieldName = field.name.match(/\]\[([^\]]+)\]$/);
                        if (fieldName) field.name = 'lines[' + index + '][' + fieldName[1] + ']';
                    });
                });
            }
        }
    });
</script>
@endpush
