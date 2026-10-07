<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Nhãn đơn <span class="font-mono">{{ $order->order_code }}</span></h2><p class="mt-1 text-xs text-slate-500">{{ $order->customer->name ?? '---' }}</p></div>
            <a href="{{ route('labels.index') }}" class="px-3 py-1.5 bg-slate-200 text-xs font-bold uppercase text-slate-700 hover:bg-slate-300">Danh sách đơn</a>
        </div>
    </x-slot>

    <div class="py-3"><div class="max-w-none px-2 space-y-3">
        @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
            <div class="border border-slate-300 bg-white p-3"><div class="text-[10px] font-bold uppercase text-slate-500">Số nhãn theo thành phẩm</div><div class="mt-1 font-mono text-lg font-bold">{{ number_format($targetTotal) }}</div></div>
            <div class="border border-slate-300 bg-white p-3"><div class="text-[10px] font-bold uppercase text-slate-500">Tổng nhãn đã in</div><div class="mt-1 font-mono text-lg font-bold text-emerald-700">{{ number_format($printedTotal) }}</div></div>
            <div class="border border-slate-300 bg-white p-3"><div class="text-[10px] font-bold uppercase text-slate-500">Còn thiếu / in vượt</div><div class="mt-1 font-mono text-lg font-bold">{{ number_format(max(0, $targetTotal - $printedTotal)) }} / {{ number_format(max(0, $printedTotal - $targetTotal)) }}</div></div>
        </div>

        @if($canPrintLabels)
        <form method="POST" action="{{ route('labels.orders.print', $order) }}" class="space-y-3">
            @csrf
            <section class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full min-w-[900px] border-collapse text-left text-xs">
                    <thead><tr class="border-b border-slate-300 bg-slate-100 text-[10px] uppercase text-slate-600"><th class="p-2.5">Sản phẩm / QCĐG</th><th class="p-2.5">Lô đã chọn</th><th class="p-2.5">QA</th><th class="p-2.5">Mẫu Excel</th><th class="p-2.5 text-right">Thành phẩm</th><th class="p-2.5 text-right">Tem chuẩn</th><th class="p-2.5 text-right">Đã in</th><th class="p-2.5 text-right">Còn phải in</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($rows as $row)
                            <tr>
                                <td class="p-2.5"><strong>{{ $row['item']->product->name }}</strong><span class="block text-[10px] text-slate-500">QCĐG {{ $row['item']->packaging_spec ?: '---' }} · {{ $row['item']->product->unit }}</span></td>
                                <td class="p-2.5 font-mono">{{ $row['batch_code'] }}</td>
                                <td class="p-2.5">{{ $row['qa_approved'] ? 'Đã duyệt' : 'Chờ QA' }}</td>
                                <td class="p-2.5">{{ $row['label_type_name'] }}</td>
                                <td class="p-2.5 text-right font-mono">{{ number_format((float) $row['item']->finished_quantity, 2) }}</td>
                                <td class="p-2.5 text-right font-mono">{{ number_format($row['target']) }}</td>
                                <td class="p-2.5 text-right font-mono">{{ number_format($row['printed']) }}</td>
                                <td class="p-2.5 text-right font-mono font-bold text-emerald-700">{{ number_format($row['remaining']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-8 text-center text-slate-500">Chưa có dòng sản phẩm nào được QA phân bổ đủ lô theo số lượng đơn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            @if($rows->isNotEmpty())
                <section class="border border-slate-300 bg-white p-3">
                    <h3 class="text-xs font-bold uppercase text-slate-700">Số nhãn cần in theo lô</h3>
                    <p class="mt-1 text-[11px] text-slate-500">Chỉ cần QA đã chỉ định lô là có thể nhập số tem cần in lần này, kể cả khi chưa sản xuất đủ số lượng. Nếu chưa có số thành phẩm, số tem mặc định là 0. Số tem vượt phần còn phải in là in bổ sung và cần ghi lý do. Tối đa 1.000 tem mỗi lô.</p>
                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($rows as $row)
                            <div class="space-y-2 border border-slate-200 p-2 text-xs">
                                <label class="flex items-center justify-between gap-3">
                                    <span class="min-w-0"><strong class="block truncate">{{ $row['item']->product->name }}</strong><span class="block font-mono text-[10px] text-slate-500">{{ $row['batch_code'] }}</span></span>
                                    <input type="number" name="copies[{{ $row['allocation']->id }}]" value="{{ old('copies.'.$row['allocation']->id, (float) $row['item']->finished_quantity > 0 ? $row['remaining'] : 0) }}" min="0" max="1000" step="1" class="w-24 shrink-0 border-slate-300 text-right font-mono text-xs" aria-label="Số nhãn cần in lô {{ $row['batch_code'] }}">
                                </label>
                                <select name="standard[{{ $row['allocation']->id }}]" class="w-full border-slate-300 text-xs" aria-label="Tiêu chuẩn chất lượng">
                                    <option value="">-- Tiêu chuẩn chất lượng --</option>
                                    @foreach(['DĐVN V', 'DĐVN VI', 'TCCS'] as $standardOption)
                                        <option value="{{ $standardOption }}" @selected(old('standard.'.$row['allocation']->id) === $standardOption)>{{ $standardOption }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="storage[{{ $row['allocation']->id }}]" value="{{ old('storage.'.$row['allocation']->id, 'Để nơi khô ráo, thoáng mát, tránh mối mọt, nấm mốc.') }}" maxlength="255" placeholder="Bảo quản" class="w-full border-slate-300 text-xs">
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
            <div class="flex flex-col gap-3 border border-slate-300 bg-white p-3 sm:flex-row sm:items-end sm:justify-between">
                <label class="w-full text-xs font-semibold text-slate-700 sm:max-w-xl">Lý do in bổ sung<input name="additional_reason" value="{{ old('additional_reason') }}" maxlength="255" placeholder="Bắt buộc nếu số tem in vượt phần còn phải in" class="mt-1 w-full border-slate-300 text-xs"></label>
                <div class="flex flex-col gap-2 text-right sm:items-end">
                    <p class="text-[11px] text-slate-500">"In trực tiếp" mở bản xem trước nhãn (khổ A4 ngang, tối đa 4 nhãn mỗi trang); chỉ khi bấm "In" trong bản xem trước mới mở hộp thoại máy in và ghi nhận lượt in.</p>
                    <p id="label-print-error" class="hidden text-[11px] font-semibold text-rose-700"></p>
                    <div class="flex flex-wrap justify-end gap-2">
                        <button type="button" id="label-direct-print" data-url="{{ route('labels.orders.print-direct', $order) }}" class="inline-flex shrink-0 items-center gap-2 bg-slate-800 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-slate-700 disabled:opacity-50">In trực tiếp</button>
                        <button type="submit" class="inline-flex shrink-0 items-center gap-2 bg-emerald-700 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-800">Tải mẫu Excel để in</button>
                    </div>
                </div>
            </div>
        </form>
        @else
            <p class="border border-slate-300 bg-white p-3 text-xs text-slate-500">Chỉ xem. Quyền in nhãn dành cho bộ phận Sản xuất và IT.</p>
        @endif
        @if($canPrintLabels)
        <div id="label-preview" class="fixed inset-0 z-50 hidden flex-col bg-slate-900/80 p-4">
            <div class="mb-2 flex items-center justify-between gap-3 text-white">
                <p class="text-xs">Xem trước nhãn. Chỉ khi bấm "In" mới được tính là đã in.</p>
                <div class="flex gap-2">
                    <button type="button" id="label-preview-print" class="bg-emerald-700 px-4 py-2 text-xs font-bold uppercase hover:bg-emerald-800 disabled:opacity-50">In</button>
                    <button type="button" id="label-preview-close" class="bg-slate-600 px-4 py-2 text-xs font-bold uppercase hover:bg-slate-500">Đóng</button>
                </div>
            </div>
            <p id="label-preview-error" class="mb-2 hidden text-xs font-semibold text-rose-300"></p>
            <iframe id="label-print-frame" class="min-h-0 flex-1 bg-white" title="Xem trước nhãn"></iframe>
        </div>
        <script>
            (() => {
                const button = document.getElementById('label-direct-print');
                if (!button) return;
                const form = button.closest('form');
                const error = document.getElementById('label-print-error');
                const overlay = document.getElementById('label-preview');
                const frame = document.getElementById('label-print-frame');
                const printButton = document.getElementById('label-preview-print');
                const previewError = document.getElementById('label-preview-error');

                const request = async (preview) => {
                    const data = new FormData(form);
                    if (preview) data.append('preview', '1');
                    const response = await fetch(button.dataset.url, {
                        method: 'POST',
                        body: data,
                        headers: { 'Accept': 'text/html, application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        let message = 'Không tạo được nhãn để in.';
                        try { message = (await response.json()).message || message; } catch (e) {}
                        throw new Error(message);
                    }
                    return response.text();
                };

                button.addEventListener('click', async () => {
                    error.classList.add('hidden');
                    button.disabled = true;
                    try {
                        const frameLoaded = new Promise((resolve) => frame.addEventListener('load', resolve, { once: true }));
                        frame.srcdoc = await request(true);
                        await frameLoaded;
                        if (!frame.contentWindow.labelQrReady) {
                            throw new Error('Không tải được chức năng tạo mã QR. Hãy tải lại trang rồi thử lại.');
                        }
                        await frame.contentWindow.labelQrReady;
                        previewError.classList.add('hidden');
                        overlay.classList.remove('hidden');
                        overlay.classList.add('flex');
                    } catch (e) {
                        error.textContent = e.message;
                        error.classList.remove('hidden');
                    } finally {
                        button.disabled = false;
                    }
                });

                document.getElementById('label-preview-close').addEventListener('click', () => {
                    overlay.classList.add('hidden');
                    overlay.classList.remove('flex');
                });

                printButton.addEventListener('click', async () => {
                    previewError.classList.add('hidden');
                    printButton.disabled = true;
                    try {
                        await request(false);
                        if (!frame.contentWindow.labelQrReady) {
                            throw new Error('Không tải được chức năng tạo mã QR. Hãy tải lại trang rồi thử lại.');
                        }
                        await frame.contentWindow.labelQrReady;
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                        setTimeout(() => window.location.reload(), 1500);
                    } catch (e) {
                        previewError.textContent = e.message;
                        previewError.classList.remove('hidden');
                        printButton.disabled = false;
                    }
                });
            })();
        </script>
        @endif

        <section class="overflow-x-auto border border-slate-300 bg-white">
            <h3 class="border-b border-slate-300 bg-slate-100 px-3 py-2 text-xs font-bold uppercase text-slate-700">Lịch sử in nhãn</h3>
            <table class="w-full min-w-[700px] border-collapse text-left text-xs">
                <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500"><th class="p-2.5">Thời điểm</th><th class="p-2.5">Sản phẩm / lô</th><th class="p-2.5">Loại lượt in</th><th class="p-2.5 text-right">Số tem</th><th class="p-2.5">Lý do bổ sung</th><th class="p-2.5">Người in</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($history as $print)
                        @php
                            $batch = $print->allocation?->supplierBatch ?? $print->allocation?->finishedBatch;
                            $batchCode = $batch?->batch_number;
                        @endphp
                        <tr><td class="p-2.5">{{ $print->created_at?->format('d/m/Y H:i') }}</td><td class="p-2.5">{{ $print->orderItem->product->name ?? '---' }} · <span class="font-mono">{{ $batchCode ?? '---' }}</span></td><td class="p-2.5">{{ $print->print_kind === 'additional' ? 'In bổ sung' : 'Theo thành phẩm' }}</td><td class="p-2.5 text-right font-mono font-semibold">{{ number_format($print->copies_count) }}</td><td class="p-2.5">{{ $print->reason ?: '---' }}</td><td class="p-2.5">{{ $print->printedBy->name ?? '---' }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="p-5 text-center text-slate-500">Chưa có lượt in nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div></div>
</x-app-layout>