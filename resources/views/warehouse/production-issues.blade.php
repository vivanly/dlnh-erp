<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Xuất và xác nhận nguyên liệu cho lệnh sản xuất</h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2">
            <div class="bg-white border border-slate-300">
                <table class="w-full text-left border-collapse text-xs">
                    <thead><tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                        <th class="p-2 border-r">Lệnh sản xuất</th><th class="p-2 border-r">Đơn hàng</th><th class="p-2 border-r">Sản phẩm</th><th class="p-2 border-r text-right">Số lượng</th><th class="p-2 text-right">Thao tác</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($productionOrders as $po)
                            <tr>
                                <td class="p-2 border-r font-mono font-bold text-blue-700">{{ $po->production_code }}</td>
                                <td class="p-2 border-r">{{ $po->order->order_code ?? '---' }} · {{ $po->order->customer->name ?? '---' }}</td>
                                <td class="p-2 border-r">{{ $po->product->name ?? '---' }}<span class="block font-mono text-[10px] text-blue-700">Mã SP: {{ $po->product->sku ?? '---' }}</span></td>
                                <td class="p-2 border-r text-right font-mono">{{ number_format($po->planned_quantity, 4) }} {{ $po->unit }}</td>
                                <td class="p-2 text-right"><a href="{{ route('warehouse.production-issues.show', $po) }}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold uppercase">{{ $po->status === 'production_reported' ? 'Xác nhận nguyên liệu trả' : (!$po->hasPreassignedBatch() ? 'Chờ QA gán lô' : ($po->status === 'materials_issued' ? 'Xuất bổ sung' : 'Xuất nguyên liệu')) }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-slate-500">Không có lệnh sản xuất nào chờ xử lý nguyên liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-2">{{ $productionOrders->links() }}</div>
        </div>
    </div>
</x-app-layout>
