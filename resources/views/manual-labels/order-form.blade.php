<x-app-layout>
    @php($isNew = ! $order->exists)
    @php($in = 'mt-1 h-8 w-full border border-slate-300 px-2 text-xs focus:border-blue-500 focus:ring-0')
    @php($lb = 'block text-[10px] font-bold uppercase tracking-wide text-slate-500')
    <x-slot name="header">
        <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Lệnh sản xuất / {{ $isNew ? 'Thêm' : 'Sửa ' . $order->label_code }}</h2>
    </x-slot>

    <div class="py-3">
        <form method="POST" action="{{ $isNew ? route('manual-labels.orders.store') : route('manual-labels.orders.update', $order) }}" class="max-w-4xl space-y-4 border border-slate-300 bg-white p-4">
            @csrf
            @unless($isNew) @method('PUT') @endunless
            @if($errors->any())<div class="border border-rose-200 bg-rose-50 p-2 text-xs text-rose-800">{{ $errors->first() }}</div>@endif
            <div class="grid gap-3 sm:grid-cols-3">
                <div><label class="{{ $lb }}">Mã nhãn *</label><input name="label_code" value="{{ old('label_code', $order->label_code) }}" required class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Lệnh sản xuất</label><input name="production_order" value="{{ old('production_order', $order->production_order) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Ngày</label><input type="date" name="order_date" value="{{ old('order_date', $order->order_date?->format('Y-m-d')) }}" class="{{ $in }}"></div>
                <div class="sm:col-span-2"><label class="{{ $lb }}">Tên hàng *</label><input name="name" list="product-names" value="{{ old('name', $order->name) }}" required class="{{ $in }}"><datalist id="product-names">@foreach($names as $n)<option value="{{ $n }}">@endforeach</datalist></div>
                <div><label class="{{ $lb }}">Số lô</label><input name="batch" value="{{ old('batch', $order->batch) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">NSX</label><input type="date" name="mfg_date" value="{{ old('mfg_date', $order->mfg_date?->format('Y-m-d')) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">HSD</label><input type="date" name="exp_date" value="{{ old('exp_date', $order->exp_date?->format('Y-m-d')) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Quy cách ĐG</label><input name="pack_spec" value="{{ old('pack_spec', $order->pack_spec) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Số lượng</label><input name="quantity" value="{{ old('quantity', $order->quantity) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Số nhãn cần in *</label><input type="number" min="0" name="label_count" value="{{ old('label_count', $order->label_count) }}" required class="{{ $in }}"></div>
            </div>
            <div class="flex items-center gap-3">
                <button class="h-8 bg-blue-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-blue-600">Lưu</button>
                <a href="{{ route('manual-labels.orders') }}" class="text-[11px] font-semibold text-slate-500 hover:underline">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
