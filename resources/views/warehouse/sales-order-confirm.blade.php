<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">{{ $order->warehouse_packed_at ? 'Xác nhận gửi hàng' : 'Kho đóng hàng' }}</h2>
                <p class="mt-1 text-xs text-slate-500">Đơn {{ $order->order_code }} · {{ $order->customer->name ?? '---' }}</p>
            </div>
            <a href="{{ route('warehouse.sales-orders') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase hover:bg-slate-300">Quay lại</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if($errors->any())
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">
                    <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="bg-white border border-slate-300 p-3 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                <div><span class="block text-[10px] uppercase text-slate-500">Ngày đặt</span>{{ $order->order_date ? date('d/m/Y', strtotime($order->order_date)) : '---' }}</div>
                <div><span class="block text-[10px] uppercase text-slate-500">Hẹn giao</span>{{ $order->delivery_date ? date('d/m/Y', strtotime($order->delivery_date)) : '---' }}</div>
                <div><span class="block text-[10px] uppercase text-slate-500">Địa chỉ</span>{{ $order->province_city ?: '---' }}</div>
                <div><span class="block text-[10px] uppercase text-slate-500">Ghi chú</span>{{ $order->notes ?: '---' }}</div>
            </div>

            @if($order->warehouse_packed_at)
                <div class="bg-white border border-slate-300">
                    <div class="p-3 bg-slate-100 border-b border-slate-300">
                        <h3 class="text-xs font-bold uppercase text-slate-700">Hàng đã đóng, chờ xác nhận gửi</h3>
                    </div>
                    @if($shipmentBlockers)
                        <div class="m-3 p-3 bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-xs" role="alert">
                            <p class="font-bold">Chưa thể xác nhận gửi hàng. Vui lòng xử lý các điều kiện sau:</p>
                            <ul class="mt-1 list-disc pl-4">
                                @foreach($shipmentBlockers as $blocker)
                                    <li>{{ $blocker }}</li>
                                @endforeach
                            </ul>
                            <p class="mt-1">Sau khi QC cập nhật hồ sơ, tải lại trang để xác nhận gửi.</p>
                        </div>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead><tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] uppercase text-slate-700"><th class="p-2.5">Sản phẩm</th><th class="p-2.5 text-right">Số lượng đã đóng</th></tr></thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($order->items as $item)
                                    <tr><td class="p-2.5">{{ $item->product->name ?? 'Sản phẩm' }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $item->packed_quantity, 2) }} {{ $item->product->unit ?? '' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($canManageWarehouse)
                    <form method="POST" action="{{ route('warehouse.sales-orders.ship', $order) }}" class="p-3 border-t border-slate-200 flex justify-end">
                        @csrf
                        <button type="submit" @disabled($shipmentBlockers) class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold uppercase hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-400" onclick="return confirm('Xác nhận đơn hàng đã gửi đi? Hệ thống sẽ trừ tồn kho.');">Xác nhận đã gửi hàng</button>
                    </form>
                    @endif
                </div>
            @else
            @if($canManageWarehouse)
            <form method="POST" action="{{ route('warehouse.sales-orders.confirm', $order) }}" class="bg-white border border-slate-300">
                @csrf
                <div class="p-3 bg-slate-100 border-b border-slate-300">
                    <h3 class="text-xs font-bold uppercase text-slate-700">Đối chiếu lô đã giữ và số lượng đóng hàng</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] uppercase text-slate-700">
                                <th class="p-2.5 border-r">Sản phẩm</th>
                                <th class="p-2.5 border-r">Số lô</th>
                                <th class="p-2.5 border-r text-right">SL theo đơn</th>
                                <th class="p-2.5 border-r text-right">Thành phẩm dự kiến</th>
                                <th class="p-2.5 w-40">SL đóng hàng đợt này</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="p-2.5 border-r font-semibold">{{ $item->product->name ?? 'Sản phẩm' }}<span class="block text-[10px] font-normal text-slate-500">{{ $item->product->sku ?? '' }} · {{ $item->product->unit ?? '' }}</span></td>
                                    <td class="p-2.5 border-r">
                                        @forelse($item->lotAllocations->where('status', 'reserved') as $allocation)
                                            @if($allocation->supplierBatch)<div class="font-mono">Lô NCC: {{ $allocation->supplierBatch->batch_number }} · còn giữ {{ number_format($allocation->reserved_quantity - $allocation->shipped_quantity, 2) }}</div>@endif
                                            @if($allocation->finishedBatch)<div class="font-mono">Lô nội bộ: {{ $allocation->finishedBatch->batch_number }} · còn giữ {{ number_format($allocation->reserved_quantity - $allocation->shipped_quantity, 2) }}</div>@endif
                                        @empty
                                            <span class="text-rose-600">Chưa có lô được giữ</span>
                                        @endforelse
                                    </td>
                                    <td class="p-2.5 border-r text-right font-mono">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="p-2.5 border-r text-right font-mono">{{ $item->finished_quantity !== null ? number_format($item->finished_quantity, 2) : '---' }}</td>
                                    <td class="p-2.5">
                                        @php
                                            $availableToShip = $item->lotAllocations->where('status', 'reserved')->sum(fn ($allocation) => (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity);
                                        @endphp
                                        @php $remainingOrderQuantity = max(0, (float) $item->quantity - (float) $item->actual_quantity); @endphp
                                        <input type="number" name="items[{{ $item->id }}]" value="{{ old('items.'.$item->id, min($availableToShip, $remainingOrderQuantity)) }}" min="0" max="{{ min($availableToShip, $remainingOrderQuantity) }}" step="0.01" required class="w-full text-xs text-right border-slate-300 rounded-none font-mono" aria-label="Số lượng đóng hàng đợt này {{ $item->product->name ?? '' }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-t border-slate-200 flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-bold uppercase hover:bg-blue-700" onclick="return confirm('Ghi nhận đóng hàng? Tồn kho chỉ được trừ sau khi xác nhận đã gửi.');">Xác nhận đóng hàng</button>
                </div>
            </form>
            @else
                <p class="border border-slate-300 bg-white p-3 text-xs text-slate-500">Chỉ xem. Quyền xác nhận đóng hàng thuộc bộ phận Kho.</p>
            @endif
            @endif
        </div>
    </div>
</x-app-layout>