<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Mã hàng / Import Excel</h2>
    </x-slot>

    <div class="py-3">
        <div class="max-w-4xl space-y-4">
            @if($errors->any())
                <div class="border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            @if(session('warning'))
                <div class="border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    <p class="font-semibold">{{ session('warning') }}</p>
                    @if(session('import_errors'))
                        <ul class="mt-2 list-disc pl-5">
                            @foreach(session('import_errors') as $failure)
                                <li>Dòng {{ $failure['row'] }}: {{ implode('; ', $failure['messages']) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('manual-labels.products.import') }}" enctype="multipart/form-data" class="space-y-4 border border-slate-300 bg-white p-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wide text-slate-500">Chọn file Excel (.xlsx, .xls, .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="mt-1 block w-full border border-slate-300 bg-white p-2 text-xs">
                </div>
                <div class="flex items-center gap-3">
                    <button class="h-8 bg-emerald-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-emerald-600">Tiến hành import</button>
                    <a href="{{ route('manual-labels.products') }}" class="text-[11px] font-semibold text-slate-500 hover:underline">Quay lại danh sách</a>
                </div>
            </form>

            <div class="border border-slate-300 bg-white p-4 text-xs text-slate-600">
                <p class="font-bold text-slate-700">Cấu trúc dòng tiêu đề bắt buộc:</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    <li><code>ten_hang</code> (bắt buộc, tên không được trùng)</li>
                    <li><code>chung_loai</code></li>
                    <li><code>ten_khoa_hoc</code></li>
                    <li><code>tieu_chuan</code></li>
                    <li><code>nguon_goc</code></li>
                    <li><code>bo_phan_dung</code></li>
                    <li><code>so_dang_ky</code></li>
                </ul>
                <p class="mt-3">Có thể dùng tiêu đề tiếng Việt như <strong>Tên hàng, Chủng loại, Tên khoa học, Tiêu chuẩn, Nguồn gốc, Bộ phận dùng, Số đăng ký</strong>. Dòng trống được bỏ qua và tối đa 10 lỗi đầu tiên được hiển thị.</p>
            </div>
        </div>
    </div>
</x-app-layout>
