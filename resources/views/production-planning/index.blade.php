<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Nhu cầu tồn thành phẩm / bán thành phẩm</h2>
                <p class="mt-1 text-xs text-slate-500">Kho đã xác nhận tồn theo FIFO. Chọn các mặt hàng còn thiếu cần lập kế hoạch sản xuất.</p>
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
                                        <form method="POST" action="{{ route('production-planning.plan', $order) }}" onsubmit="return confirm('Lập kế hoạch cho các mặt hàng đã chọn và gửi duyệt?');">
                                            @csrf
                                            @foreach($order->items as $item)
                                                @php($plannedShortage = $item->production_shortage)
                                                @if($plannedShortage > 0)
                                                    <div class="mb-3 border border-slate-200 p-2">
                                                        <label class="flex items-start gap-2 text-[10px] font-medium text-slate-700">
                                                            <input type="checkbox" name="selected_item_ids[]" value="{{ $item->id }}" class="mt-0.5 border-slate-300" onchange="document.getElementById('planned-quantity-{{ $item->id }}').disabled = !this.checked">
                                                            <span>{{ $item->product->name ?? 'Sản phẩm' }} · còn thiếu {{ number_format($plannedShortage, 4) }} {{ $item->product->unit ?? '' }}</span>
                                                        </label>
                                                        <span class="mt-2 flex items-center gap-2"><input id="planned-quantity-{{ $item->id }}" type="number" name="planned_quantities[{{ $item->id }}]" value="{{ old('planned_quantities.'.$item->id, number_format($plannedShortage, 4, '.', '')) }}" min="{{ number_format($plannedShortage, 4, '.', '') }}" step="0.0001" disabled class="w-full text-right text-xs font-mono border-slate-300 rounded-none"><span>{{ $item->product->unit ?? '' }}</span></span>
                                                    </div>
                                                @endif
                                            @endforeach
                                            <button class="mt-1 px-3 py-1.5 bg-emerald-600 text-[10px] font-bold uppercase text-white hover:bg-emerald-700">Lập kế hoạch và gửi duyệt</button>
                                        </form>
                                    @else
                                        <span class="text-slate-500">Chờ Kế hoạch lập lệnh</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Chưa có đơn thiếu hàng cần hoạch định.</td></tr>
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