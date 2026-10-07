<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Đơn QA đã gán lô · chờ Sản xuất đóng gói</h2>
                <p class="mt-1 text-xs text-slate-500">Chỉ xác nhận sau khi QA gán đủ lô và nhãn đã in.</p>
            </div>
            <span class="px-2.5 py-1 border border-amber-200 bg-amber-50 text-xs font-semibold text-amber-800">{{ $orders->total() }} đơn</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <form method="GET" class="flex gap-2 border border-slate-300 bg-white p-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn hoặc khách hàng" class="w-full max-w-md border-slate-300 py-1.5 text-xs">
                <button class="bg-slate-800 px-3 py-1.5 text-xs font-bold uppercase text-white">Tìm</button>
            </form>

            <div class="space-y-3">
                @forelse($orders as $order)
                    <section class="border border-slate-300 bg-white">
                        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 p-3">
                            <div><strong class="font-mono text-blue-700">{{ $order->order_code }}</strong><span class="ml-2 text-xs">{{ $order->customer->name ?? '---' }}</span></div>
                            <span class="text-[10px] text-slate-500">Hẹn giao {{ $order->delivery_date ? date('d/m/Y', strtotime($order->delivery_date)) : '---' }}</span>
                        </header>
                        <div class="divide-y divide-slate-200">
                            @foreach($order->items as $item)
                                <div class="grid grid-cols-1 gap-2 p-3 text-xs sm:grid-cols-[minmax(10rem,1fr)_minmax(15rem,2fr)_8rem]">
                                    <div><strong>{{ $item->product->name }}</strong><span class="block text-[10px] text-slate-500">Cần {{ number_format($item->quantity, 4) }} {{ $item->product->unit }}</span></div>
                                    <div class="space-y-1">
                                        @foreach($item->lotAllocations->where('status', 'reserved') as $allocation)
                                            @php($batch = $allocation->finishedBatch ?? $allocation->supplierBatch)
                                            <div class="flex flex-wrap justify-between gap-2 font-mono"><span>{{ $batch?->batch_number ?? '---' }}</span><span>{{ number_format($allocation->reserved_quantity, 4) }} {{ $item->product->unit }}</span></div>
                                        @endforeach
                                    </div>
                                    <div class="text-right text-[10px]">Nhãn {{ number_format($item->labelPrints->sum('copies_count')) }} / {{ $item->label_target_count }}</div>
                                </div>
                            @endforeach
                        </div>
                        <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 p-3">
                            <a href="{{ route('labels.orders.show', $order) }}" class="px-3 py-1.5 border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">In / xem nhãn</a>
                            @if($canWorkInProduction)
                            <form method="POST" action="{{ route('production.packaging.confirm', $order) }}" onsubmit="return confirm(@js(__('Xác nhận Sản xuất đã đóng gói đơn :order theo các lô QA chỉ định?', ['order' => $order->order_code])));">
                                @csrf
                                <button class="bg-emerald-700 px-4 py-2 text-xs font-bold uppercase text-white">Xác nhận đơn đã đóng gói</button>
                            </form>
                            @else
                                <span class="text-xs text-slate-400">Chỉ xem</span>
                            @endif
                        </footer>
                    </section>
                @empty
                    <div class="border border-slate-300 bg-white p-8 text-center text-xs text-slate-500">Chưa có đơn nào chờ Sản xuất đóng gói.</div>
                @endforelse
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $orders->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
