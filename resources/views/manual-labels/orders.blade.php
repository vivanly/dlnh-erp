<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Lệnh sản xuất</h2>
                <p class="mt-1 text-xs text-slate-500">Tìm theo mã nhãn, lệnh SX, tên hàng hoặc số lô, rồi bấm In nhãn để sang trang in với thông tin đã điền sẵn.</p>
            </div>
            <div class="flex items-center gap-3"><a href="{{ route('manual-labels.create') }}" class="text-[11px] font-semibold text-blue-700 hover:underline">In nhãn nhập tay</a><a href="{{ route('manual-labels.orders.create') }}" class="rounded-md bg-blue-700 px-3.5 py-2 text-[11px] font-bold uppercase text-white hover:bg-blue-600">+ Thêm lệnh</a></div>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-7xl px-2 space-y-3">
            @if(session('success'))<div class="border border-emerald-200 bg-emerald-50 p-2 text-xs text-emerald-800">{{ session('success') }}</div>@endif
            <form method="GET" class="flex flex-wrap items-center gap-2 border border-slate-300 bg-white p-3">
                <input name="q" value="{{ $q }}" placeholder="Mã nhãn (NH001), lệnh SX, tên hàng, số lô" autofocus class="h-8 w-80 border border-slate-300 px-2 text-xs focus:border-blue-500 focus:ring-0">
                <button class="h-8 bg-blue-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-blue-600">Tìm</button>
                @if($q !== '')<a href="{{ route('manual-labels.orders') }}" class="text-[11px] font-semibold text-slate-500 hover:underline">Bỏ lọc</a>@endif
            </form>

            @if(! $hasData)
                <div class="border-l-4 border-amber-500 bg-amber-50 p-3 text-xs text-amber-900">Chưa có dữ liệu. Vào <a href="{{ route('manual-labels.import.form') }}" class="font-semibold underline">Import Excel</a> để tải file lên.</div>
            @endif

            <div class="border border-slate-300 bg-white">
                <table class="w-full table-auto text-left text-[11px] leading-tight">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-2 py-1.5 text-right">STT</th><th class="px-2 py-1.5">Mã nhãn</th><th class="px-2 py-1.5">Lệnh SX</th><th class="px-2 py-1.5">Ngày</th><th class="px-2 py-1.5">Tên hàng</th>
                            <th class="px-2 py-1.5">Số lô</th><th class="px-2 py-1.5">NSX / HSD</th><th class="px-2 py-1.5">QC ĐG</th>
                            <th class="px-2 py-1.5 text-right">SL</th><th class="px-2 py-1.5 text-right">Đã in / Cần in</th><th class="px-2 py-1.5">Trạng thái</th><th class="px-2 py-1.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($orders as $o)
                            @php
                                $done = (int) ($printed[$o->label_code] ?? 0);
                                $need = (int) $o->label_count;
                                $known = isset($products[\App\Models\ManualLabelProduct::key($o->name)]);
                            @endphp
                            <tr class="align-top hover:bg-slate-50/60">
                                <td class="px-2 py-1 text-right text-slate-400">{{ $orders->firstItem() + $loop->index }}</td><td class="px-2 py-1 font-mono font-semibold text-slate-700">{{ $o->label_code }}</td>
                                <td class="px-2 py-1 font-mono text-slate-600">{{ $o->production_order }}</td>
                                <td class="px-2 py-1 text-slate-600">{{ $o->order_date?->format('d/m/Y') }}</td>
                                <td class="px-2 py-1 font-semibold text-slate-800">{{ $o->name }}@unless($known)<span class="ml-1 text-[10px] font-normal text-rose-600" title="Tên hàng chưa có trong sheet MÃ HÀNG">chưa có mã hàng</span>@endunless</td>
                                <td class="px-2 py-1 break-all font-mono text-slate-600">{{ $o->batch ?: '—' }}</td>
                                <td class="px-2 py-1 text-slate-600">{{ $o->mfg_date?->format('d/m/Y') }}<br><span class="text-slate-500">{{ $o->exp_date?->format('d/m/Y') }}</span></td>
                                <td class="px-2 py-1 text-slate-600">{{ $o->pack_spec }}</td>
                                <td class="px-2 py-1 text-right text-slate-600">{{ $o->quantity }}</td>
                                <td class="px-2 py-1 text-right font-mono">{{ number_format($done) }} / {{ number_format($need) }}</td>
                                <td class="px-2 py-1">
                                    @if($done === 0)<span class="text-slate-400">Chưa in</span>
                                    @elseif($done >= $need)<span class="font-semibold text-emerald-700">Đã in</span>
                                    @else<span class="font-semibold text-amber-700">In thiếu {{ number_format($need - $done) }}</span>@endif
                                </td>
                                <td class="px-2 py-1 text-right"><div class="flex flex-wrap justify-end gap-x-1"><a href="{{ route('manual-labels.create', ['order' => $o->id]) }}" class="px-1.5 text-[11px] font-bold text-blue-700 hover:underline">In nhãn</a><a href="{{ route('manual-labels.orders.edit', $o) }}" class="px-1.5 text-[11px] font-bold text-slate-600 hover:underline">Sửa</a><form method="POST" action="{{ route('manual-labels.orders.destroy', $o) }}" class="inline" onsubmit="return confirm('Xóa lệnh {{ $o->label_code }}?')">@csrf @method('DELETE')<button class="px-1.5 text-[11px] font-bold text-rose-600 hover:underline">Xóa</button></form></div></td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="px-4 py-10 text-center text-slate-500">Không có lệnh sản xuất nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2"><x-per-page-select />{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
