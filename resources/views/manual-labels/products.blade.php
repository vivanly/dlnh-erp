<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Mã hàng</h2>
                <p class="mt-1 text-xs text-slate-500">Thông tin sản phẩm (tên khoa học, nguồn gốc, số đăng ký…) dùng để tự điền khi in nhãn từ lệnh sản xuất.</p>
            </div>
            <a href="{{ route('manual-labels.products.create') }}" class="rounded-md bg-blue-700 px-3.5 py-2 text-[11px] font-bold uppercase text-white hover:bg-blue-600">+ Thêm mã hàng</a>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-7xl px-2 space-y-3">
            @if(session('success'))<div class="border border-emerald-200 bg-emerald-50 p-2 text-xs text-emerald-800">{{ session('success') }}</div>@endif
            <form method="GET" class="flex flex-wrap items-center gap-2 border border-slate-300 bg-white p-3">
                <input name="q" value="{{ $q }}" placeholder="Tên hàng, tên khoa học, số đăng ký" class="h-8 w-80 border border-slate-300 px-2 text-xs focus:border-blue-500 focus:ring-0">
                <button class="h-8 bg-blue-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-blue-600">Tìm</button>
                @if($q !== '')<a href="{{ route('manual-labels.products') }}" class="text-[11px] font-semibold text-slate-500 hover:underline">Bỏ lọc</a>@endif
            </form>
            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full min-w-[1000px] text-left text-[11px]">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr class="whitespace-nowrap"><th class="px-2 py-1.5 text-right">STT</th><th class="px-2 py-1.5">Tên hàng</th><th class="px-2 py-1.5">Chủng loại</th><th class="px-2 py-1.5">Tên khoa học</th><th class="px-2 py-1.5">Tiêu chuẩn</th><th class="px-2 py-1.5">Nguồn gốc</th><th class="px-2 py-1.5">Bộ phận dùng</th><th class="px-2 py-1.5">Số đăng ký</th><th class="px-2 py-1.5 text-right">Thao tác</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $p)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-2 py-1 text-right text-slate-400">{{ $products->firstItem() + $loop->index }}</td><td class="px-2 py-1 font-semibold text-slate-800">{{ $p->name }}</td>
                                <td class="px-2 py-1 text-slate-600">{{ $p->category }}</td>
                                <td class="px-2 py-1 italic text-slate-600">{{ $p->scientific_name }}</td>
                                <td class="px-2 py-1 text-slate-600">{{ $p->standard }}</td>
                                <td class="px-2 py-1 text-slate-600">{{ $p->origin }}</td>
                                <td class="px-2 py-1 text-slate-600">{{ $p->part_used }}</td>
                                <td class="px-2 py-1 font-mono text-slate-600">{{ $p->registration }}</td>
                                <td class="whitespace-nowrap px-2 py-1 text-right">
                                    <a href="{{ route('manual-labels.products.edit', $p) }}" class="px-1.5 text-[11px] font-bold text-slate-600 hover:underline">Sửa</a>
                                    <form method="POST" action="{{ route('manual-labels.products.destroy', $p) }}" class="inline" onsubmit="return confirm('Xóa mã hàng {{ $p->name }}?')">@csrf @method('DELETE')<button class="px-1.5 text-[11px] font-bold text-rose-600 hover:underline">Xóa</button></form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">Chưa có mã hàng nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2"><x-per-page-select />{{ $products->links() }}</div>
        </div>
    </div>
</x-app-layout>
