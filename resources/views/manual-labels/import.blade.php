<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Import Excel</h2>
                <p class="mt-1 text-xs text-slate-500">Tải file Excel (sheet PKN) lên hệ thống; khi in nhãn tìm tên/số lô để tự điền thông tin.</p>
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
                    <button class="h-8 rounded bg-emerald-600 px-3 text-[11px] font-bold uppercase text-white hover:bg-emerald-500">Tải lên & đọc dữ liệu</button>
                </form>
                <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">Hệ thống đọc sheet <code>PKN</code> (VT/DL, Tên, Số lô, Ngày sản xuất, Hạn dùng, Giấy phép) và nối theo tên với sheet <code>TT</code> (Tên khoa học, Bộ phận dùng, Nguồn gốc). Tải lại file mới sẽ cập nhật dữ liệu theo (Số lô + Tên). Hiện có <b>{{ number_format($total) }}</b> dòng.</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <form method="GET" class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Tìm tên hoặc số lô" class="h-7 w-56 border border-slate-300 px-2 text-xs"><button class="h-7 px-2 text-[11px] font-semibold text-blue-700 hover:underline">Tìm</button></form>
                @if($total)
                    <form method="POST" action="{{ route('manual-labels.sources.destroy') }}" onsubmit="return confirm('Xóa toàn bộ dữ liệu đã tải lên?')">@csrf @method('DELETE')<button class="text-[11px] font-semibold text-rose-600 hover:underline">Xóa toàn bộ</button></form>
                @endif
            </div>

            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[900px] text-left text-[11px]">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr class="whitespace-nowrap"><th class="px-2.5 py-1.5">VT/DL</th><th class="px-2.5 py-1.5">Tên</th><th class="px-2.5 py-1.5">Tên khoa học</th><th class="px-2.5 py-1.5">Bộ phận</th><th class="px-2.5 py-1.5">Nguồn gốc</th><th class="px-2.5 py-1.5">Số lô</th><th class="px-2.5 py-1.5">NSX</th><th class="px-2.5 py-1.5">HSD</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sources as $s)
                            <tr class="whitespace-nowrap hover:bg-slate-50/60">
                                <td class="px-2.5 py-1 text-slate-600">{{ $s->kind }}</td>
                                <td class="px-2.5 py-1 font-semibold text-slate-800">{{ $s->name }}</td>
                                <td class="px-2.5 py-1 italic text-slate-600">{{ $s->scientific_name }}</td>
                                <td class="px-2.5 py-1 text-slate-600">{{ $s->part_used }}</td>
                                <td class="px-2.5 py-1 text-slate-600">{{ $s->origin ?: $s->origin_code }}</td>
                                <td class="px-2.5 py-1 font-mono text-slate-600">{{ $s->batch }}</td>
                                <td class="px-2.5 py-1 text-slate-600">{{ $s->mfg_date?->format('d/m/Y') }}</td>
                                <td class="px-2.5 py-1 text-slate-600">{{ $s->exp_date?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Chưa có dữ liệu. Tải file Excel lên để bắt đầu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2"><x-per-page-select />{{ $sources->links() }}</div>
        </div>
    </div>
</x-app-layout>