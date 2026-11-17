<x-app-layout>
    @push('scripts') @vite('resources/js/app.js') @endpush
    <x-slot name="header">
        <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In nhãn tay / In theo lệnh sản xuất</h2>
    </x-slot>
    <div class="max-w-7xl space-y-3 px-2 py-3">
        <form method="GET" class="flex flex-wrap items-end gap-2 border border-slate-300 bg-white p-3">
            <label class="text-xs font-semibold">Lệnh sản xuất
                <select name="order" required class="block h-8 min-w-56 border-slate-300 text-xs">
                    <option value="">-- Chọn lệnh --</option>
                    @foreach($orderCodes as $orderCode)<option value="{{ $orderCode }}" @selected($code === $orderCode)>{{ $orderCode }}</option>@endforeach
                </select>
            </label>
            <button class="h-8 bg-blue-700 px-4 text-xs font-bold text-white">Chọn lệnh</button>
            <a href="{{ route('manual-labels.orders') }}" class="p-2 text-xs text-blue-700">Danh sách lệnh</a>
        </form>
        @if($errors->any())<div class="border border-rose-200 bg-rose-50 p-3 text-xs text-rose-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @unless($ready)<p class="border border-amber-300 bg-amber-50 p-3 text-xs font-semibold text-amber-900">Cơ sở dữ liệu chưa được nâng cấp mã nhãn. Chưa thể in theo lệnh; liên hệ quản trị viên.</p>@endunless
        @if($code !== '' && $rows->isEmpty())
            <p class="bg-amber-50 p-3 text-xs">Không tìm thấy lệnh sản xuất này.</p>
        @elseif($rows->isNotEmpty())
            <a href="{{ route('manual-labels.orders.create', ['production_order' => $code]) }}" class="inline-block text-xs font-semibold text-blue-700 hover:underline">+ Thêm dòng vào lệnh {{ $code }}</a>
            <form method="POST" action="{{ route('manual-labels.batch.store') }}" target="_blank" class="space-y-3"
                  x-data="{ counts: @js($rows->mapWithKeys(fn ($row) => [$row['order']->id => old('counts.' . $row['order']->id, $row['remaining'])])), get total() { return Object.values(this.counts).reduce((sum, n) => sum + (Number(n) || 0), 0); } }">
                @csrf
                <input type="hidden" name="production_order" value="{{ $code }}">
                <div class="overflow-x-auto border border-slate-300 bg-white">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50"><tr><th class="p-2">Mã nhãn</th><th class="p-2">Tên hàng / Số lô</th><th class="p-2">SL / QCĐG</th><th class="p-2">Cần / Đã in / Còn</th><th class="p-2">Số nhãn lần này</th></tr></thead>
                        <tbody class="divide-y">
                            @foreach($rows as $row)
                                @php($o = $row['order'])
                                <tr>
                                    <td class="p-2 font-mono">{{ $o->label_code }}</td>
                                    <td class="p-2">{{ $o->name }}<br><span class="text-slate-500">{{ $o->batch }} · {{ \App\Http\Controllers\ManualLabelController::LABEL_TYPES[$row['template']['label_type']] ?? 'Mẫu không hợp lệ' }}@if($row['template']['traditional_medicine_name']) · Tên vị thuốc cổ truyền @endif @if(! $o->label_type) · Theo mã hàng @endif</span></td>
                                    <td class="p-2">{{ \App\Services\ManualLabelOrders::normalizeWeight($o->quantity) ?? $o->quantity }} / {{ \App\Services\ManualLabelOrders::normalizeWeight($o->pack_spec) ?? $o->pack_spec }}
                                        @if($row['calculation'] && $row['calculation']['remainder_g'] > 0)
                                            <p class="text-amber-700">Còn {{ rtrim(rtrim(number_format($row['calculation']['remainder_g'], 6, '.', ''), '0'), '.') }} g cần xử lý/in tay.</p>
                                        @endif
                                    </td>
                                    <td class="p-2">
                                        @if($row['calculation']){{ number_format($row['calculation']['count']) }} / {{ number_format($row['printed']) }} / {{ number_format($row['remaining']) }}@else Không tính được @endif
                                        @if($row['error'])<p class="text-rose-700">{{ $row['error'] }} <a href="{{ route('manual-labels.orders.edit', $o) }}" class="underline">Sửa dòng</a> · <a href="{{ route('manual-labels.products', ['q' => $o->name]) }}" class="underline">Mã hàng</a></p>@endif
                                    </td>
                                    <td class="p-2"><input type="number" min="0" max="1000" name="counts[{{ $o->id }}]" value="{{ old('counts.' . $o->id, $row['remaining']) }}" x-model.number="counts[{{ $o->id }}]" required class="h-8 w-24 border-slate-300 text-xs"><p x-show="counts[{{ $o->id }}] > {{ $row['remaining'] }}" class="text-rose-700">Vượt số còn thiếu</p></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-slate-700">Tổng: <strong x-text="total"></strong> nhãn · <strong x-text="Math.floor(total / 4)"></strong> tờ đủ · <strong x-text="total % 4"></strong> nhãn trên tờ cuối. Tối đa 1.000 nhãn mỗi lần in.</p>
                <p class="text-xs text-amber-700">Hệ thống ghi nhận số lượng khi mở trang in, kể cả khi bạn hủy hộp thoại máy in. Kiểm tra lịch sử nếu cần điều chỉnh.</p>
                <label class="block text-xs"><input type="checkbox" name="allow_extra" value="1"> Tôi xác nhận nếu số lượng nhập vượt số nhãn còn thiếu.</label>
                @if(! $ready)
                    <p class="text-xs font-semibold text-rose-700">Cần nâng cấp cơ sở dữ liệu trước khi in.</p>
                @elseif($rows->contains(fn ($row) => $row['error']))
                    <p class="text-xs font-semibold text-rose-700">Cần sửa các dòng lỗi trước khi in lệnh.</p>
                @else
                    <div class="flex flex-wrap gap-2">
                        <button name="include_partial" value="0" class="bg-slate-700 px-4 py-2 text-xs font-bold text-white">Chỉ in tờ đủ 4 nhãn</button>
                        <button name="include_partial" value="1" class="bg-blue-700 px-4 py-2 text-xs font-bold text-white" x-show="total % 4 !== 0" @click="if (!confirm('In cả tờ cuối chưa đủ 4 nhãn?')) $event.preventDefault()">In cả tờ cuối chưa đủ</button>
                    </div>
                @endif
            </form>
        @endif
    </div>
</x-app-layout>
