<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                    Chi Tiết Đơn Hàng: <span class="font-mono text-blue-600">{{ $order->order_code }}</span>
                </h2>
                @php
                    $orderStatusLabel = [
                        'pending_sales_approval' => 'Chờ GĐ Kinh doanh duyệt',
                        'sales_rejected' => 'Kinh doanh từ chối',
                        'pending_warehouse_check' => 'Chờ Kho kiểm tồn',
                        'pending_material_issue' => 'Chờ Kho xuất nguyên liệu',
                        'pending_qa' => 'Chờ QA chốt lô',
                        'pending_planning' => 'Chờ Kế hoạch',
                        'waiting_finished_goods_receipt' => 'Chờ Kho nhập lô thành phẩm',
                        'waiting_production' => 'Chờ sản xuất',
                        'processing' => 'Đang sản xuất',
                        'ready_to_ship' => 'Chờ đóng gói / giao',
                        'completed' => 'Hoàn tất',
                    ][$order->status] ?? $order->status;
                @endphp
                <span class="px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-none text-xs font-semibold uppercase">{{ $orderStatusLabel }}</span>
                @php
                    $lotStatus = $order->lot_assignment_status;
                    $lotStatusClass = $lotStatus === 'Đã Có Số Lô'
                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                        : ($lotStatus === 'QA đang Cập Nhật'
                            ? 'bg-amber-50 text-amber-700 border-amber-200'
                            : 'bg-slate-50 text-slate-600 border-slate-200');
                @endphp
                <span class="px-2.5 py-0.5 border rounded-none text-xs font-semibold {{ $lotStatusClass }}">
                    {{ $lotStatus }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                @if(!$order->isEditLockedAfterQa() && (!in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) || $canManageLockedOrders || ($canManageOrderLots && $order->status === 'pending_qa')))
                                        <div>
                                            <span class="text-slate-500 text-[10px] font-bold block">Giám đốc Kinh doanh duyệt:</span>
                                            <span class="font-medium text-slate-800">{{ $order->salesApprovedBy->name ?? ($order->sales_approved_at ? 'Đã duyệt' : 'Chưa duyệt') }}</span>
                                            @if($order->sales_approved_at)<span class="block text-[10px] text-slate-500">{{ $order->sales_approved_at->format('d/m/Y H:i') }}</span>@endif
                                        </div>
                                        @if($order->sales_rejection_reason)
                                            <div class="col-span-2 sm:col-span-4 border-l-2 border-rose-500 pl-2">
                                                <span class="text-rose-700 text-[10px] font-bold block">Lý do từ chối của Giám đốc Kinh doanh:</span>
                                                <span class="font-medium text-rose-800">{{ $order->sales_rejection_reason }}</span>
                                            </div>
                                        @endif
                    <a href="{{ route('orders.edit', $order->id) }}" class="px-3 py-1.5 bg-slate-700 hover:bg-slate-800 text-white text-xs uppercase font-bold rounded-none transition">
                        {{ $canManageOrderLots && !$canManageLockedOrders && $order->status === 'pending_qa' ? 'QA Chốt Số Lô' : 'Sửa Đơn Hàng' }}
                    </a>
                @endif
                <a href="{{ route('orders.index') }}" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs uppercase font-bold rounded-none transition">
                    Quay lại danh sách
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">

            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs rounded-none">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs rounded-none">{{ session('error') }}</div>
            @endif
            @if($order->status === 'waiting_finished_goods_receipt')
                <div class="border-l-4 border-amber-500 bg-amber-50 p-3 text-xs text-amber-900">
                    QA đã phân bổ lô thành phẩm cho đơn. Đơn sẽ chuyển sang đóng gói khi Kho nhập đủ số lượng đã giữ.
                </div>
            @endif

            <!-- 1. THÔNG TIN TỔNG QUAN ĐƠN HÀNG -->
            <div class="bg-white border border-slate-300 rounded-none p-4 shadow-none">
                <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
                    1. Thông tin chung đơn hàng
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Mã Đơn Hàng:</span>
                        <span class="font-mono font-bold text-blue-600 text-sm">{{ $order->order_code }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Mã Khách Hàng:</span>
                        <span class="font-mono font-semibold text-slate-800">{{ $order->customer->code ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Khách Hàng:</span>
                        <span class="font-bold text-slate-900 text-sm">{{ $order->customer->name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Địa Chỉ:</span>
                        <span class="font-mono font-semibold text-slate-800">{{ $order->customer->address ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Loại Đơn Hàng:</span>
                        <span class="font-medium text-slate-800">{{ $order->order_type }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Ngày Nhận Đơn:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $order->order_date ? date('d/m/Y', strtotime($order->order_date)) : '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Dự Kiến Giao Hàng:</span>
                        <span class="font-mono font-bold text-slate-800">{{ $order->delivery_date ? date('d/m/Y', strtotime($order->delivery_date)) : '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Tỉnh / Thành Phố:</span>
                        <span class="font-medium text-slate-800">{{ $order->province_city ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500e text-[10px] font-bold block">Người Thông Tin / Liên Hệ:</span>
                        <span class="font-medium text-slate-800">{{ $order->contact_person ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Địa Chỉ Khách Hàng:</span>
                        <span class="font-medium text-slate-800">{{ $order->customer->address ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-[10px] font-bold block">Ghi Chú:</span>
                        <span class="font-medium text-slate-800">{{ $order->notes ?? '-' }}</span>
                    </div>
                    @if($order->warehouse_stock_checked_at)
                        <div>
                            <span class="text-slate-500 text-[10px] font-bold block">Kho xác nhận tồn:</span>
                            <span class="font-medium text-slate-800">{{ $order->warehouseStockCheckedBy->name ?? 'Nhân viên Kho' }} · {{ $order->warehouse_stock_checked_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 2. DANH MỤC VỊ THUỐC DƯỢC LIỆU YÊU CẦU -->
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                <div class="p-3 bg-slate-100 border-b border-slate-300 flex items-center justify-between">
                    <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider">
                        2. Danh mục vị thuốc dược liệu yêu cầu
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-300 text-[11px] font-bold text-slate-700">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Dược liệu / Vị Thuốc</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Mã Hàng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 text-center">Đvt</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">DL / VT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Nguồn Gốc</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 text-center">Số Lượng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 text-center">QCĐG</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 text-center">Thành Phẩm</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Yêu Cầu Bào Chế</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Mã PPCB</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Số Lô</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Kho xác nhận tồn</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Ghi Chú</th>
                                <th class="py-2.5 px-3 text-center">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs">
                            @forelse($order->items ?? [] as $index => $item)
                                <tr class="hover:bg-blue-50/20 transition">
                                    <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono">{{ $index + 1 }}</td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 font-bold text-slate-900">
                                        {{ optional($item->catalog_item)->name ?? $item->product_name ?? 'N/A' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 font-mono text-slate-600">
                                        {{ optional($item->catalog_item)->sku ?? $item->sku ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 text-center">
                                        {{ $item->unit ?? optional($item->catalog_item)->unit ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200">
                                        {{ $item->classification ?? optional($item->catalog_item)->type ?? optional($item->catalog_item)->classification ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200">
                                        {{ $item->origin ?? optional($item->catalog_item)->origin ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono font-bold">
                                        {{ $item->quantity ?? 0 }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono">
                                        {{ $item->packaging_spec ?? 0 }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono font-bold text-blue-600">
                                        {{ $item->finished_quantity ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200">
                                        {{ $item->ten_ppcb ?? optional($item->ppcb)->ten_ppcb ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 font-mono">
                                        {{ optional($item->ppcb)->ma ?? $item->ppcb_ma ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200 text-xs">
                                        @if($order->status === 'pending_qa')
                                            <span class="px-2.5 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-none text-xs font-semibold uppercase">Chờ QA chốt lô</span>
                                        @endif
                                        @php($lotAllocations = $item->lotAllocations->whereIn('status', ['reserved', 'shipped']))
                                        @forelse($lotAllocations as $allocation)
                                            @php($lot = $allocation->supplierBatch ?? $allocation->finishedBatch)
                                            <div class="space-y-1">
                                                <div>
                                                    <span class="text-slate-500 font-bold text-[10px]">{{ $allocation->supplier_batch_id ? 'Lô NCC' : 'Lô nội bộ' }}:</span>
                                                    <span class="font-mono font-semibold text-emerald-700">{{ $lot->batch_number ?? '---' }}</span>
                                                    <span class="text-[10px] text-slate-500">· {{ number_format($allocation->reserved_quantity, 4) }} {{ optional($item->product)->unit ?? '' }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            @if($order->status !== 'pending_qa')
                                                <span class="text-slate-400">Chưa phân lô</span>
                                            @endif
                                        @endforelse
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200">
                                        @if($item->warehouse_stock_confirmed_quantity !== null)
                                            @php($stockShortage = max(0, (float) $item->quantity - (float) $item->warehouse_stock_confirmed_quantity))
                                            <span class="block font-semibold {{ $stockShortage > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                                {{ $stockShortage > 0 ? 'Thiếu ' . number_format($stockShortage, 4) : 'Đủ hàng' }}
                                            </span>
                                            <span class="block text-[10px] text-slate-500">Kho xác nhận {{ number_format((float) $item->warehouse_stock_confirmed_quantity, 4) }} / cần {{ number_format((float) $item->quantity, 4) }} {{ optional($item->product)->unit ?? '' }}</span>
                                            <span class="block text-[10px] text-slate-500">Tồn hệ thống lúc kiểm: {{ number_format((float) $item->warehouse_stock_available_quantity, 4) }}</span>
                                        @else
                                            <span class="text-slate-400">Chưa xác nhận</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 border-r border-slate-200">{{ $item->notes ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-center">
                                        @if(!$order->isApproved() && ($canManageLockedOrders || !in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true)))
                                            <form action="{{ route('order-items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vị thuốc này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold">Xóa</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="py-6 text-center text-slate-500">Đơn hàng chưa có sản phẩm.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>p-layout>