<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">QA · Gán số lô cho lệnh sản xuất</h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            <p class="border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">Các lệnh đã duyệt, chưa gán lô và chưa xuất nguyên liệu. QA gán số lô trước khi Kho xuất nguyên liệu.</p>
            <form method="GET" action="{{ route('qa.production-batch-assignments.index') }}" class="flex gap-2 border border-slate-300 bg-white p-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã lệnh, sản phẩm hoặc đơn bán" class="w-full border-slate-300 py-1.5 text-xs sm:max-w-md">
                <button class="bg-slate-800 px-3 py-1.5 text-xs font-bold uppercase text-white hover:bg-slate-700">Tìm</button>
            </form>
            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-300 bg-slate-100 text-slate-700">
                        <tr><th class="p-3">Lệnh sản xuất</th><th class="p-3">Nguồn</th><th class="p-3">Sản phẩm</th><th class="p-3 text-right">Kế hoạch</th><th class="p-3 text-right">Thao tác</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($productionOrders as $productionOrder)
                            <tr>
                                <td class="p-3 font-mono font-bold text-blue-700">{{ $productionOrder->production_code }}</td>
                                <td class="p-3">{{ $productionOrder->order?->order_code ?? ('Kế hoạch tháng '.$productionOrder->monthlyPlanLine?->plan?->plan_month?->format('m/Y')) }}</td>
                                <td class="p-3">{{ $productionOrder->product->name }} <span class="font-mono text-blue-700">{{ $productionOrder->product->sku }}</span></td>
                                <td class="p-3 text-right font-mono">{{ number_format($productionOrder->planned_quantity, 4) }} {{ $productionOrder->unit }}</td>
                                <td class="p-3 text-right"><a href="{{ route('qa.production-batch-assignments.show', $productionOrder) }}" class="bg-blue-700 px-3 py-2 font-bold text-white hover:bg-blue-800">{{ $canManageLots ? 'Gán số lô' : 'Xem lệnh' }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-6 text-center text-slate-500">Không có lệnh chờ QA gán số lô.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border border-slate-300 bg-slate-50 p-3"><x-per-page-select />{{ $productionOrders->links() }}</div>
        </div>
    </div>
</x-app-layout>
