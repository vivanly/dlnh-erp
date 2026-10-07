<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">QA · Theo dõi sản lượng hoàn thành</h2>
                <p class="mt-1 text-xs text-slate-500">Theo dõi sản lượng còn lại theo từng lệnh. Với kế hoạch tháng, Kho chọn lô nội bộ QA đã tạo khi chốt sản lượng để liên kết nguyên liệu đã xuất với thành phẩm.</p>
            </div>
            <span class="border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $productionOrders->total() }} lệnh cần theo dõi</span>
        </div>
    </x-slot>
    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            <form method="GET" class="flex gap-2 border border-slate-300 bg-white p-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã lệnh, đơn bán hoặc sản phẩm" class="w-full max-w-md border-slate-300 py-1.5 text-xs">
                <button class="bg-slate-800 px-3 py-1.5 text-xs font-bold uppercase text-white">Tìm</button>
            </form>
            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-300 bg-slate-100 text-[10px] uppercase text-slate-600"><tr><th class="p-2.5">Lệnh sản xuất</th><th class="p-2.5">Nguồn kế hoạch</th><th class="p-2.5">Sản phẩm</th><th class="p-2.5 text-right">Sản lượng còn theo dõi</th><th class="p-2.5">Hoàn thành</th>@if($canManageLots)<th class="p-2.5">Thao tác</th>@endif</tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($productionOrders as $productionOrder)
                            <tr>
                                <td class="p-2.5 font-mono font-semibold">{{ $productionOrder->production_code }}</td>
                                <td class="p-2.5">{{ $productionOrder->order->order_code ?? ('Kế hoạch tháng ' . ($productionOrder->monthlyPlanLine?->plan?->plan_month?->format('m/Y') ?? '')) }}</td>
                                <td class="p-2.5">{{ $productionOrder->product->name }}</td>
                                <td class="p-2.5 text-right font-mono font-semibold">{{ number_format((float) $productionOrder->pending_finished_quantity, 4) }} {{ $productionOrder->unit }}</td>
                                <td class="p-2.5">{{ $productionOrder->completed_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                @if($canManageLots)
                                    <td class="p-2.5">
                                        <a href="{{ route('qa.internal-lots.create', ['production_order_id' => $productionOrder->id]) }}" class="inline-block whitespace-nowrap bg-emerald-700 px-3 py-1.5 text-[10px] font-bold uppercase text-white hover:bg-emerald-800">Tạo lô theo lệnh</a>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canManageLots ? 6 : 5 }}" class="p-8 text-center text-slate-500">Không có sản lượng hoàn thành nào cần theo dõi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2"><x-per-page-select />{{ $productionOrders->links() }}</div>
        </div>
    </div>
</x-app-layout>
