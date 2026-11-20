<x-app-layout>
    @push('scripts') @vite('resources/js/app.js') @endpush
    @php
        $isNew = ! $order->exists;
        $in = 'mt-1 h-8 w-full border border-slate-300 px-2 text-xs focus:border-blue-500 focus:ring-0';
        $lb = 'block text-[10px] font-bold uppercase tracking-wide text-slate-500';
        $emptyLine = ['name' => '', 'batch' => '', 'mfg_date' => '', 'exp_date' => '', 'pack_spec' => '', 'quantity' => '', 'label_type' => '', 'traditional_medicine_name' => false, 'qr_content' => '', 'storage' => \App\Models\ManualLabelProductionOrder::DEFAULT_STORAGE];
        $template = $isNew ? [] : $order->toFormArray();
    @endphp
    <x-slot name="header">
        <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Lệnh sản xuất / {{ $isNew ? 'Thêm' : 'Sửa ' . $order->label_code }}</h2>
    </x-slot>
    <div class="py-3">
        <form method="POST" action="{{ $isNew ? route('manual-labels.orders.store') : route('manual-labels.orders.update', $order) }}"
              class="max-w-4xl space-y-3 border border-slate-300 bg-white p-3"
              @if($isNew) x-data="{ lines: @js(array_values(old('lines', [$emptyLine]))), emptyLine: @js($emptyLine) }"
              @else x-data="{ type: @js(old('label_type', $template['label_type'])) }" @endif>
            @csrf
            @unless($isNew) @method('PUT') @endunless
            @if($errors->any())
                <div class="border border-rose-200 bg-rose-50 p-2 text-xs text-rose-800">
                    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <div class="grid gap-2 sm:grid-cols-2">
                <div><label class="{{ $lb }}">Lệnh sản xuất *</label><input name="production_order" value="{{ old('production_order', $order->production_order) }}" required class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Ngày (dd/mm/yyyy)</label><input name="order_date" inputmode="numeric" placeholder="dd/mm/yyyy" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" value="{{ \App\Services\ManualLabelOrders::dateForDisplay(old('order_date', $order->order_date?->format('Y-m-d'))) }}" class="{{ $in }}"></div>
                @unless($isNew)<div><label class="{{ $lb }}">Mã nhãn</label><p class="mt-2 text-xs font-semibold">{{ $order->label_code }}</p></div>@endunless
            </div>
            <datalist id="product-names">@foreach($names as $n)<option value="{{ $n }}">@endforeach</datalist>
            @if($isNew)
                <template x-for="(line, index) in lines" :key="index">
                    <div class="grid gap-x-3 gap-y-2 border border-slate-200 p-2 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="flex justify-between sm:col-span-2 lg:col-span-5"><strong class="text-xs" x-text="'Dòng ' + (index + 1)"></strong><button type="button" x-show="lines.length > 1" @click="lines.splice(index, 1)" class="text-xs text-rose-600">Xóa dòng</button></div>
                        <div class="lg:col-span-2"><label class="{{ $lb }}">Tên hàng *</label><input :name="`lines[${index}][name]`" x-model="line.name" list="product-names" required class="{{ $in }}"></div>
                        <div><label class="{{ $lb }}">Số lô *</label><input :name="`lines[${index}][batch]`" x-model="line.batch" required class="{{ $in }}"></div>
                        <div><label class="{{ $lb }}">NSX (dd/mm/yyyy)</label><input :name="`lines[${index}][mfg_date]`" x-model="line.mfg_date" inputmode="numeric" placeholder="dd/mm/yyyy" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" class="{{ $in }}"></div>
                        <div><label class="{{ $lb }}">HSD (dd/mm/yyyy)</label><input :name="`lines[${index}][exp_date]`" x-model="line.exp_date" inputmode="numeric" placeholder="dd/mm/yyyy" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" class="{{ $in }}"></div>
                        <div><label class="{{ $lb }}">QCĐG * (KG)</label><input :name="`lines[${index}][pack_spec]`" x-model="line.pack_spec" inputmode="decimal" placeholder="1 hoặc 0.5" required class="{{ $in }}"></div>
                        <div><label class="{{ $lb }}">Số lượng * (KG)</label><input :name="`lines[${index}][quantity]`" x-model="line.quantity" inputmode="decimal" placeholder="50 hoặc 0.5" required class="{{ $in }}"></div>
                        <div class="sm:col-span-2 lg:col-span-2"><label class="{{ $lb }}">Mẫu nhãn *</label><select :name="`lines[${index}][label_type]`" x-model="line.label_type" required class="{{ $in }}"><option value="">-- Chọn mẫu --</option>@foreach(\App\Http\Controllers\ManualLabelController::LABEL_TYPES as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach</select></div>
                        <label x-show="line.label_type === 'vt'" class="flex items-center gap-2 self-end pb-2 text-xs"><input type="hidden" :name="`lines[${index}][traditional_medicine_name]`" value="0"><input type="checkbox" :name="`lines[${index}][traditional_medicine_name]`" value="1" x-model="line.traditional_medicine_name"> Vị thuốc cổ truyền</label>
                        <div class="sm:col-span-2 lg:col-span-5"><label class="{{ $lb }}">Nội dung mã QR</label><input :name="`lines[${index}][qr_content]`" x-model="line.qr_content" maxlength="1000" placeholder="Link hoặc nội dung; để trống nếu không in mã QR" class="{{ $in }}"></div>
                        <div class="sm:col-span-2 lg:col-span-5"><label class="{{ $lb }}">Bảo quản</label><input :name="`lines[${index}][storage]`" x-model="line.storage" maxlength="255" class="{{ $in }}"></div>
                    </div>
                </template>
                <button type="button" @click="lines.push({...emptyLine})" class="text-xs font-semibold text-blue-700">+ Thêm dòng</button>
                <p class="text-xs text-slate-500">Mã nhãn được tự sinh khi lưu. Số nhãn tính bằng SL / QCĐG; phần khối lượng lẻ được báo khi in.</p>
            @else
                <div class="grid gap-x-3 gap-y-2 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="sm:col-span-2"><label class="{{ $lb }}">Tên hàng *</label><input name="name" list="product-names" value="{{ old('name', $order->name) }}" required class="{{ $in }}"></div>
                    <div><label class="{{ $lb }}">Số lô *</label><input name="batch" value="{{ old('batch', $order->batch) }}" required class="{{ $in }}"></div>
                    <div><label class="{{ $lb }}">NSX (dd/mm/yyyy)</label><input name="mfg_date" inputmode="numeric" placeholder="dd/mm/yyyy" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" value="{{ \App\Services\ManualLabelOrders::dateForDisplay(old('mfg_date', $order->mfg_date?->format('Y-m-d'))) }}" class="{{ $in }}"></div>
                    <div><label class="{{ $lb }}">HSD (dd/mm/yyyy)</label><input name="exp_date" inputmode="numeric" placeholder="dd/mm/yyyy" pattern="[0-9]{2}/[0-9]{2}/[0-9]{4}" maxlength="10" value="{{ \App\Services\ManualLabelOrders::dateForDisplay(old('exp_date', $order->exp_date?->format('Y-m-d'))) }}" class="{{ $in }}"></div>
                    <div><label class="{{ $lb }}">QCĐG * (KG)</label><input name="pack_spec" value="{{ old('pack_spec', $order->pack_spec) }}" inputmode="decimal" placeholder="Ví dụ: 1 hoặc 0.5" required class="{{ $in }}"></div>
                    <div><label class="{{ $lb }}">Số lượng * (KG)</label><input name="quantity" value="{{ old('quantity', $order->quantity) }}" inputmode="decimal" placeholder="Ví dụ: 50 hoặc 0.5" required class="{{ $in }}"></div>
                    <div class="sm:col-span-2 lg:col-span-2"><label class="{{ $lb }}">Mẫu nhãn *</label><select name="label_type" x-model="type" required class="{{ $in }}"><option value="">-- Chọn mẫu --</option>@foreach(\App\Http\Controllers\ManualLabelController::LABEL_TYPES as $value => $text)<option value="{{ $value }}">{{ $text }}</option>@endforeach</select></div>
                    <label x-show="type === 'vt'" class="flex items-center gap-2 self-end pb-2 text-xs"><input type="hidden" name="traditional_medicine_name" value="0"><input type="checkbox" name="traditional_medicine_name" value="1" @checked(old('traditional_medicine_name', $template['traditional_medicine_name']))> Vị thuốc cổ truyền</label>
                    <div class="sm:col-span-2 lg:col-span-5"><label class="{{ $lb }}">Nội dung mã QR</label><input name="qr_content" value="{{ old('qr_content', $order->qr_content) }}" maxlength="1000" placeholder="Link hoặc nội dung; để trống nếu không in mã QR" class="{{ $in }}"></div>
                    <div class="sm:col-span-2 lg:col-span-5"><label class="{{ $lb }}">Bảo quản</label><input name="storage" value="{{ old('storage', $order->storage) }}" maxlength="255" class="{{ $in }}"></div>
                </div>
            @endif
            <div class="flex items-center gap-3">
                <button class="h-8 bg-blue-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-blue-600">Lưu</button>
                <a href="{{ route('manual-labels.orders') }}" class="text-[11px] font-semibold text-slate-500 hover:underline">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
