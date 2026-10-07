@php
    $orderStatuses = [
        'pending_planning' => 'Chờ lập kế hoạch',
        'pending_production_approval' => 'Chờ Giám Đốc duyệt',
        'waiting_finished_goods_receipt' => 'Chờ Kho nhập thành phẩm',
        'waiting_production' => 'Chờ sản xuất',
        'processing' => 'Đang sản xuất',
        'ready_to_ship' => 'Sẵn sàng giao',
        'completed' => 'Hoàn thành',
    ];
    $monthlyStatuses = ['draft' => 'Nháp', 'pending_approval' => 'Chờ Ban Giám Đốc duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Cần chỉnh sửa'];
    $allStatuses = $orderStatuses + $monthlyStatuses;
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Lịch sử kế hoạch</h2>
            <p class="mt-1 text-xs text-slate-500">Tra cứu các kế hoạch sản xuất theo đơn hàng (theo ngày lập) và kế hoạch tháng cùng trạng thái hiện tại.</p>
        </div>
    </x-slot>
    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            <form method="GET" class="flex flex-wrap items-end gap-3 border border-slate-300 bg-white p-3 text-xs">
                <label class="font-semibold text-slate-700">Tháng<input type="month" name="month" value="{{ $month }}" class="mt-1 block border-slate-300 text-xs"></label>
                <label class="font-semibold text-slate-700">Trạng thái
                    <select name="status" class="mt-1 block border-slate-300 text-xs">
                        <option value="">Tất cả</option>
                        @foreach($allStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <button class="bg-slate-800 px-4 py-2 font-bold uppercase text-white hover:bg-slate-700">Lọc</button>
                <a href="{{ route('production-planning.history') }}" class="border border-slate-300 px-4 py-2 font-semibold text-slate-700 hover:bg-slate-50">Xóa lọc</a>
            </form>

            <section class="overflow-x-auto border border-slate-300 bg-white">
                <h3 class="border-b border-slate-300 bg-slate-100 p-2.5 text-[10px] font-bold uppercase text-slate-600">Kế hoạch theo đơn hàng</h3>
                <table class="w-full text-left text-xs">
                    <thead class="text-[10px] uppercase text-slate-600"><tr><th class="p-2.5">Ngày lập</th><th class="p-2.5">Đơn hàng</th><th class="p-2.5">Khách hàng</th><th class="p-2.5">Sản phẩm / số lượng</th><th class="p-2.5">Trạng thái</th><th class="p-2.5">Lý do trả</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($orders as $order)
                            <tr>
                                <td class="p-2.5 font-mono">{{ optional($order->productionOrders->min('created_at'))->format('d/m/Y') ?? '---' }}</td>
                                <td class="p-2.5 font-mono font-semibold"><a href="{{ route('orders.show', $order->id) }}" class="text-blue-700 hover:underline">{{ $order->order_code }}</a></td>
                                <td class="p-2.5">{{ $order->customer->name ?? '---' }}</td>
                                <td class="p-2.5">@foreach($order->productionOrders as $po)<div>{{ $po->product->name ?? '---' }} @php($ppcb = optional($po->orderItem)->ppcb ?? optional($po->product)->ppcb)@if($ppcb)<span class="text-slate-500">[PPCB: {{ $ppcb->ma ?? $ppcb->ten_ppcb }}]</span>@endif · <span class="font-mono">{{ number_format((float) $po->planned_quantity, 4) }} {{ $po->unit }}</span></div>@endforeach</td>
                                <td class="p-2.5">{{ $orderStatuses[$order->status] ?? $order->status }}</td>
                                <td class="p-2.5 text-rose-700">{{ $order->production_plan_rejection_reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-6 text-center text-slate-500">Không có kế hoạch theo đơn hàng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            {{ $orders->links() }}

            <section class="overflow-x-auto border border-slate-300 bg-white">
                <h3 class="border-b border-slate-300 bg-slate-100 p-2.5 text-[10px] font-bold uppercase text-slate-600">Kế hoạch sản xuất tháng</h3>
                <table class="w-full text-left text-xs">
                    <thead class="text-[10px] uppercase text-slate-600"><tr><th class="p-2.5">Tháng</th><th class="p-2.5">Sản phẩm / sản lượng</th><th class="p-2.5">Trạng thái</th><th class="p-2.5">Lý do trả</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($monthlyPlans as $plan)
                            <tr>
                                <td class="p-2.5 font-mono font-semibold">{{ $plan->plan_month->format('m/Y') }}</td>
                                <td class="p-2.5">@foreach($plan->lines as $line)<div>{{ $line->product->name ?? '---' }} @php($linePpcb = $line->ppcb ?? optional($line->product)->ppcb)@if($linePpcb)<span class="text-slate-500">[PPCB: {{ $linePpcb->ma ?? $linePpcb->ten_ppcb }}]</span>@endif · <span class="font-mono">{{ number_format((float) $line->planned_quantity, 4) }} {{ $line->unit }}</span></div>@endforeach</td>
                                <td class="p-2.5">{{ $monthlyStatuses[$plan->status] ?? $plan->status }}</td>
                                <td class="p-2.5 text-rose-700">{{ $plan->rejection_reason }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-6 text-center text-slate-500">Không có kế hoạch tháng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
            {{ $monthlyPlans->links() }}
        </div>
    </div>
</x-app-layout>
