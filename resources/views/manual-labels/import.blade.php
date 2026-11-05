<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Import Excel</h2>
                <p class="mt-1 text-xs text-slate-500">Tải file Excel dự trù lên hệ thống; khi in nhãn tìm lệnh sản xuất để tự điền thông tin.</p>
            </div>
            <a href="{{ route('manual-labels.create') }}" class="rounded-md bg-blue-700 px-3.5 py-2 text-[11px] font-bold uppercase text-white hover:bg-blue-600">+ In nhãn mới</a>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-5xl px-2 space-y-3">
            @if(session('success'))<div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">{{ $errors->first() }}</div>@endif
            @if(session('warning'))
                <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">
                    <p>{{ session('warning') }}</p>
                    @if(session('import_errors'))
                        <ul class="mt-2 list-disc space-y-0.5 pl-5">
                            @foreach(session('import_errors') as $failure)<li>Dòng {{ $failure['row'] }}: {{ implode('; ', $failure['messages']) }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <form action="{{ route('manual-labels.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xlsm,.xls" required class="rounded border border-slate-300 p-1 text-xs">
                    <button class="h-8 rounded bg-emerald-600 px-3 text-[11px] font-bold uppercase text-white hover:bg-emerald-500">Tải lên &amp; đọc dữ liệu</button>
                </form>
                <div class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                    <p>Đọc sheet <code>DM lệnh SX</code> (Mã nhãn, Lệnh SX, Tên hàng, Số lô, NSX, HSD, Quy cách ĐG, Số lượng, Số nhãn cần in) và sheet <code>MÃ HÀNG</code> (Tên khoa học, Tiêu chuẩn, Nguồn gốc, Bộ phận dùng, Số đăng ký), nối với nhau theo tên hàng.</p>
                    <p>NSX/HSD để công thức trong Excel thì hệ thống tự tính: NSX theo ngày trong số lô (ddmmyy…), HSD = NSX + 2 năm − 1 ngày. Tải lại file mới sẽ cập nhật theo Mã nhãn; dòng cũ không có trong file mới vẫn được giữ.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-700 shadow-sm">
                <div>Hiện có <b>{{ number_format($orderCount) }}</b> lệnh sản xuất, <b>{{ number_format($productCount) }}</b> mã hàng
                    @if($lastImport) · cập nhật lần cuối {{ \Illuminate\Support\Carbon::parse($lastImport)->format('d/m/Y H:i') }} @endif
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('manual-labels.orders') }}" class="font-semibold text-blue-700 hover:underline">Xem danh sách lệnh sản xuất</a>
                    @if($orderCount || $productCount)
                        <form method="POST" action="{{ route('manual-labels.sources.destroy') }}" onsubmit="return confirm('Xóa toàn bộ dữ liệu đã tải lên?')">@csrf @method('DELETE')<button class="font-semibold text-rose-600 hover:underline">Xóa toàn bộ</button></form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>