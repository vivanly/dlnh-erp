<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Duyệt đơn bán hàng</h2>
            <span class="border border-blue-200 bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">Tổng: {{ $orders->total() }} đơn</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            @if(session('success'))<div class="rounded-lg border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="rounded-lg border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="rounded-lg border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <form method="GET" action="{{ route('sales-order-approvals.index') }}" style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:8px;" class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <div style="flex:1 1 420px; max-width:560px; min-width:260px;">
                    <label for="sales-order-search" class="mb-1 block text-[11px] font-semibold text-slate-600">Từ khóa</label>
                    <input id="sales-order-search" type="search" name="search" value="{{ request('search') }}" placeholder="Mã đơn hoặc khách hàng..." style="height:36px; width:100%; box-sizing:border-box; padding:6px 10px; font-size:13px;" class="rounded-md border-slate-300 focus:border-blue-600 focus:ring-blue-600">
                </div>
                <div style="flex:0 1 250px; min-width:180px;">
                    <label for="sales-order-type" class="mb-1 block text-[11px] font-semibold text-slate-600">Loại đơn</label>
                    <select id="sales-order-type" name="order_type" style="height:36px; width:100%; box-sizing:border-box; padding:4px 10px; font-size:13px;" class="rounded-md border-slate-300 focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Tất cả loại đơn</option>
                        <option value="DL" {{ request('order_type') === 'DL' ? 'selected' : '' }}>Dược liệu</option>
                        <option value="VT" {{ request('order_type') === 'VT' ? 'selected' : '' }}>Vị thuốc</option>
                        <option value="NL" {{ request('order_type') === 'NL' ? 'selected' : '' }}>Dược liệu thô</option>
                    </select>
                </div>
                <div style="display:flex; gap:8px; flex:0 0 auto;">
                    <a href="{{ route('sales-order-approvals.index') }}" style="height:36px; display:inline-flex; align-items:center; padding:0 12px; font-size:12px;" class="rounded-md border border-slate-300 bg-white font-semibold text-slate-700 transition hover:bg-slate-50">Xóa lọc</a>
                    <button style="height:36px; padding:0 14px; font-size:12px;" class="rounded-md bg-blue-700 font-bold text-white transition hover:bg-blue-800">Lọc</button>
                </div>
            </form>

            @forelse($orders as $order)
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 bg-slate-50 p-4">
                        <div>
                            <a href="{{ route('orders.show', $order) }}" class="font-mono text-sm font-bold text-blue-700 hover:underline">{{ $order->order_code }}</a>
                            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $order->customer->name ?? '—' }} · {{ $order->province_city ?: '—' }}</p>
                            <p class="text-sm text-slate-600">Ngày đặt {{ date('d/m/Y', strtotime($order->order_date)) }} · Hẹn giao {{ date('d/m/Y', strtotime($order->delivery_date)) }} · Loại đơn hàng {{ ['DL' => 'Dược liệu', 'VT' => 'Vị thuốc', 'NL' => 'Dược liệu thô'][$order->order_type] ?? $order->order_type }}</p>
                        </div>
                        <div class="text-right text-xs"><span class="block text-[10px] uppercase text-slate-500">Tổng dòng hàng</span><strong>{{ $order->items->count() }}</strong></div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[980px] text-left text-xs">
                            <thead><tr class="border-b border-slate-200 bg-slate-100 text-[10px] uppercase text-slate-600"><th class="p-2.5">Sản phẩm</th><th class="p-2.5">SKU</th><th class="p-2.5">Nguồn gốc</th><th class="p-2.5">Phương pháp chế biến</th><th class="p-2.5 text-right">Số lượng</th><th class="p-2.5">QCĐG</th><th class="p-2.5 text-right">Thành phẩm</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($order->items as $item)
                                    <tr>
                                        <td class="p-2.5 font-semibold">{{ $item->catalog_item?->name ?? '—' }}</td>
                                        <td class="p-2.5 font-mono">{{ $item->catalog_item?->sku ?? '—' }}</td>
                                        <td class="p-2.5">{{ $item->catalog_item?->origin ?? '—' }}</td>
                                        <td class="p-2.5">{{ $item->ppcb?->ten_ppcb ?? $item->product?->ppcb?->ten_ppcb ?? '—' }}</td>
                                        <td class="p-2.5 text-right font-mono">{{ number_format((float) $item->quantity, 2) }} {{ $item->catalog_item?->unit ?? '' }}</td>
                                        <td class="p-2.5">{{ $item->packaging_spec ?: '—' }}</td>
                                        <td class="p-2.5 text-right font-mono">{{ $item->finished_quantity !== null ? number_format((float) $item->finished_quantity, 2) : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($canApproveSalesOrders)
                        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 p-4 lg:flex-row lg:items-end lg:justify-between">
                            <form method="POST" action="{{ route('orders.reject-sales', $order) }}" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-2xl">
                                @csrf
                                <input name="reason" required maxlength="1000" placeholder="Lý do từ chối bắt buộc" class="w-full rounded-md border-slate-300 text-xs focus:border-blue-600 focus:ring-blue-600">
                                <button class="shrink-0 rounded-md bg-rose-700 px-4 py-2 text-xs font-bold uppercase text-white transition hover:bg-rose-800">Từ chối</button>
                            </form>
                            <form method="POST" action="{{ route('orders.approve-sales', $order) }}" onsubmit="return confirm('Duyệt đơn và chuyển Kho kiểm tồn?');">
                                @csrf
                                <button class="w-full rounded-md bg-emerald-700 px-4 py-2 text-xs font-bold uppercase text-white transition hover:bg-emerald-800 sm:w-auto">Duyệt đơn</button>
                            </form>
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">Không có đơn bán chờ duyệt.</div>
            @endforelse
            <div class="flex flex-wrap items-center justify-between gap-2"><x-per-page-select />{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>
