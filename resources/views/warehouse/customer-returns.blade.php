<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Hàng khách trả · Khu cách ly</h2>
            <p class="mt-1 text-xs text-slate-500">Chỉ nhận trong 30 ngày từ khi giao. Hàng trả chưa cộng tồn cho đến khi QC chấp nhận nhập lại đúng lô.</p>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-none space-y-4 px-2">
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="space-y-3">
                <h3 class="text-xs font-bold uppercase text-slate-700">Đơn trong thời hạn nhận trả</h3>
                @forelse($orders as $order)
                    @php($returnableAllocations = $order->items->flatMap(fn ($item) => $item->lotAllocations)->filter(fn ($allocation) => (float) $allocation->returnable_quantity > 0))
                    @if($returnableAllocations->isNotEmpty())
                        <form method="POST" action="{{ route('warehouse.customer-returns.receive', $order) }}" class="border border-slate-300 bg-white">
                            @csrf
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 p-3">
                                <div><strong class="font-mono text-blue-700">{{ $order->order_code }}</strong> · {{ $order->customer->name ?? '---' }}<span class="ml-2 text-[10px] text-slate-500">Giao {{ $order->warehouse_confirmed_at?->format('d/m/Y H:i') }}</span></div>
                                <span class="text-[10px] text-amber-800">Tiếp nhận sẽ đưa hàng vào khu cách ly</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[700px] text-left text-xs">
                                    <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500"><th class="p-2">Sản phẩm</th><th class="p-2">Lô đã giao</th><th class="p-2 text-right">Còn được trả</th><th class="p-2 text-right">Số lượng nhận trả</th></tr></thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($order->items as $item)
                                            @foreach($item->lotAllocations->where('shipped_quantity', '>', 0) as $allocation)
                                                @if((float) $allocation->returnable_quantity > 0)
                                                    @php($batch = $allocation->finishedBatch ?? $allocation->supplierBatch)
                                                    <tr>
                                                        <td class="p-2">{{ $item->product->name ?? '---' }}<span class="block text-[10px] text-slate-500">{{ $item->product->unit ?? '' }}</span></td>
                                                        <td class="p-2 font-mono">{{ $batch->batch_number ?? 'Không rõ lô' }}</td>
                                                        <td class="p-2 text-right font-mono">{{ number_format((float) $allocation->returnable_quantity, 4) }}</td>
                                                        <td class="p-2 text-right"><input type="number" name="items[{{ $allocation->id }}][quantity]" min="0" max="{{ $allocation->returnable_quantity }}" step="0.0001" value="{{ old('items.'.$allocation->id.'.quantity', 0) }}" class="w-36 border-slate-300 text-right font-mono text-xs"></td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex flex-wrap items-end justify-between gap-3 p-3">
                                <label class="min-w-[240px] flex-1 text-[10px] font-semibold text-slate-600">Ghi chú tiếp nhận<textarea name="note" rows="2" maxlength="2000" class="mt-1 w-full border-slate-300 text-xs">{{ old('note') }}</textarea></label>
                                @if(auth()->user()->isWarehouseDepartment() || auth()->user()->isITDepartment())
                                    <button class="bg-amber-600 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-amber-700">Nhận vào khu cách ly</button>
                                @else
                                    <span class="text-[10px] text-slate-500">Chỉ Kho được tiếp nhận hàng trả.</span>
                                @endif
                            </div>
                        </form>
                    @endif
                @empty
                    <p class="border border-slate-300 bg-white p-4 text-xs text-slate-500">Không có đơn hoàn tất trong thời hạn 30 ngày.</p>
                @endforelse
            </section>

            <section class="border border-slate-300 bg-white">
                <div class="border-b border-slate-200 bg-slate-50 p-3">
                    <h3 class="text-xs font-bold uppercase text-slate-700">Phiếu hàng trả / quyết định QC</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1000px] text-left text-xs">
                        <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500"><th class="p-2">Phiếu / Đơn</th><th class="p-2">Sản phẩm / Lô</th><th class="p-2 text-right">SL</th><th class="p-2">Trạng thái</th><th class="p-2">Kết quả QC / xử lý</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($returnItems as $item)
                                @php($batch = $item->productionFinishedBatch ?? $item->supplierBatch)
                                <tr class="align-top">
                                    <td class="p-2"><strong class="font-mono">{{ $item->salesOrderReturn->order->order_code ?? '---' }}</strong><span class="block text-[10px] text-slate-500">Phiếu #{{ $item->sales_order_return_id }} · {{ $item->salesOrderReturn->received_at?->format('d/m/Y H:i') }}</span></td>
                                    <td class="p-2">{{ $item->orderItem->product->name ?? '---' }}<span class="block font-mono text-[10px] text-slate-500">{{ $batch->batch_number ?? '---' }}</span></td>
                                    <td class="p-2 text-right font-mono">{{ number_format((float) $item->quantity, 4) }}</td>
                                    <td class="p-2">{{ ['pending_inspection' => 'Chờ QC kiểm tra', 'restocked' => 'Đã nhập lại đúng lô', 'rework' => 'Chờ tái sản xuất', 'discarded' => 'Đã hủy'][$item->status] ?? $item->status }}</td>
                                    <td class="p-2">
                                        @if($item->status === 'pending_inspection' && (auth()->user()->isQCDepartment() || auth()->user()->isITDepartment()))
                                            <form method="POST" action="{{ route('warehouse.customer-returns.inspect', $item) }}" class="flex min-w-[440px] flex-wrap items-end gap-2">
                                                @csrf @method('PATCH')
                                                <label class="text-[10px]">Quyết định<select name="decision" required class="mt-1 block border-slate-300 text-xs"><option value="">-- Chọn --</option><option value="restock">Đạt · nhập lại tồn đúng lô</option><option value="rework">Tái sản xuất</option><option value="discard">Hủy</option></select></label>
                                                <label class="min-w-[170px] flex-1 text-[10px]">Ghi chú QC<input name="inspection_note" required maxlength="2000" class="mt-1 w-full border-slate-300 text-xs"></label>
                                                <button class="bg-emerald-700 px-3 py-1.5 text-[10px] font-bold uppercase text-white">Lưu quyết định</button>
                                            </form>
                                        @else
                                            <span>{{ $item->inspection_note ?: 'Chưa có kết luận' }}</span>
                                            @if($item->reworkProductionOrder)<span class="block font-mono text-[10px] text-blue-700">Lệnh tái sản xuất {{ $item->reworkProductionOrder->production_code }}</span>@endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="p-8 text-center text-slate-500">Chưa có phiếu hàng khách trả.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-200 p-3">{{ $returnItems->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
