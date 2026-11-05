<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Lịch sử &amp; giao nhãn</h2>
                <p class="mt-1 text-xs text-slate-500">Theo dõi số nhãn đã in, đã giao và còn lại của từng lần in.</p>
            </div>
            <a href="{{ route('manual-labels.create') }}" class="rounded-md bg-blue-700 px-3.5 py-2 text-[11px] font-bold uppercase text-white shadow-sm hover:bg-blue-600">+ In nhãn mới</a>
        </div>
    </x-slot>

    @php
        $btn = 'inline-flex h-6 items-center justify-center rounded px-1.5 text-[11px] font-semibold transition';
        $fieldCls = 'h-6 rounded border border-slate-300 px-2 py-0 text-[11px] leading-none focus:border-blue-500 focus:ring-0';
    @endphp

    <div class="py-3">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">{{ $errors->first() }}</div>@endif

            <div class="flex flex-wrap items-center gap-x-5 gap-y-1 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-[11px] text-slate-500">
                <span>Đã in: <strong class="text-slate-800">{{ number_format($totals['printed']) }}</strong></span>
                <span>Đã giao: <strong class="text-emerald-700">{{ number_format($totals['delivered']) }}</strong></span>
                <span>Còn lại: <strong class="text-amber-600">{{ number_format(max(0, $totals['printed'] - $totals['delivered'])) }}</strong></span>
            </div>
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full min-w-[1000px] text-left text-[11px]">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr class="whitespace-nowrap">
                            <th class="px-2.5 py-1.5">Thời gian</th>
                            <th class="px-2.5 py-1.5">Mã ĐH</th>
                            <th class="px-2.5 py-1.5">Người in</th>
                            <th class="px-2.5 py-1.5">Sản phẩm</th>
                            <th class="px-2.5 py-1.5">Số lô</th>
                            <th class="px-2.5 py-1.5">Loại nhãn</th>
                            <th class="px-2.5 py-1.5 text-right">Đã in</th>
                            <th class="px-2.5 py-1.5 text-right">Đã giao</th>
                            <th class="px-2.5 py-1.5 text-right">Còn lại</th>
                            <th class="px-2.5 py-1.5">Trạng thái</th>
                            <th class="px-2.5 py-1.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    @forelse($prints as $print)
                        @php $done = $print->remaining_count === 0; @endphp
                        <tbody x-data="{ open: false, log: false }" class="border-t border-slate-100">
                            <tr class="whitespace-nowrap hover:bg-slate-50/60">
                                <td class="px-2.5 py-1 font-mono text-slate-700">{{ $print->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-2.5 py-1 font-mono font-semibold text-slate-700">{{ $print->order_code ?: '—' }}</td>
                                <td class="px-2.5 py-1 text-slate-600">{{ $print->printedBy->name ?? '---' }}</td>
                                <td class="max-w-[220px] truncate px-2.5 py-1 font-semibold text-slate-800" title="{{ $print->product_name }}{{ $print->note ? ' — ' . $print->note : '' }}">{{ $print->product_name }}</td>
                                <td class="max-w-[140px] truncate px-2.5 py-1 font-mono text-slate-600" title="{{ $print->batch }}">{{ $print->batch }}</td>
                                <td class="px-2.5 py-1 text-slate-600">{{ $labelTypes[$print->label_type] ?? $print->label_type }}</td>
                                <td class="px-2.5 py-1 text-right font-mono">{{ number_format($print->copies_count) }}</td>
                                <td class="px-2.5 py-1 text-right font-mono">{{ number_format($print->delivered_count) }}</td>
                                <td class="px-2.5 py-1 text-right font-mono font-semibold {{ $done ? 'text-emerald-700' : 'text-amber-600' }}">{{ number_format($print->remaining_count) }}</td>
                                <td class="px-2.5 py-1">
                                    @if($done)<span class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold text-emerald-700">Đã giao hết</span>
                                    @elseif($print->delivered_count > 0)<span class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">Giao một phần</span>
                                    @else<span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600">Chưa giao</span>@endif
                                </td>
                                <td class="px-2.5 py-1">
                                    <div class="flex items-center justify-end gap-1">
                                        @unless($done)<button type="button" @click="open = !open; log = false" class="{{ $btn }} text-emerald-600 hover:text-emerald-800 hover:underline">Giao nhãn</button>@endunless
                                        @if($print->deliveries->isNotEmpty())<button type="button" @click="log = !log; open = false" class="{{ $btn }} text-slate-500 hover:text-slate-800 hover:underline">Đã giao ({{ $print->deliveries->count() }})</button>@endif
                                        <form method="POST" action="{{ route('manual-labels.reprint', $print) }}" target="_blank">@csrf<button class="{{ $btn }} text-blue-600 hover:text-blue-800 hover:underline">In lại</button></form>
                                        <form method="POST" action="{{ route('manual-labels.destroy', $print) }}" onsubmit="return confirm('Xóa lần in này{{ $print->delivered_count > 0 ? ' cùng lịch sử giao nhãn' : '' }}? Không thể hoàn tác.')">@csrf @method('DELETE')<button class="{{ $btn }} text-rose-600 hover:text-rose-800 hover:underline">Xóa</button></form>
                                    </div>
                                </td>
                            </tr>
                            @unless($done)
                                <tr x-show="open" x-cloak class="bg-emerald-50/60">
                                    <td colspan="11" class="px-2.5 py-1">
                                        <form method="POST" action="{{ route('manual-labels.deliver', $print) }}" class="flex flex-wrap items-center gap-1.5">
                                            @csrf
                                            <input type="number" name="quantity" min="1" max="{{ $print->remaining_count }}" value="{{ $print->remaining_count }}" title="Tối đa {{ $print->remaining_count }}" class="w-16 {{ $fieldCls }}" required>
                                            <input name="received_by" placeholder="Người nhận" class="w-36 {{ $fieldCls }}">
                                            <input name="note" placeholder="Ghi chú" class="w-48 {{ $fieldCls }}">
                                            <button class="{{ $btn }} bg-blue-700 text-white hover:bg-blue-600">Xác nhận giao</button>
                                            <button type="button" @click="open = false" class="{{ $btn }} border border-slate-300 text-slate-600 hover:bg-white">Hủy</button>
                                        </form>
                                    </td>
                                </tr>
                            @endunless
                            @if($print->deliveries->isNotEmpty())
                                <tr x-show="log" x-cloak class="bg-slate-50">
                                    <td colspan="11" class="px-2.5 py-1 text-[11px] text-slate-600">
                                        @foreach($print->deliveries as $delivery)
                                            <div><strong class="text-slate-800">{{ number_format($delivery->quantity) }} nhãn</strong> · {{ $delivery->delivered_at->format('d/m/Y H:i') }}@if($delivery->received_by) · Nhận: {{ $delivery->received_by }}@endif · Giao: {{ $delivery->deliveredBy->name ?? '---' }}@if($delivery->note) · <em>{{ $delivery->note }}</em>@endif</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody><tr><td colspan="11" class="px-4 py-12 text-center text-slate-500">Chưa có lần in nhãn tay nào.</td></tr></tbody>
                    @endforelse
                </table>
            </div>            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $prints->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
