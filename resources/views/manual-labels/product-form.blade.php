<x-app-layout>
    @php($isNew = ! $product->exists)
    @php($in = 'mt-1 h-8 w-full border border-slate-300 px-2 text-xs focus:border-blue-500 focus:ring-0')
    @php($lb = 'block text-[10px] font-bold uppercase tracking-wide text-slate-500')
    <x-slot name="header">
        <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Mã hàng / {{ $isNew ? 'Thêm' : 'Sửa' }}</h2>
    </x-slot>

    <div class="py-3">
        <form method="POST" action="{{ $isNew ? route('manual-labels.products.store') : route('manual-labels.products.update', $product) }}" class="max-w-4xl space-y-4 border border-slate-300 bg-white p-4">
            @csrf
            @unless($isNew) @method('PUT') @endunless
            @if($errors->any())<div class="border border-rose-200 bg-rose-50 p-2 text-xs text-rose-800">{{ $errors->first() }}</div>@endif
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="{{ $lb }}">Tên hàng *</label><input name="name" value="{{ old('name', $product->name) }}" required class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Chủng loại (Vị thuốc / Dược liệu…)</label><input name="category" value="{{ old('category', $product->category) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Tên khoa học</label><input name="scientific_name" value="{{ old('scientific_name', $product->scientific_name) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Tiêu chuẩn áp dụng</label><input name="standard" value="{{ old('standard', $product->standard) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Nguồn gốc</label><input name="origin" value="{{ old('origin', $product->origin) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Bộ phận dùng</label><input name="part_used" value="{{ old('part_used', $product->part_used) }}" class="{{ $in }}"></div>
                <div><label class="{{ $lb }}">Số đăng ký</label><input name="registration" value="{{ old('registration', $product->registration) }}" class="{{ $in }}"></div>
            </div>
            <div class="flex items-center gap-3">
                <button class="h-8 bg-blue-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-blue-600">Lưu</button>
                <a href="{{ route('manual-labels.products') }}" class="text-[11px] font-semibold text-slate-500 hover:underline">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
