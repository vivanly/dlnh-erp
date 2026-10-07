<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
                            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Đơn bán chờ đóng hàng / xác nhận gửi</h2>
            <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">Sẵn sàng xuất giao</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('warehouse.sales-orders') }}" class="bg-white border border-slate-300 p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn, khách hàng hoặc số lô..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold uppercase hover:bg-slate-700">Tìm kiếm</button>
                    @if(request('search'))
                        <a href="{{ route('warehouse.sales-orders') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase hover:bg-slate-300">Xóa lọc</a>
                    @endif
                </div>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] uppercase text-slate-700">
                            <th class="p-2.5 border-r">Mã đơn</th>
                            <th class="p-2.5 border-r">Khách hàng</th>
                            <th class="p-2.5 border-r">Loại đơn</th>
                            <th class="p-2.5 border-r">Ngày đặt / Hẹn giao</th>
                            <th class="p-2.5 border-r">Tỉnh / Người liên hệ</th>
                            <th class="p-2.5 border-r text-center">Số dòng hàng</th>
                            <th class="p-2.5 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($orders as $order)
                            <tr class="hover:bg-blue-50/30">
                                <td class="p-2.5 border-r font-mono font-bold text-blue-700">{{ $order->order_code }}</td>
                                <td class="p-2.5 border-r font-semibold">{{ $order->customer->name ?? '---' }}</td>
                                <td class="p-2.5 border-r">{{ $order->order_type }}</td>
                                <td class="p-2.5 border-r">{{ $order->order_date ? date('d/m/Y', strtotime($order->order_date)) : '---' }} / {{ $order->delivery_date ? date('d/m/Y', strtotime($order->delivery_date)) : '---' }}</td>
                                <td class="p-2.5 border-r">{{ $order->province_city }} / {{ $order->contact_person ?: '---' }}</td>
                                <td class="p-2.5 border-r text-center font-mono">{{ $order->items->count() }}</td>
                                <td class="p-2.5 text-center">
                                    @if($order->waiting_production_packaging)
                                        <span class="text-[10px] font-semibold text-amber-700">Chờ Sản xuất xác nhận đóng gói</span>
                                    @else
                                        @if($canManageWarehouse)
                                            <a href="{{ route('warehouse.sales-orders.confirm-form', $order) }}" class="inline-block px-3 py-1.5 {{ $order->warehouse_packed_at ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white text-[10px] font-bold uppercase">{{ $order->warehouse_packed_at ? 'Xác nhận đã gửi' : 'Đóng hàng' }}</a>
                                        @else
                                            <span class="text-slate-400">Chỉ xem</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-8 text-center text-slate-500">Không có đơn bán nào đang chờ Kho xác nhận.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <x-per-page-select />
                @if($orders->hasPages()){{ $orders->links() }}@endif
            </div>
        </div>
    </div>
</x-app-layout>