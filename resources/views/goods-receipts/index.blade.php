<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Danh sách phiếu nhận hàng</h2>
            <a href="{{ route('purchase-orders.index') }}" class="px-3 py-1.5 bg-slate-200 border border-slate-300 text-xs font-bold uppercase text-slate-700 hover:bg-slate-300">
                Đơn mua hàng
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            <div class="bg-white border border-slate-300">
                <form method="GET" action="{{ route('goods-receipts.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã phiếu hoặc mã đơn mua..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold uppercase hover:bg-slate-700">Tìm kiếm</button>
                        @if(request('search'))
                            <a href="{{ route('goods-receipts.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase hover:bg-slate-300">Xóa lọc</a>
                        @endif
                    </div>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold uppercase text-slate-700">
                                <th class="p-2.5 border-r text-center w-12">STT</th>
                                <th class="p-2.5 border-r">Mã phiếu nhận</th>
                                <th class="p-2.5 border-r">Đơn mua</th>
                                <th class="p-2.5 border-r">Nhà cung cấp</th>
                                <th class="p-2.5 border-r">Ngày nhận</th>
                                <th class="p-2.5 border-r">Người nhận</th>
                                <th class="p-2.5 border-r">Trạng thái QC</th>
                                <th class="p-2.5 text-center w-24">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-800">
                            @forelse($goodsReceipts as $index => $receipt)
                                <tr class="hover:bg-blue-50/40">
                                    <td class="p-2.5 border-r text-center font-mono text-slate-500">{{ $goodsReceipts->firstItem() + $index }}</td>
                                    <td class="p-2.5 border-r font-mono font-bold text-slate-900">{{ $receipt->receipt_code }}</td>
                                    <td class="p-2.5 border-r">
                                        @if($receipt->purchaseOrder)
                                            <a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}" class="font-mono text-blue-600 hover:underline">{{ $receipt->purchaseOrder->po_number }}</a>
                                        @else
                                            ---
                                        @endif
                                    </td>
                                    <td class="p-2.5 border-r">{{ $receipt->purchaseOrder->supplier->name ?? '---' }}</td>
                                    <td class="p-2.5 border-r font-mono">{{ $receipt->receipt_date ? date('d/m/Y', strtotime($receipt->receipt_date)) : '---' }}</td>
                                    <td class="p-2.5 border-r">{{ $receipt->receiver->name ?? '---' }}</td>
                                    <td class="p-2.5 border-r">{{ [
                                        'pending' => 'Chờ kiểm định',
                                        'passed' => 'Đạt',
                                        'failed' => 'Không đạt',
                                        'partially_passed' => 'Đạt một phần',
                                    ][$receipt->qc_status] ?? $receipt->qc_status }}</td>
                                    <td class="p-2.5 text-center">
                                        <a href="{{ route('goods-receipts.show', $receipt) }}" class="font-bold text-blue-600 hover:underline">Xem</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-6 text-center text-slate-500 italic">
                                        {{ request('search') ? 'Không tìm thấy phiếu nhận hàng phù hợp.' : 'Chưa có phiếu nhận hàng.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-t border-slate-300 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select />
                    @if($goodsReceipts->hasPages())
                        {{ $goodsReceipts->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>