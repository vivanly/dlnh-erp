<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Đơn hàng chờ QA chốt số lô</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $orders->total() }} đơn cần cập nhật lô bán.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif

            <form method="GET" action="{{ route('qa.order-batches') }}" class="bg-white border border-slate-300 p-3 flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn, khách hàng hoặc sản phẩm..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white hover:bg-slate-700">Tìm</button>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead><tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] uppercase text-slate-700"><th class="p-2.5 border-r">Đơn / Khách hàng</th><th class="p-2.5 border-r">Ngày nhận / Hẹn giao</th><th class="p-2.5 border-r">Nơi giao / Liên hệ</th><th class="p-2.5 border-r">Sản phẩm, quy cách, số lượng</th><th class="p-2.5">Thao tác</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($orders as $order)
                            <tr class="align-top hover:bg-blue-50/30">
                                <td class="p-2.5 border-r"><strong class="font-mono text-blue-700">{{ $order->order_code }}</strong><span class="block mt-1">{{ $order->customer->name ?? '---' }}</span><span class="block text-[10px] text-slate-500">{{ $order->order_type }}</span>@if($order->warehouse_stock_checked_at)<span class="block mt-1 text-[10px] text-slate-500">Kho kiểm {{ $order->warehouse_stock_checked_at->format('d/m/Y H:i') }} · {{ $order->warehouseStockCheckedBy->name ?? 'Nhân viên Kho' }}</span>@endif</td>
                                <td class="p-2.5 border-r">{{ date('d/m/Y', strtotime($order->order_date)) }}<span class="block text-slate-500">Hẹn {{ date('d/m/Y', strtotime($order->delivery_date)) }}</span></td>
                                <td class="p-2.5 border-r">{{ $order->province_city }}<span class="block text-slate-500">{{ $order->contact_person ?: 'Chưa có người liên hệ' }}</span></td>
                                <td class="p-2.5 border-r">
                                    @foreach($order->items as $item)
                                        <div class="mb-1 last:mb-0">
                                            <strong>{{ $item->product->name ?? '---' }}</strong>
                                            <span class="block text-[10px] text-slate-500">SKU {{ $item->product->sku ?? '---' }} · {{ $item->product->classification ?? '---' }} · {{ $item->product->origin ?? '---' }}</span>
                                            <span class="block text-[10px] text-slate-500">{{ number_format((float) $item->quantity, 4) }} {{ $item->product->unit ?? '' }} · QCĐG {{ $item->packaging_spec ?: '---' }} · TP {{ number_format((float) $item->finished_quantity, 4) }} · PPCB {{ $item->ppcb->ten_ppcb ?? '---' }}</span>
                                            @if($item->warehouse_stock_confirmed_quantity !== null)
                                                @php($stockShortage = max(0, (float) $item->quantity - (float) $item->warehouse_stock_confirmed_quantity))
                                                <span class="block text-[10px] font-semibold {{ $stockShortage > 0 ? 'text-amber-700' : 'text-emerald-700' }}">Kho xác nhận {{ number_format((float) $item->warehouse_stock_confirmed_quantity, 4) }} {{ $item->product->unit ?? '' }} · {{ $stockShortage > 0 ? 'Thiếu ' . number_format($stockShortage, 4) : 'Đủ hàng' }}</span>
                                            @endif
                                            @if($item->notes)<span class="block text-[10px] text-slate-500">{{ $item->notes }}</span>@endif
                                        </div>
                                    @endforeach
                                    @if($order->notes)<p class="mt-2 border-t pt-1 text-[10px] text-slate-500">Ghi chú đơn: {{ $order->notes }}</p>@endif
                                </td>
                                <td class="p-2.5 whitespace-nowrap">@if($canManageQaLots)<a href="{{ route('orders.edit', $order) }}" class="inline-block px-3 py-1.5 bg-blue-600 text-[10px] font-bold uppercase text-white hover:bg-blue-700">Cập nhật số lô</a>@endif<a href="{{ route('orders.show', $order) }}" class="ml-1 inline-block px-3 py-1.5 bg-slate-100 text-[10px] font-bold uppercase text-slate-700 hover:bg-slate-200">Chi tiết</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Không có đơn nào đang chờ QA chốt lô.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-300 flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                @if($orders->hasPages()){{ $orders->links() }}@endif
            </div>

        </div>
    </div>
</x-app-layout>