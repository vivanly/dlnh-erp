<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">BTP dược liệu / TP vị thuốc chờ nhập kho</h2>
                <p class="mt-1 text-xs text-slate-500">Nhập số lượng thực nhận vào lô QA đã tạo. Khi nhập thành phẩm kế hoạch tháng, chọn lệnh tương ứng để ghi nhận nguồn nguyên liệu truy vết.</p>
            </div>
            <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">{{ $batches->total() }} lô chờ nhập</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif
            <form method="GET" class="bg-white border border-slate-300 p-3 flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã lô, lệnh hoặc sản phẩm" class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white">Tìm</button>
            </form>
            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full text-left text-xs">
                    <thead><tr class="border-b border-slate-300 bg-slate-100 text-[10px] uppercase text-slate-600"><th class="p-2.5">Lô BTP / TP</th><th class="p-2.5">Nguồn lô</th><th class="p-2.5 text-right">Lượng chờ nhập</th><th class="p-2.5">Trạng thái PKN</th><th class="p-2.5">SL nhận thực tế</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($batches as $batch)
                            @php
                                $usesAllocationCapacity = $batch->order_allocations_count > 0
                                    || ($batch->production_order_id && ! $batch->productionOrder?->monthlyPlanLine);
                                $authorizedQuantity = $usesAllocationCapacity
                                    ? min((float) $batch->planned_quantity, (float) $batch->initial_quantity)
                                    : (float) $batch->planned_quantity;
                                $maxReceivableQuantity = min(
                                    (float) $batch->pending_warehouse_quantity,
                                    max(0, $authorizedQuantity - (float) $batch->received_quantity),
                                );
                            @endphp
                            <tr>
                                <td class="p-2.5"><strong class="font-mono">{{ $batch->batch_number }}</strong><span class="block">{{ $batch->product->name }}</span><span class="block text-[10px] text-slate-500">{{ str_contains(strtoupper((string) $batch->product->classification), 'VT') ? 'Thành phẩm · vị thuốc' : 'Bán thành phẩm · dược liệu đã sơ chế' }}</span></td>
                                <td class="p-2.5 font-mono">@if($batch->orderAllocations->isNotEmpty()) @foreach($batch->orderAllocations as $allocation)<span class="block">{{ $allocation->productionOrder?->production_code }} · {{ number_format((float) $allocation->quantity, 4) }} {{ $batch->unit }}</span>@endforeach @elseif($batch->productionOrder) {{ $batch->productionOrder->production_code }} @else QA-created independent lot @endif</td>
                                <td class="p-2.5 text-right font-mono">{{ number_format($maxReceivableQuantity, 4) }} {{ $batch->unit }}<span class="block text-[10px] text-slate-500">Cỡ lô {{ number_format((float) $batch->planned_quantity, 4) }} · đã nhập {{ number_format((float) $batch->current_quantity, 4) }}</span></td>
                                <td class="p-2.5">
                                    <span class="block {{ $batch->qc_result === 'passed' ? 'text-emerald-700' : ($batch->qc_result === 'failed' ? 'text-rose-700' : 'text-slate-500') }}">{{ $batch->qc_result === 'passed' ? 'PKN Đạt' : ($batch->qc_result === 'failed' ? 'PKN Không đạt' : 'Chờ QC · không chặn nhập kho') }}</span>
                                </td>
                                <td class="p-2.5">
                                    @if($canManageWarehouse)
                                        @php
                                            $eligibleMonthlyOrders = $monthlyProductionOrders->filter(fn ($order) => (int) $order->product_id === (int) $batch->product_id);
                                            $linkedMonthlyOrder = $batch->productionOrder?->monthlyPlanLine ? $batch->productionOrder : null;
                                            $canLinkMonthlyOrder = $batch->production_order_id === null
                                                && (float) $batch->initial_quantity <= 0.000001
                                                && ! $batch->warehouse_received_at
                                                && $batch->sales_order_allocations_count === 0
                                                && $batch->inputs_count === 0
                                                && $batch->inventory_movements_count === 0
                                                && $batch->order_allocations_count === 0;
                                        @endphp
                                        <form method="POST" action="{{ route('warehouse.production-batches.receive', $batch) }}" class="flex min-w-56 flex-col items-start gap-2" onsubmit="return confirm('Xác nhận nhập số lượng thực tế đã khai báo?');">
                                            @csrf
                                            @if($canLinkMonthlyOrder || $linkedMonthlyOrder)
                                                @if($eligibleMonthlyOrders->isNotEmpty() || $linkedMonthlyOrder)
                                                    <label class="w-full text-[10px] font-semibold text-slate-600">Lệnh kế hoạch tháng (nếu có)
                                                        <select name="production_order_id" class="mt-1 w-full border-slate-300 py-1 text-[10px]">
                                                            <option value="">Không gắn kế hoạch tháng</option>
                                                            @foreach($eligibleMonthlyOrders as $order)
                                                                <option value="{{ $order->id }}" @selected((int) $batch->production_order_id === (int) $order->id)>{{ $order->production_code }} · {{ $order->monthlyPlanLine->plan->plan_month->format('m/Y') }} · Còn {{ number_format((float) $order->pending_finished_quantity, 4) }} {{ $order->unit }}</option>
                                                            @endforeach
                                                            @if($linkedMonthlyOrder && !$eligibleMonthlyOrders->contains('id', $linkedMonthlyOrder->id))
                                                                <option value="{{ $linkedMonthlyOrder->id }}" selected>{{ $linkedMonthlyOrder->production_code }} · {{ $linkedMonthlyOrder->monthlyPlanLine->plan->plan_month->format('m/Y') }} · Đã liên kết</option>
                                                            @endif
                                                        </select>
                                                    </label>
                                                @endif
                                            @endif
                                            <div class="flex items-center gap-2">
                                                <input type="number" name="received_quantity" min="0.0001" max="{{ $maxReceivableQuantity }}" step="0.0001" placeholder="Tối đa {{ number_format($maxReceivableQuantity, 4) }}" required @disabled($maxReceivableQuantity <= 0) class="w-32 border-slate-300 text-right font-mono text-xs">
                                                <button class="whitespace-nowrap bg-emerald-700 px-3 py-1.5 text-[10px] font-bold uppercase text-white">Nhập kho</button>
                                            </div>
                                        </form>
                                    @else
                                        <span class="text-slate-400">Chỉ xem</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Không có lô nào đang chờ nhập kho.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $batches->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
