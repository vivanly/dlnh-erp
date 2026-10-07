<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Kế hoạch sản xuất chờ duyệt</h2>
                <p class="mt-1 text-xs text-slate-500">Duyệt xong các lệnh mới chuyển sang Kho để xuất nguyên liệu FIFO.</p>
            </div>
            <a href="{{ route('production-planning.index') }}" class="px-3 py-1.5 bg-slate-200 text-xs font-bold uppercase text-slate-700">Nhu cầu sản xuất</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif
            <form method="GET" class="bg-white border border-slate-300 p-3 flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn hoặc khách hàng" class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white">Tìm</button>
            </form>
            <div class="space-y-3">
                @forelse($orders as $order)
                    <section class="border border-slate-300 bg-white">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 p-3">
                            <div><strong class="font-mono text-sm text-blue-700">{{ $order->order_code }}</strong><span class="ml-2 text-xs text-slate-600">{{ $order->customer->name ?? '---' }}</span></div>
                            <span class="text-xs">Giao dự kiến {{ $order->delivery_date ? date('d/m/Y', strtotime($order->delivery_date)) : '---' }}</span>
                        </div>
                        <div class="divide-y divide-slate-200">
                            @foreach($order->productionOrders as $productionOrder)
                                <div class="p-3">
                                    <div class="flex flex-wrap justify-between gap-2 text-xs">
                                        <strong>{{ $productionOrder->product->name }} · lệnh <span class="font-mono">{{ $productionOrder->production_code }}</span></strong>
                                        <strong class="font-mono">{{ number_format($productionOrder->planned_quantity, 4) }} {{ $productionOrder->unit }}</strong>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($canApproveProductionPlan)
                            <div class="flex flex-wrap items-end justify-between gap-3 border-t border-slate-200 p-3">
                                <form method="POST" action="{{ route('production-planning.reject', $order) }}" class="flex min-w-[min(100%,28rem)] flex-1 items-end gap-2">
                                    @csrf
                                    <label class="flex-1 text-[10px] font-semibold">Lý do trả kế hoạch
                                        <input name="reason" required maxlength="1000" class="mt-1 w-full text-xs border-slate-300 rounded-none">
                                    </label>
                                    <button class="px-3 py-2 bg-rose-700 text-[10px] font-bold uppercase text-white">Trả lại</button>
                                </form>
                                <form method="POST" action="{{ route('production-planning.approve', $order) }}" onsubmit="return confirm('Duyệt kế hoạch và mở quyền lập phiếu xuất nguyên liệu cho Kho?');">
                                    @csrf
                                    <button class="px-4 py-2 bg-emerald-700 text-[10px] font-bold uppercase text-white">Duyệt kế hoạch</button>
                                </form>
                            </div>
                        @endif
                    </section>
                @empty
                    <div class="border border-slate-300 bg-white p-8 text-center text-xs text-slate-500">Không có kế hoạch sản xuất nào đang chờ duyệt.</div>
                @endforelse
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>