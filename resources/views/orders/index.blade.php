<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                    Quản Lý Đơn Bán Hàng
                </h2>
                <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-none text-xs font-semibold">
                    Tổng: {{ $orders->total() ?? count($orders) }} đơn
                </span>
            </div>

            <!-- PHÂN QUYỀN HIỂN THỊ NÚT LẬP ĐƠN -->
            @php
                $user = auth()->user();
                $isSales = (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
                           (isset($user->department) && strtolower($user->department) === 'sales');

                $isIT = (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                        (isset($user->department) && strtolower($user->department) === 'it') ||
                        (isset($user->role) && strtolower($user->role) === 'it');

            @endphp

            @if($isSales || $isIT)
                <a href="{{ route('orders.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition shadow-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                    Lên Đơn Mới
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            <!-- THÔNG BÁO THÀNH CÔNG -->
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs rounded-none flex items-center justify-between">
                    <span class="font-medium">{{ session('success') }}</span>
                    <button type="button" class="text-emerald-700 hover:text-emerald-900 font-bold" onclick="this.parentElement.remove();">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs rounded-none">{{ session('error') }}</div>
            @endif

            <!-- THANH TÌM KIẾM & LỌC -->
            <div class="bg-white border border-slate-300 rounded-none p-3 shadow-none">
                <form action="{{ route('orders.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                    <div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã đơn, khách hàng hoặc số lô..." class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                    </div>
                    <div>
                        <select name="status" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="pending_qa" {{ request('status') == 'pending_qa' ? 'selected' : '' }}>Chờ QA chốt số lô</option>
                            <option value="pending_sales_approval" {{ request('status') == 'pending_sales_approval' ? 'selected' : '' }}>Chờ Giám đốc Kinh doanh duyệt</option>
                            <option value="sales_rejected" {{ request('status') == 'sales_rejected' ? 'selected' : '' }}>Kinh doanh từ chối</option>
                            <option value="pending_warehouse_check" {{ request('status') == 'pending_warehouse_check' ? 'selected' : '' }}>Chờ Kho xác nhận tồn</option>
                            <option value="pending_planning" {{ request('status') == 'pending_planning' ? 'selected' : '' }}>Chờ hoạch định</option>
                            <option value="waiting_finished_goods_receipt" {{ request('status') == 'waiting_finished_goods_receipt' ? 'selected' : '' }}>Chờ Kho nhập lô thành phẩm</option>
                            <option value="waiting_production" {{ request('status') == 'waiting_production' ? 'selected' : '' }}>Chờ sản xuất</option>
                            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Đang sản xuất</option>
                            <option value="ready_to_ship" {{ request('status') == 'ready_to_ship' ? 'selected' : '' }}>Chờ xuất giao</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-2 justify-end">
                        <button type="submit" class="px-3 py-1.5 bg-slate-700 text-white text-xs uppercase font-bold rounded-none hover:bg-slate-800 transition">
                            Lọc dữ liệu
                        </button>
                        <a href="{{ route('orders.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition">
                            Đặt lại
                        </a>
                    </div>
                </form>
            </div>

            <!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Mã Đơn Hàng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Khách Hàng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-32">Loại Đơn</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28 text-center">Ngày Nhận</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28 text-center">Hẹn Giao</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Tỉnh/Thành</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-32">Người Thông Tin</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-36 text-center">Trạng Thái / Số Lô</th>
                                <th class="py-2.5 px-3 text-center w-28">Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse($orders as $order)
                            <tr class="hover:bg-blue-50/20 transition">
                                <td class="py-2.5 px-3 border-r border-slate-200 font-mono font-bold text-slate-900">
                                    <a href="{{ route('orders.show', $order->id) }}" class="text-blue-600 hover:underline">
                                        {{ $order->order_code }}
                                    </a>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 font-bold text-slate-900">
                                    {{ $order->customer->name ?? 'N/A' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    {{ $order->order_type }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono">
                                    {{ date('d/m/Y', strtotime($order->order_date)) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                    {{ date('d/m/Y', strtotime($order->delivery_date)) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    {{ $order->province_city }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-slate-600">
                                    {{ $order->contact_person ?? '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-center">
                                    @php
                                        $lotStatus = $order->lot_assignment_status;
                                        $lotStatusClass = $lotStatus === 'Đã Có Số Lô'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                            : ($lotStatus === 'QA đang Cập Nhật'
                                                ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                : 'bg-slate-50 text-slate-600 border-slate-200');
                                    @endphp
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="px-2 py-0.5 border rounded-none text-[10px] font-bold {{ $lotStatusClass }}">
                                            {{ $lotStatus }}
                                        </span>
                                        <span class="text-[9px] text-slate-500">Đơn: {{ [
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
                                        ][$order->status] ?? $order->status }}</span>
                                        @if($order->status === 'sales_rejected' && $order->sales_rejection_reason)
                                            <span class="max-w-48 text-[9px] text-rose-700">Lý do: {{ $order->sales_rejection_reason }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-center align-middle">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('orders.show', $order->id) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase tracking-wider rounded-none transition">
                                            Chi tiết
                                        </a>
                                        @if($canManageOrderDrafts && !$order->isApproved() && (!in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) || $canManageLockedOrders))
                                            <form action="{{ route('orders.destroy', $order->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Xóa đơn hàng">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-slate-500 py-6 text-xs italic">
                                    Chưa có đơn hàng dược liệu nào trong hệ thống.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- PHÂN TRANG -->
                <div class="p-3 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select />
                    @if(method_exists($orders, 'hasPages') && $orders->hasPages())
                        {{ $orders->links() }}
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>