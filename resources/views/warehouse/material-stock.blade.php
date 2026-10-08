<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Tồn kho nguyên liệu thô &amp; phụ liệu</h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-2 bg-emerald-50 border border-emerald-300 text-emerald-700 text-xs font-semibold">{{ session('success') }}</div>@endif
            <form method="GET" class="bg-white border border-slate-300 p-3 flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Tìm tên hoặc mã..." class="text-xs border-slate-300 w-64">
                <select name="type" class="text-xs border-slate-300">
                    <option value="">Tất cả loại</option>
                    <option value="raw_material" @selected($type === 'raw_material')>Nguyên liệu thô</option>
                    <option value="accessory" @selected($type === 'accessory')>Phụ liệu</option>
                </select>
                <button class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold uppercase">Lọc</button>
                <a href="{{ route('warehouse.material-stock') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase">Xóa lọc</a>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2.5 border-r">Loại</th>
                            <th class="p-2.5 border-r">Mã</th>
                            <th class="p-2.5 border-r">Tên</th>
                            <th class="p-2.5 border-r">Lô NCC (nguyên liệu thô) / phụ liệu quản lý theo tổng</th>
                            <th class="p-2.5 border-r text-right">Đã nhập</th>
                            <th class="p-2.5 border-r text-right">Đã xuất</th>
                            <th class="p-2.5 text-right">Tồn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($rows as $row)
                            <tr>
                                <td class="p-2.5 border-r">{{ $row->type === 'accessory' ? 'Phụ liệu' : 'Nguyên liệu thô' }}</td>
                                <td class="p-2.5 border-r font-mono">{{ $row->item->sku }}</td>
                                <td class="p-2.5 border-r font-medium">{{ $row->item->name }}</td>
                                <td class="p-2.5 border-r">
                                    @forelse($row->lots as $lot)
                                        <span class="block font-mono {{ $lot->balance > 0 ? '' : 'text-slate-400 line-through' }}">{{ $lot->batch ?: '(không số lô)' }}: <b>{{ number_format($lot->balance, 2) }}</b> <span class="text-slate-500">(nhập {{ number_format($lot->received, 2) }}, xuất {{ number_format($lot->issued, 2) }}@if($lot->returned > 0), trả {{ number_format($lot->returned, 2) }}@endif)</span>@if($lot->exp_date) <span class="text-slate-500">HSD {{ \Illuminate\Support\Carbon::parse($lot->exp_date)->format('d/m/Y') }}</span>@endif</span>
                                    @empty
                                        <span class="text-slate-400">{{ $row->type === 'accessory' ? 'Theo tổng khối lượng' : '---' }}</span>
                                    @endforelse
                                </td>
                                <td class="p-2.5 border-r text-right font-mono">{{ number_format($row->in, 2) }} {{ $row->item->unit }}</td>
                                <td class="p-2.5 border-r text-right font-mono">{{ number_format($row->out, 2) }} {{ $row->item->unit }}</td>
                                <td class="p-2.5 text-right font-mono font-bold {{ $row->balance > 0 ? 'text-emerald-700' : 'text-slate-500' }}">{{ number_format($row->balance, 2) }} {{ $row->item->unit }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-4 text-center text-slate-500">Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <div class="p-3 bg-slate-100 border-b border-slate-300 font-bold text-xs uppercase text-slate-700">20 biến động gần nhất</div>
                <table class="w-full text-left border-collapse text-xs">
                    <thead><tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                        <th class="p-2 border-r">Thời gian</th><th class="p-2 border-r">Hàng hóa</th><th class="p-2 border-r">Nghiệp vụ</th><th class="p-2 border-r text-right">Số lượng</th><th class="p-2">Lô NCC / đợt nhập / ghi chú</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($movements as $movement)
                            <tr>
                                <td class="p-2 border-r font-mono">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="p-2 border-r">{{ $movement->catalog_item->name ?? '---' }}</td>
                                <td class="p-2 border-r">{{ ['RECEIVE_PURCHASE' => 'Nhập mua', 'REJECT_PURCHASE' => 'Trả NCC khi nhận', 'RETURN_SUPPLIER' => 'Đơn trả NCC', 'ISSUE_SALE' => 'Xuất bán', 'ISSUE_PRODUCTION' => 'Xuất sản xuất', 'RETURN_PRODUCTION' => 'Trả từ sản xuất'][$movement->movement_type] ?? $movement->movement_type }}</td>
                                <td class="p-2 border-r text-right font-mono {{ $movement->direction === 'out' ? 'text-rose-600' : ($movement->direction === 'in' ? 'text-emerald-700' : '') }}">{{ $movement->direction === 'out' ? '-' : ($movement->direction === 'in' ? '+' : '') }}{{ number_format((float) $movement->quantity, 2) }} {{ $movement->unit }}</td>
                                <td class="p-2">{{ $movement->batch_number ?: $movement->note }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>