<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Nhu cầu tồn thành phẩm / bán thành phẩm</h2>
                <p class="mt-1 text-xs text-slate-500">QA chọn lô đang có trước; Kế hoạch lập sản xuất cho phần còn thiếu.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('production-planning.approvals') }}" class="px-3 py-1.5 bg-amber-100 text-xs font-bold uppercase text-amber-900 hover:bg-amber-200">Kế hoạch chờ duyệt</a>
                <a href="{{ route('production-orders.index') }}" class="px-3 py-1.5 bg-slate-200 text-xs font-bold uppercase text-slate-700 hover:bg-slate-300">Lệnh sản xuất</a>
            </div>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif
            <form method="GET" action="{{ route('production-planning.index') }}" class="bg-white border border-slate-300 p-3 flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn hoặc khách hàng..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white hover:bg-slate-700">Tìm</button>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead><tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] uppercase text-slate-700"><th class="p-2.5 border-r">Đơn / Khách hàng</th><th class="p-2.5 border-r">Sản phẩm / Nhu cầu</th><th class="p-2.5 border-r">Đã có lô / Còn thiếu</th><th class="p-2.5 border-r">Ngày giao</th><th class="p-2.5">Sản lượng sản xuất dự kiến</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($orders as $order)
                            <tr class="align-top hover:bg-blue-50/30">
                                <td class="p-2.5 border-r"><strong class="font-mono text-blue-700">{{ $order->order_code }}</strong><span class="block">{{ $order->customer->name ?? '---' }}</span></td>
                                <td class="p-2.5 border-r">@foreach($order->items as $item)<div class="mb-1 last:mb-0"><strong>{{ $item->product->name ?? '---' }}</strong><span class="block text-[10px] text-slate-500">Cần {{ number_format((float) $item->quantity, 4) }} {{ $item->product->unit ?? '' }}</span></div>@endforeach</td>
                                <td class="p-2.5 border-r">@foreach($order->items as $item)<div class="mb-1 last:mb-0">{{ $item->product->name ?? '---' }}<span class="block text-[10px] text-slate-500">Nhu cầu {{ number_format((float) $item->quantity, 4) }} · Đã có lô {{ number_format($item->qa_selected_quantity, 4) }} · Còn thiếu {{ number_format($item->production_shortage, 4) }} {{ $item->product->unit ?? '' }}</span></div>@endforeach</td>
                                <td class="p-2.5 border-r">{{ date('d/m/Y', strtotime($order->delivery_date)) }}</td>
                                <td class="p-2.5 min-w-[240px]">
                                    @if($canPlan)
                                        <form method="POST" action="{{ route('production-planning.plan', $order) }}" onsubmit="return confirm('Lập lệnh theo lượng kế hoạch đã nhập? Sản lượng dư sẽ nhập kho cho nhu cầu sau.');">
                                            @csrf
                                            @foreach($order->items as $item)
                                                @php($plannedShortage = $item->production_shortage)
                                                <label class="mb-2 block text-[10px] font-medium text-slate-600">{{ $item->product->name ?? 'Sản phẩm' }} · thiếu {{ number_format($plannedShortage, 4) }} {{ $item->product->unit ?? '' }}
                                                    <span class="mt-1 flex items-center gap-2"><input type="number" name="planned_quantities[{{ $item->id }}]" value="{{ old('planned_quantities.'.$item->id, $plannedShortage > 0 ? number_format($plannedShortage, 4, '.', '') : '0') }}" min="{{ number_format($plannedShortage, 4, '.', '') }}" step="0.0001" class="w-full text-right text-xs font-mono border-slate-300 rounded-none"><span>{{ $item->product->unit ?? '' }}</span></span>
                                                </label>
                                            @endforeach
                                            <button class="mt-1 px-3 py-1.5 bg-emerald-600 text-[10px] font-bold uppercase text-white hover:bg-emerald-700">Lập kế hoạch / lệnh</button>
                                        </form>
                                    @else
                                        <span class="text-slate-500">Chờ Kế hoạch lập lệnh</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Chưa có đơn QA chốt lô cần hoạch định.</td></tr>
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