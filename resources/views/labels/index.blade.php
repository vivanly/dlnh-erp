<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Quản lý / In nhãn theo đơn hàng</h2>
            <p class="mt-1 text-xs text-slate-500">Số tem chuẩn lấy theo số thành phẩm trên từng dòng đơn.</p>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-none px-2 space-y-3">
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full min-w-[850px] border-collapse text-left text-xs">
                    <thead><tr class="border-b border-slate-300 bg-slate-100 text-[10px] uppercase text-slate-600"><th class="p-2.5">Đơn hàng / Khách hàng</th><th class="p-2.5 text-right">Dòng đủ lô</th><th class="p-2.5 text-right">Chưa đủ lô</th><th class="p-2.5 text-right">Tem chuẩn</th><th class="p-2.5 text-right">Đã in</th><th class="p-2.5">Trạng thái</th><th class="p-2.5 text-right">Thao tác</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($orders as $order)
                            <tr>
                                <td class="p-2.5"><strong class="font-mono text-blue-700">{{ $order->order_code }}</strong><span class="block text-slate-500">{{ $order->customer->name ?? '---' }} · {{ $order->delivery_date ? date('d/m/Y', strtotime($order->delivery_date)) : '---' }}</span></td>
                                <td class="p-2.5 text-right font-mono">{{ $order->label_item_count }}</td>
                                <td class="p-2.5 text-right font-mono">{{ $order->label_waiting_count }}</td>
                                <td class="p-2.5 text-right font-mono">{{ number_format($order->label_target_total) }}</td>
                                <td class="p-2.5 text-right font-mono font-semibold">{{ number_format($order->label_printed_total) }}</td>
                                <td class="p-2.5">{{ $order->status === 'completed' ? 'Đã giao' : 'Chờ đóng gói / giao' }}</td>
                                <td class="p-2.5 text-right"><a href="{{ route('labels.orders.show', $order) }}" class="inline-flex items-center gap-1 bg-slate-800 px-3 py-1.5 text-[10px] font-bold uppercase text-white hover:bg-slate-700">Chi tiết nhãn</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-8 text-center text-slate-500">Chưa có đơn QA chốt lô để quản lý nhãn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>