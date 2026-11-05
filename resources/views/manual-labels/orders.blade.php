<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">In Nhãn Tay / Truy xuất đơn hàng</h2>
                <p class="mt-1 text-xs text-slate-500">Nhập mã đơn hàng để xem các dòng vị thuốc, bấm In để sang trang in nhãn.</p>
            </div>
            <a href="{{ route('manual-labels.create') }}" class="text-[11px] font-semibold text-blue-700 hover:underline">In nhãn nhập tay</a>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-6xl px-2 space-y-3">
            <form method="GET" class="flex flex-wrap items-center gap-2 border border-slate-300 bg-white p-3">
                <label class="text-[10px] font-bold uppercase text-slate-600">Mã đơn hàng</label>
                <input name="code" value="{{ $code }}" placeholder="VD: VT26010001" autofocus class="h-8 w-56 border border-slate-300 px-2 text-xs uppercase focus:border-blue-500 focus:ring-0">
                <button class="h-8 bg-blue-700 px-4 text-[11px] font-bold uppercase text-white hover:bg-blue-600">Truy xuất</button>
            </form>

            @if(! $hasData)
                <div class="border-l-4 border-amber-500 bg-amber-50 p-3 text-xs text-amber-900">Chưa có dữ liệu đơn hàng. Vào <a href="{{ route('manual-labels.import.form') }}" class="font-semibold underline">Import Excel</a> để tải file lên.</div>
            @elseif($code !== '' && $lines->isEmpty())
                <div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">
                    Không tìm thấy đơn hàng <b>{{ $code }}</b>.
                    @if($suggestions->isNotEmpty())
                        Có thể là:
                        @foreach($suggestions as $s)<a href="{{ route('manual-labels.orders', ['code' => $s->order_code]) }}" class="ml-1 font-semibold underline">{{ $s->order_code }}</a>@endforeach
                    @endif
                </div>
            @endif

            @if($order)
                <div class="grid gap-x-6 gap-y-1 border border-slate-300 bg-white p-3 text-xs sm:grid-cols-3">
                    <div><span class="text-slate-500">Mã ĐH:</span> <b>{{ $order->order_code }}</b></div>
                    <div><span class="text-slate-500">Khách hàng:</span> <b>{{ $order->customer }}</b></div>
                    <div><span class="text-slate-500">Loại ĐH:</span> <b>{{ $order->order_type }}</b> @if($order->customer_type)<span class="text-slate-500">({{ $order->customer_type }})</span>@endif</div>
                    <div><span class="text-slate-500">Ngày nhận đơn:</span> <b>{{ $order->received_date?->format('d/m/Y') }}</b></div>
                    <div><span class="text-slate-500">Ngày giao:</span> <b>{{ $order->delivery_date }}</b></div>
                    <div><span class="text-slate-500">Số dòng:</span> <b>{{ $lines->count() }}</b></div>
                </div>

                <div class="overflow-x-auto border border-slate-300 bg-white">
                    <table class="w-full min-w-[1000px] text-left text-[11px]">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                            <tr class="whitespace-nowrap">
                                <th class="px-2 py-1.5">Stt</th><th class="px-2 py-1.5">Tên vị thuốc</th><th class="px-2 py-1.5">Tên khoa học</th><th class="px-2 py-1.5">Nguồn gốc</th>
                                <th class="px-2 py-1.5 text-right">SL</th><th class="px-2 py-1.5 text-right">QCĐG</th><th class="px-2 py-1.5">Số lô</th><th class="px-2 py-1.5">NSX</th><th class="px-2 py-1.5">HSD</th><th class="px-2 py-1.5 text-right">In</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($lines as $line)
                                @php $info = $catalog[\App\Models\ManualLabelCatalog::key($line->name)] ?? null; @endphp
                                <tr class="hover:bg-slate-50/60">
                                        <td class="px-2 py-1 text-slate-500">{{ $line->line_no }}</td>
                                        <td class="px-2 py-1 font-semibold text-slate-800">{{ $line->name }}@if($line->processing || $line->note)<div class="text-[10px] font-normal text-slate-500">{{ $line->processing }} {{ $line->note ? '· ' . $line->note : '' }}</div>@endif</td>
                                        <td class="px-2 py-1 italic text-slate-600">{{ $info?->scientific_name }}</td>
                                        <td class="px-2 py-1 text-slate-600">{{ $info?->origin ?: $line->origin_code }}</td>
                                        <td class="whitespace-nowrap px-2 py-1 text-right text-slate-700">{{ rtrim(rtrim(number_format((float) $line->quantity, 3, '.', ''), '0'), '.') }} {{ $line->unit }}</td>
                                        <td class="whitespace-nowrap px-2 py-1 text-right text-slate-700">{{ $line->netWeight() }}</td>
                                        <td class="px-2 py-1 font-mono text-slate-600">{{ $line->batch ?: '—' }}</td>
                                        <td class="whitespace-nowrap px-2 py-1 text-slate-600">{{ $line->mfg_date?->format('d/m/Y') }}</td>
                                        <td class="whitespace-nowrap px-2 py-1 text-slate-600">{{ $line->exp_date?->format('d/m/Y') }}</td>
                                        <td class="px-2 py-1 text-right">
                                            @if($line->batch)
                                                <a href="{{ route('manual-labels.create', ['line' => $line->id]) }}" class="px-1.5 text-[11px] font-bold text-blue-700 hover:underline">In nhãn</a>
                                            @else
                                                <span class="text-[10px] text-slate-400">Chưa có số lô</span>
                                            @endif
                                        </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-[11px] text-slate-500">Bấm In nhãn để sang trang in nhãn tay với thông tin đã điền sẵn; bổ sung các ô còn thiếu (SĐK/SCB, tiêu chuẩn, bảo quản, mã QR…), chọn số lượng rồi in. Số nhãn mặc định theo "Số lượng gói thành phẩm". Mỗi lần in được lưu vào Lịch sử &amp; giao nhãn.</p>
            @endif
        </div>
    </div>
</x-app-layout>
