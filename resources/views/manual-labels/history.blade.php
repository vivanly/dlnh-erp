<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Quản lý nhãn</h2>
                <p class="mt-1 text-xs text-slate-500">Theo dõi từng lần in: số nhãn in, thực xuất, nhãn trả và tồn. Tồn = In − Thực xuất + Nhãn trả.</p>
            </div>
            <a href="{{ route('manual-labels.create') }}" class="rounded-md bg-blue-700 px-3.5 py-2 text-[11px] font-bold uppercase text-white shadow-sm hover:bg-blue-600">+ In nhãn mới</a>
        </div>
    </x-slot>

    @php
        $btn = 'inline-flex h-6 items-center justify-center rounded px-1.5 text-[11px] font-semibold transition';
        $fieldCls = 'h-6 rounded border border-slate-300 px-2 py-0 text-[11px] leading-none focus:border-blue-500 focus:ring-0';
        $th = 'border border-slate-300 px-2 py-1.5';
        $td = 'border border-slate-200 px-2 py-1';
    @endphp

    <div class="py-3">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-800">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="rounded-md border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800">{{ $errors->first() }}</div>@endif

            <form method="GET" class="flex flex-wrap items-center gap-2 border border-slate-300 bg-white p-2">
                <input name="q" value="{{ $q }}" placeholder="Lệnh SX, mã nhãn, tên hàng, số lô, khách hàng" class="h-8 w-80 border border-slate-300 px-2 text-xs focus:border-blue-500 focus:ring-0">
                <select name="status" class="h-8 border border-slate-300 py-0 pl-2 pr-7 text-xs focus:border-blue-500 focus:ring-0">
                    <option value="">Tất cả</option>
                    <option value="stock" @selected($status === 'stock')>Còn tồn</option>
                    <option value="empty" @selected($status === 'empty')>Hết tồn</option>
                </select>
                <button class="h-8 bg-blue-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-blue-600">Lọc</button>
                @if($q !== '' || $status !== '')<a href="{{ route('manual-labels.history') }}" class="text-[11px] font-semibold text-slate-500 hover:underline">Bỏ lọc</a>@endif
                <div class="ml-auto flex flex-wrap items-center gap-x-5 text-[11px] text-slate-500">
                    <span>Số nhãn in: <strong class="text-slate-800">{{ number_format($totals['printed']) }}</strong></span>
                    <span>Thực xuất: <strong class="text-emerald-700">{{ number_format($totals['delivered']) }}</strong></span>
                    <span>Nhãn trả: <strong class="text-slate-800">{{ number_format($totals['returned']) }}</strong></span>
                    <span>Tồn: <strong class="text-amber-600">{{ number_format($totals['stock']) }}</strong></span>
                </div>
            </form>

            <div class="border border-slate-300 bg-white">
                <table class="w-full table-auto border-collapse text-left text-[11px] leading-tight">
                    <thead class="bg-slate-100 text-[10px] uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="{{ $th }} text-right">STT</th>
                            <th class="{{ $th }}">Lệnh SX</th>
                            <th class="{{ $th }}">VT/DL</th>
                            <th class="{{ $th }} text-right">Số nhãn in</th>
                            <th class="{{ $th }}">Quy cách</th>
                            <th class="{{ $th }}">Số lô</th>
                            <th class="{{ $th }} text-right bg-yellow-100">Thực xuất</th>
                            <th class="{{ $th }} text-right">Nhãn trả</th>
                            <th class="{{ $th }} text-right">Tồn</th>
                            <th class="{{ $th }}">Khách hàng</th>
                            <th class="{{ $th }} text-right">Khối lượng</th>
                            <th class="{{ $th }}">Ngày SX / Hạn SD</th><th class="{{ $th }}">Số ĐK/Số GPNK</th><th class="{{ $th }}">Ngày in / Người in</th>
                            <th class="{{ $th }} text-right">Thao tác</th>
                        </tr>
                    </thead>
                    @forelse($prints as $print)
                        @php $empty = $print->remaining_count === 0; @endphp
                        <tbody x-data="{ form: null, log: false }">
                            <tr class="align-top odd:bg-white even:bg-slate-50/70 hover:bg-blue-50/40">
                                <td class="{{ $td }} text-right text-slate-400">{{ $prints->firstItem() + $loop->index }}</td>
                                <td class="{{ $td }} font-mono font-semibold text-slate-700">{{ $print->order_code ?: '—' }}@if($print->label_code)<span class="ml-1 font-normal text-slate-500">{{ $print->label_code }}</span>@endif</td>
                                <td class="{{ $td }} font-semibold text-slate-800" title="{{ $print->note }}">{{ $print->product_name }}</td>
                                <td class="{{ $td }} text-right font-mono">{{ number_format($print->copies_count) }}</td>
                                <td class="{{ $td }}">{{ $print->net_weight }}</td>
                                <td class="{{ $td }} break-all font-mono">{{ $print->batch }}</td>
                                <td class="{{ $td }} bg-yellow-50 text-right font-mono">{{ number_format($print->delivered_count) }}</td>
                                <td class="{{ $td }} text-right font-mono">{{ $print->returned_count ? number_format($print->returned_count) : '' }}</td>
                                <td class="{{ $td }} text-right font-mono font-semibold {{ $empty ? 'text-emerald-700' : 'text-amber-600' }}" title="{{ $empty ? 'Đã giao hết' : 'Còn tồn' }}">{{ number_format($print->remaining_count) }}</td>
                                <td class="{{ $td }}">{{ $print->customer }}</td>
                                <td class="{{ $td }} text-right">{{ $print->total_weight }}</td>
                                <td class="{{ $td }}">{{ $print->mfg_date }}<br><span class="text-slate-500">{{ $print->exp_date }}</span></td><td class="{{ $td }} break-all font-mono">{{ $print->registration }}</td><td class="{{ $td }}">{{ $print->created_at->format('d/m/Y') }}<br><span class="text-slate-500">{{ $print->printedBy->name ?? '---' }}</span></td>
                                <td class="{{ $td }}">
                                    <div class="flex flex-wrap items-center justify-end gap-x-1 gap-y-0.5">
                                        @unless($empty)<button type="button" @click="form = form === 'out' ? null : 'out'; log = false" class="{{ $btn }} text-emerald-600 hover:underline">Xuất</button>@endunless
                                        @if($print->returnable_count > 0)<button type="button" @click="form = form === 'return' ? null : 'return'; log = false" class="{{ $btn }} text-amber-600 hover:underline">Trả</button>@endif
                                        @if($print->deliveries->isNotEmpty())<button type="button" @click="log = !log; form = null" class="{{ $btn }} text-slate-500 hover:underline">Nhật ký ({{ $print->deliveries->count() }})</button>@endif
                                        <form method="POST" action="{{ route('manual-labels.reprint', $print) }}" target="_blank">@csrf<button class="{{ $btn }} text-blue-600 hover:underline">In lại</button></form>
                                        <form method="POST" action="{{ route('manual-labels.destroy', $print) }}" onsubmit="return confirm('Xóa lần in này{{ $print->delivered_count > 0 ? ' cùng nhật ký xuất/trả nhãn' : '' }}? Không thể hoàn tác.')">@csrf @method('DELETE')<button class="{{ $btn }} text-rose-600 hover:underline">Xóa</button></form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="form" x-cloak class="bg-emerald-50/60">
                                <td colspan="15" class="px-2.5 py-1">
                                    <form method="POST" action="{{ route('manual-labels.deliver', $print) }}" class="flex flex-wrap items-center gap-1.5">
                                        @csrf
                                        <input type="hidden" name="kind" :value="form">
                                        <span class="text-[11px] font-semibold" x-text="form === 'return' ? 'Nhãn trả:' : 'Thực xuất:'"></span>
                                        <input type="number" name="quantity" min="1" :max="form === 'return' ? {{ $print->returnable_count }} : {{ $print->remaining_count }}" value="{{ $print->remaining_count ?: 1 }}" class="w-20 {{ $fieldCls }}" required>
                                        <input name="customer" value="{{ $print->customer }}" placeholder="Khách hàng" class="w-56 {{ $fieldCls }}">
                                        <input name="received_by" placeholder="Người nhận" class="w-36 {{ $fieldCls }}">
                                        <input name="note" placeholder="Ghi chú" class="w-48 {{ $fieldCls }}">
                                        <button class="{{ $btn }} bg-blue-700 text-white hover:bg-blue-600">Xác nhận</button>
                                        <button type="button" @click="form = null" class="{{ $btn }} border border-slate-300 text-slate-600 hover:bg-white">Hủy</button>
                                    </form>
                                </td>
                            </tr>
                            @if($print->deliveries->isNotEmpty())
                                <tr x-show="log" x-cloak class="bg-slate-50">
                                    <td colspan="15" class="px-2.5 py-1 text-[11px] text-slate-600">
                                        @foreach($print->deliveries as $delivery)
                                            <div><strong class="{{ $delivery->kind === 'return' ? 'text-amber-700' : 'text-slate-800' }}">{{ $delivery->kind === 'return' ? 'Trả' : 'Xuất' }} {{ number_format($delivery->quantity) }} nhãn</strong> · {{ $delivery->delivered_at->format('d/m/Y H:i') }}@if($delivery->received_by) · Nhận: {{ $delivery->received_by }}@endif · Thực hiện: {{ $delivery->deliveredBy->name ?? '---' }}@if($delivery->note) · <em>{{ $delivery->note }}</em>@endif</div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody><tr><td colspan="15" class="px-4 py-12 text-center text-slate-500">Chưa có lần in nhãn tay nào.</td></tr></tbody>
                    @endforelse
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $prints->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
