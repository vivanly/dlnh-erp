@php
    $formHasOldInput = old('_product_modal') === $modal;
    $value = fn ($key) => $formHasOldInput ? old($key) : $product?->{$key};
@endphp
@if($formHasOldInput && $errors->any())<div data-product-errors class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-800"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="grid grid-cols-1 gap-x-5 gap-y-4 md:grid-cols-2">
<label class="text-xs font-semibold text-slate-700">Phân loại<select name="classification" required class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"><option value="">-- Chọn phân loại --</option>@foreach($productClassifications as $code => $classification)<option value="{{ $code }}" @selected($value('classification') === $code)>{{ $code }} · {{ $classification }}</option>@endforeach</select></label>
<label class="text-xs font-semibold text-slate-700">Mã hàng (SKU) *<input name="sku" value="{{ $value('sku') }}" required class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700 md:col-span-2">Tên hàng hóa *<input name="name" value="{{ $value('name') }}" required class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700">Đơn vị tính<input name="unit" value="{{ $value('unit') }}" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700">Nguồn gốc<input name="origin" value="{{ $value('origin') }}" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700">Dược liệu thô nguồn<select name="raw_material_id" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"><option value="">-- Không gắn --</option>@foreach($rawMaterials as $rawMaterial)<option value="{{ $rawMaterial->id }}" @selected((string) $value('raw_material_id') === (string) $rawMaterial->id)>{{ $rawMaterial->name }} ({{ $rawMaterial->sku }})</option>@endforeach</select></label>
<label class="text-xs font-semibold text-slate-700">Bộ phận dùng<input name="part_used" value="{{ $value('part_used') }}" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700">Phương pháp chế biến<select name="ppcb_id" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"><option value="">-- Chọn PPCB --</option>@foreach($ppcbs as $ppcb)<option value="{{ $ppcb->id }}" @selected((string) $value('ppcb_id') === (string) $ppcb->id)>{{ $ppcb->ten_ppcb }} ({{ $ppcb->ma }})</option>@endforeach</select></label>
<label class="text-xs font-semibold text-slate-700">Tên khoa học<input name="scientific_name" value="{{ $value('scientific_name') }}" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700">Mã GTIN<input name="gtin" value="{{ $value('gtin') }}" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700">Tài liệu tham khảo tên khoa học<input name="scientific_name_reference" value="{{ $value('scientific_name_reference') }}" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm"></label>
<label class="text-xs font-semibold text-slate-700 md:col-span-2">Ghi chú<textarea name="note" rows="3" class="mt-1 w-full border-slate-300 px-3 py-2 text-sm">{{ $value('note') }}</textarea></label>
</div>