<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Đơn bán chờ Giám đốc Kinh doanh duyệt</h2>
                <p class="mt-1 text-xs text-slate-500">Đơn chỉ chuyển sang Kho kiểm tồn sau khi được duyệt.</p>
            </div>
            <span class="border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $orders->total() }} đơn</span>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <form method="GET" action="{{ route('sales-order-approvals.index') }}" class="flex gap-2 border border-slate-300 bg-white p-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn hoặc khách hàng" class="w-full max-w-md border-slate-300 text-xs">
                <button class="bg-slate-800 px-3 py-1.5 text-xs font-bold uppercase text-white">Tìm</button>
            </form>

            @forelse($orders as $order)
                <section class="border border-slate-300 bg-white">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 bg-slate-50 p-3">
                        <div>
                            <a href="{{ route('orders.show', $order) }}" class="font-mono text-sm font-bold text-blue-700 hover:underline">{{ $order->order_code }}</a>
                            <p class="mt-1 text-xs">{{ $order->customer->name ?? '---' }} · {{ $order->province_city }}</p>
                            <p class="text-[10px] text-slate-500">Ngày đặt {{ date('d/m/Y', strtotime($order->order_date)) }} · Hẹn giao {{ date('d/m/Y', strtotime($order->delivery_date)) }} · Loại {{ $order->order_type }}</p>
                        </div>
                        <div class="text-right text-xs"><span class="block text-[10px] uppercase text-slate-500">Tổng dòng hàng</span><strong>{{ $order->items->count() }}</strong></div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px] text-left text-xs">
                            <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500"><th class="p-2.5">Sản phẩm</th><th class="p-2.5">SKU</th><th class="p-2.5 text-right">Số lượng</th><th class="p-2.5">QCĐG</th><th class="p-2.5 text-right">Thành phẩm</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($order->items as $item)
                                    <tr><td class="p-2.5 font-semibold">{{ $item->catalog_item->name ?? '---' }}</td><td class="p-2.5 font-mono">{{ $item->catalog_item->sku ?? '---' }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $item->quantity, 2) }} {{ $item->catalog_item->unit ?? '' }}</td><td class="p-2.5">{{ $item->packaging_spec ?: '---' }}</td><td class="p-2.5 text-right font-mono">{{ $item->finished_quantity !== null ? number_format((float) $item->finished_quantity, 2) : '---' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($canApproveSalesOrders)
                        <div class="flex flex-col gap-3 border-t border-slate-200 p-3 lg:flex-row lg:items-end lg:justify-between">
                            <form method="POST" action="{{ route('orders.reject-sales', $order) }}" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-2xl">
                                @csrf
                                <input name="reason" required maxlength="1000" placeholder="Lý do từ chối bắt buộc" class="w-full border-slate-300 text-xs">
                                <button class="shrink-0 bg-rose-700 px-3 py-2 text-[10px] font-bold uppercase text-white hover:bg-rose-800">Từ chối</button>
                            </form>
                            <form method="POST" action="{{ route('orders.approve-sales', $order) }}" onsubmit="return confirm('Duyệt đơn và chuyển Kho kiểm tồn?');">
                                @csrf
                                <button class="w-full bg-emerald-700 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-800 sm:w-auto">Duyệt, chuyển Kho</button>
                            </form>
                        </div>
                    @endif
                </section>
            @empty
                <div class="border border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Không có đơn bán chờ duyệt.</div>
            @endforelse

            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>