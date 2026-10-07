<x-app-layout>
    <x-slot name="header"><div class="flex items-center justify-between"><h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">{{ $storageMethod->exists ? 'Sửa phương pháp bảo quản' : 'Thêm phương pháp bảo quản' }}</h2><a href="{{ route('product-storage-methods.index') }}" class="border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700">Quay lại</a></div></x-slot>
    <div class="py-2"><div class="max-w-3xl px-2">
        @if($errors->any())<div class="mb-3 border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $storageMethod->exists ? route('product-storage-methods.update', $storageMethod) : route('product-storage-methods.store') }}" class="space-y-3 border border-slate-300 bg-white p-4">
            @csrf @if($storageMethod->exists) @method('PUT') @endif
            <label class="block text-xs font-semibold text-slate-700">Sản phẩm<select name="product_id" required class="mt-1 w-full border-slate-300 text-xs"><option value="">Chọn sản phẩm</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) old('product_id', $storageMethod->product_id) === (string) $product->id)>{{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}</option>@endforeach</select></label>
            <label class="block text-xs font-semibold text-slate-700">Phương pháp bảo quản<textarea name="storage_method" required rows="4" class="mt-1 w-full border-slate-300 text-xs">{{ old('storage_method', $storageMethod->storage_method) }}</textarea></label>
            <label class="block text-xs font-semibold text-slate-700">Ghi chú<textarea name="note" rows="3" class="mt-1 w-full border-slate-300 text-xs">{{ old('note', $storageMethod->note) }}</textarea></label>
            <div class="flex justify-end border-t border-slate-200 pt-3"><button class="bg-blue-700 px-4 py-2 text-xs font-bold uppercase text-white">Lưu</button></div>
        </form>
    </div></div>
</x-app-layout>