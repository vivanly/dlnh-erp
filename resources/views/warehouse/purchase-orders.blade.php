<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Theo dõi tiến độ giao hàng đơn mua</h2>
            <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">Đơn đã duyệt / chờ nhập</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('warehouse.purchase-orders') }}" class="bg-white border border-slate-300 p-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn, nhà cung cấp hoặc phiếu nhập..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold uppercase hover:bg-slate-700">Tìm kiếm</button>
                    @if(request('search'))
                        <a href="{{ route('warehouse.purchase-orders') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase hover:bg-slate-300">Xóa lọc</a>
                    @endif
                </div>
            </form>

            @forelse($purchaseOrders as $purchaseOrder)
                <section class="bg-white border border-slate-300">
                    <div class="p-3 bg-slate-100 border-b border-slate-300 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-2 text-xs">
                            <div><span class="block text-[10px] text-slate-500 uppercase">Mã đơn mua</span><span class="font-mono font-bold text-blue-700">{{ $purchaseOrder->po_number }}</span></div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Nhà cung cấp</span><span class="font-semibold">{{ $purchaseOrder->supplier->name ?? '---' }}</span></div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Ngày đặt</span>{{ $purchaseOrder->order_date ? date('d/m/Y', strtotime($purchaseOrder->order_date)) : '---' }}</div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Dự kiến giao</span>{{ $purchaseOrder->expected_delivery_date ? date('d/m/Y', strtotime($purchaseOrder->expected_delivery_date)) : '---' }}</div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Người lập</span>{{ $purchaseOrder->user->name ?? '---' }}</div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Trạng thái đơn</span>{{ $purchaseOrder->status === 'approved' ? 'Đang chờ đợt giao tiếp theo' : 'Hàng đã đến, chờ nhập kho' }}</div>
                            @if($purchaseOrder->warehouse_confirmed_at)
                                <div><span class="block text-[10px] text-slate-500 uppercase">Kho xác nhận hàng đến</span>{{ $purchaseOrder->warehouse_confirmed_at->format('d/m/Y H:i') }}</div>
                            @endif
                            <div><span class="block text-[10px] text-slate-500 uppercase">Tiền hàng</span><span class="font-mono">{{ number_format($purchaseOrder->subtotal, 0, ',', '.') }} đ</span></div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Thuế</span><span class="font-mono">{{ number_format($purchaseOrder->tax_amount, 0, ',', '.') }} đ</span></div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Tổng thanh toán</span><span class="font-mono font-bold">{{ number_format($purchaseOrder->grand_total, 0, ',', '.') }} đ</span></div>
                            <div><span class="block text-[10px] text-slate-500 uppercase">Ghi chú</span>{{ $purchaseOrder->notes ?: '---' }}</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="px-3 py-1.5 bg-slate-700 text-white text-xs font-bold uppercase hover:bg-slate-800">Chi tiết PO</a>
                            @if($purchaseOrder->status === 'approved')
                                @if($canManageWarehouse)
                                <form method="POST" action="{{ route('warehouse.purchase-orders.mark-delivered', $purchaseOrder) }}" onsubmit="return confirm('Xác nhận nhà cung cấp đã giao đơn hàng này đến kho?');">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-bold uppercase hover:bg-indigo-700">Xác nhận đợt giao đến</button>
                                </form>
                                @else
                                    <span class="text-slate-400">Chỉ xem</span>
                                @endif
                            @elseif($purchaseOrder->status === 'delivered')
                                @if($purchaseOrder->items->whereNotNull('product_id')->isNotEmpty())
                                    <a href="{{ route('goods-receipts.create', $purchaseOrder) }}" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold uppercase hover:bg-emerald-700">Lập phiếu nhập kho</a>
                                @endif
                                @if($purchaseOrder->items->whereNotNull('material_type')->isNotEmpty())
                                    <a href="{{ route('material-receipts.create', $purchaseOrder) }}" class="px-3 py-1.5 bg-teal-600 text-white text-xs font-bold uppercase hover:bg-teal-700">Nhập kho NL thô / phụ liệu</a>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-white border-b border-slate-200 text-[10px] uppercase text-slate-600">
                                    <th class="p-2 border-r">Sản phẩm</th>
                                    <th class="p-2 border-r text-right">SL đặt</th>
                                    <th class="p-2 border-r text-right">Đơn giá</th>
                                    <th class="p-2 border-r text-right">Thành tiền</th>
                                    <th class="p-2">Phiếu nhập / Thực nhận / Trả lỗi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($purchaseOrder->items as $item)
                                    <tr>
                                        <td class="p-2 border-r font-medium">{{ $item->catalog_item->name ?? 'Sản phẩm' }} <span class="text-slate-500">({{ $item->catalog_item->sku ?? '---' }})</span> <span class="text-[10px] text-slate-500">[{{ $item->item_type_label }}]</span></td>
                                        <td class="p-2 border-r text-right font-mono">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</td>
                                        <td class="p-2 border-r text-right font-mono">{{ number_format($item->unit_price, 0, ',', '.') }} đ</td>
                                        <td class="p-2 border-r text-right font-mono">{{ number_format($item->total_price, 0, ',', '.') }} đ</td>
                                        <td class="p-2">
                                            @forelse($item->goodsReceiptItems as $receiptItem)
                                                <div class="flex flex-wrap gap-x-3 gap-y-1 {{ !$loop->first ? 'mt-1 pt-1 border-t border-slate-100' : '' }}">
                                                    <a href="{{ route('goods-receipts.show', $receiptItem->goodsReceipt) }}" class="font-mono font-semibold text-blue-600 hover:underline">{{ $receiptItem->goodsReceipt->receipt_code ?? 'Phiếu nhập' }}</a>
                                                    <span>{{ $receiptItem->goodsReceipt?->receipt_date ? date('d/m/Y', strtotime($receiptItem->goodsReceipt->receipt_date)) : '' }}</span>
                                                    <span>Đạt: <strong>{{ number_format($receiptItem->received_quantity, 2) }}</strong></span>
                                                    <span>Trả: <strong class="text-rose-600">{{ number_format($receiptItem->returned_quantity, 2) }}</strong></span>
                                                    <span>Người nhận: {{ $receiptItem->goodsReceipt->receiver->name ?? '---' }}</span>
                                                    @if($receiptItem->return_reason)<span class="text-rose-600">Lý do: {{ $receiptItem->return_reason }}</span>@endif
                                                </div>
                                            @empty
                                                @if($item->material_type)
                                                    @forelse($item->materialMovements()->get() as $movement)
                                                        <div class="flex flex-wrap gap-x-3 {{ !$loop->first ? 'mt-1 pt-1 border-t border-slate-100' : '' }}">
                                                            <span>{{ $movement->created_at?->format('d/m/Y') }}</span>
                                                            <span>{{ $movement->movement_type === 'RECEIVE_PURCHASE' ? 'Đạt' : 'Trả' }}: <strong class="{{ $movement->movement_type === 'RECEIVE_PURCHASE' ? '' : 'text-rose-600' }}">{{ number_format((float) $movement->quantity, 2) }}</strong></span>
                                                            @if($movement->batch_number)<span>Lô: {{ $movement->batch_number }}</span>@endif
                                                        </div>
                                                    @empty
                                                        <span class="text-slate-400">Chưa có chi tiết nhận cho dòng hàng này</span>
                                                    @endforelse
                                                @else
                                                    <span class="text-slate-400">Chưa có chi tiết nhận cho dòng hàng này</span>
                                                @endif
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </section>
            @empty
                <div class="bg-white border border-slate-300 p-8 text-center text-sm text-slate-500">Không có đơn mua đã duyệt nào đang chờ giao hoặc nhập kho.</div>
            @endforelse

            <div class="p-3 bg-slate-50 border border-slate-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <x-per-page-select />
                @if($purchaseOrders->hasPages()){{ $purchaseOrders->links() }}@endif
            </div>
        </div>
    </div>
</x-app-layout>