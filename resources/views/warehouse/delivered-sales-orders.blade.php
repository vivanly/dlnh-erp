<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Đơn bán đã giao</h2>
                <p class="mt-1 text-xs text-slate-500">Đối chiếu số lượng đã giao theo từng lô với giao dịch trừ tồn SALE_SHIPMENT.</p>
            </div>
            <span class="border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ $orders->total() }} đơn đã giao</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            <form method="GET" action="{{ route('warehouse.delivered-sales-orders') }}" class="flex flex-col gap-2 border border-slate-300 bg-white p-3 sm:flex-row sm:items-center">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn, khách hàng, sản phẩm hoặc số lô..." class="w-full max-w-xl border-slate-300 py-1.5 text-xs">
                <button class="bg-slate-800 px-3 py-1.5 text-xs font-bold uppercase text-white hover:bg-slate-700">Tìm</button>
                @if(request('search'))
                    <a href="{{ route('warehouse.delivered-sales-orders') }}" class="px-2 py-1.5 text-xs text-slate-600 hover:text-slate-900">Xóa lọc</a>
                @endif
            </form>

            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full min-w-[1050px] text-left text-xs">
                    <thead class="border-b border-slate-300 bg-slate-100 text-[10px] uppercase text-slate-600">
                        <tr>
                            <th class="p-2.5">Đơn / khách hàng</th>
                            <th class="p-2.5">Ngày xác nhận gửi</th>
                            <th class="p-2.5">Sản phẩm và lô</th>
                            <th class="p-2.5 text-right">Đã giao</th>
                            <th class="p-2.5 text-right">Đã trừ tồn</th>
                            <th class="p-2.5">Đối chiếu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($orders as $order)
                            <tr class="align-top">
                                <td class="p-2.5">
                                    <strong class="font-mono text-blue-700">{{ $order->order_code }}</strong>
                                    <span class="block text-slate-500">{{ $order->customer->name ?? '---' }}</span>
                                </td>
                                <td class="p-2.5 whitespace-nowrap">{{ $order->warehouse_confirmed_at?->format('d/m/Y H:i') ?? '---' }}</td>
                                <td class="p-2.5">
                                    @foreach($order->items as $item)
                                        <div class="mb-2 last:mb-0">
                                            <strong>{{ $item->product->name ?? 'Sản phẩm' }}</strong>
                                            @forelse($item->lotAllocations->where('shipped_quantity', '>', 0) as $allocation)
                                                @php
                                                    $lot = $allocation->supplierBatch ?? $allocation->finishedBatch;
                                                    $lotType = $allocation->supplier_batch_id ? 'Lô NCC' : 'Lô nội bộ';
                                                @endphp
                                                <span class="mt-0.5 block text-[10px] text-slate-600">
                                                    {{ $lotType }} {{ $lot->batch_number ?? '---' }}
                                                    · đã giao {{ number_format((float) $allocation->shipped_quantity, 4) }} {{ $item->product->unit ?? '' }}
                                                </span>
                                            @empty
                                                <span class="block text-[10px] text-rose-700">Không tìm thấy dòng phân bổ lô đã giao.</span>
                                            @endforelse
                                        </div>
                                    @endforeach
                                </td>
                                <td class="p-2.5 text-right font-mono">
                                    @foreach($order->items as $item)
                                        <span class="mb-2 block last:mb-0">{{ number_format((float) $item->actual_quantity, 4) }} {{ $item->product->unit ?? '' }}</span>
                                    @endforeach
                                </td>
                                <td class="p-2.5 text-right font-mono">
                                    @foreach($order->items as $item)
                                        <span class="mb-2 block last:mb-0 {{ $item->stock_deduction_matches ? 'text-emerald-700' : 'font-bold text-rose-700' }}">
                                            {{ number_format((float) $item->ledger_deducted_quantity, 4) }} {{ $item->product->unit ?? '' }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="p-2.5">
                                    @if($order->has_missing_stock_deductions)
                                        <span class="inline-block border border-rose-200 bg-rose-50 px-2 py-1 text-[10px] font-semibold text-rose-700">Kiểm tra giao dịch trừ tồn</span>
                                    @else
                                        <span class="inline-block border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-semibold text-emerald-700">Đã trừ tồn đủ</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-8 text-center text-slate-500">Chưa có đơn bán nào đã giao.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                @if($orders->hasPages()){{ $orders->links() }}@endif
            </div>
        </div>
    </div>
</x-app-layout>
