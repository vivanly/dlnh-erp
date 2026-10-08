<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Lệnh sản xuất / sơ chế</h2>
            <span class="px-2.5 py-1 bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-700">Chờ xử lý</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif

            <form method="GET" action="{{ route('production-orders.index') }}" class="bg-white border border-slate-300 p-3 flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã lệnh, tên sản phẩm trong lệnh, đơn bán hoặc mã lô NCC..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white hover:bg-slate-700">Tìm</button>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead><tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] uppercase text-slate-700"><th class="p-2.5 border-r">Lệnh sản xuất</th><th class="p-2.5 border-r">Đơn bán</th><th class="p-2.5 border-r">Đầu ra BTP / TP</th><th class="p-2.5 border-r text-right">Số lượng cần</th><th class="p-2.5 border-r text-center">Nguyên liệu</th><th class="p-2.5 border-r text-center">Trạng thái</th><th class="p-2.5 text-center">Thao tác</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($productionOrders as $productionOrder)
                            <tr class="hover:bg-blue-50/30">
                                <td class="p-2.5 border-r font-mono font-bold text-blue-700">{{ $productionOrder->production_code }}</td>
                                <td class="p-2.5 border-r">
                                    @if($productionOrder->order)
                                        {{ $productionOrder->order->order_code }}<span class="block text-[10px] text-slate-500">{{ $productionOrder->order->customer->name ?? '' }}</span>
                                    @else
                                        Kế hoạch tháng {{ $productionOrder->monthlyPlanLine?->plan?->plan_month?->format('m/Y') ?? '—' }}
                                    @endif
                                </td>
                                <td class="p-2.5 border-r font-semibold">{{ $productionOrder->product->name ?? '---' }}<span class="block text-[10px] font-normal text-slate-500">{{ str_contains(strtoupper((string) $productionOrder->product->classification), 'VT') ? 'Thành phẩm · vị thuốc' : 'Bán thành phẩm · dược liệu sơ chế' }}</span></td>
                                <td class="p-2.5 border-r text-right font-mono">{{ number_format($productionOrder->planned_quantity, 4) }} {{ $productionOrder->unit }}</td>
                                <td class="p-2.5 border-r text-center">{{ $productionOrder->materials->count() }}</td>
                                <td class="p-2.5 border-r text-center">{{ [
                                    'pending_director_approval' => 'Chờ Tổng Giám đốc/Giám đốc duyệt kế hoạch',
                                    'released' => 'Đã duyệt - Kho chờ xuất nguyên liệu FIFO',
                                    'materials_issued' => 'Đang sản xuất',
                                    'production_reported' => 'Sản xuất đã báo sản lượng - Chờ Kho xác nhận',
                                ][$productionOrder->status] ?? $productionOrder->status }}</td>
                                <td class="p-2.5 text-center whitespace-nowrap">
                                    <a href="{{ route('production-orders.show', $productionOrder) }}" class="px-3 py-1.5 bg-blue-600 text-[10px] font-bold uppercase text-white hover:bg-blue-700">Mở lệnh</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-8 text-center text-slate-500">Chưa có lệnh sản xuất đang chờ duyệt hoặc đang thực hiện.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <x-per-page-select />
                @if($productionOrders->hasPages()){{ $productionOrders->links() }}@endif
            </div>
        </div>
    </div>
</x-app-layout>