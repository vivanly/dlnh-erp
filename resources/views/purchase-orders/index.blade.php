<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                    Quản Lý Đơn Mua Dược Liệu (Purchase Orders)
                </h2>
                <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-none text-xs font-semibold">
                    Tổng: {{ $purchaseOrders->total() }} đơn
                </span>
            </div>

            <!-- CHỈ HIỆN KHI LÀ KINH DOANH, IT HOẶC ADMIN -->
            @php
                $user = auth()->user();
                $isSales = (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
                           (isset($user->department) && strtolower($user->department) === 'sales');

                $isIT = (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                        (isset($user->department) && strtolower($user->department) === 'it') ||
                        (isset($user->role) && strtolower($user->role) === 'it');

            @endphp

            @if($isSales || $isIT)
                <a href="{{ route('purchase-orders.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition shadow-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                    Lập Đơn Mới
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">

            <!-- THÔNG BÁO THÀNH CÔNG / LỖI -->
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs rounded-none flex items-center justify-between">
                    <span class="font-medium">{{ session('success') }}</span>
                    <button type="button" class="text-emerald-700 hover:text-emerald-900 font-bold" onclick="this.parentElement.remove();">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs rounded-none flex items-center justify-between">
                    <span class="font-medium">{{ session('error') }}</span>
                    <button type="button" class="text-rose-700 hover:text-rose-900 font-bold" onclick="this.parentElement.remove();">&times;</button>
                </div>
            @endif

            <!-- THANH TÌM KIẾM & BỘ LỌC NHANH -->
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-3">
                    <h3 class="text-sm font-bold text-slate-800">Tìm kiếm và lọc đơn mua hàng</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Lọc theo nhóm hàng, trạng thái hoặc thông tin đơn mua.</p>
                </div>
                <form action="{{ route('purchase-orders.index') }}" method="GET" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4 xl:items-end">
                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Từ khóa</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo mã PO, nhà cung cấp..." class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Loại đơn mua hàng</label>
                        <select name="item_type" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="">-- Tất cả nhóm hàng --</option>
                            <option value="raw_material" {{ request('item_type') === 'raw_material' ? 'selected' : '' }}>Nguyên liệu thô</option>
                            <option value="accessory" {{ request('item_type') === 'accessory' ? 'selected' : '' }}>Phụ liệu</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Trạng thái</label>
                        <select name="status" class="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Khởi tạo / Nháp</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Đã giao hàng</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Bị từ chối</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-end gap-2 md:col-span-2 xl:col-span-4">
                        <a href="{{ route('purchase-orders.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                            Xóa bộ lọc
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md bg-blue-700 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Lọc dữ liệu
                        </button>
                    </div>
                </form>
            </div>

            <!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Mã Đơn Mua</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Nhà cung cấp dược liệu</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-32 text-center">Ngày đặt</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-32 text-center">Dự kiến giao</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-36 text-right">Tổng tiền (VNĐ)</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28 text-center">Trạng thái</th>
                                <th class="py-2.5 px-3 text-center w-36">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse($purchaseOrders as $po)
                            <tr class="hover:bg-blue-50/20 transition">
                                <td class="py-2.5 px-3 border-r border-slate-200 font-mono font-bold text-slate-900">
                                    <a href="{{ route('purchase-orders.show', $po->id) }}" class="text-blue-600 hover:underline">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200">
                                    <div class="font-bold text-slate-900">{{ $po->supplier->name ?? 'N/A' }}</div>
                                    @if($po->supplier && $po->supplier->phone)
                                        <div class="text-[11px] text-slate-500 font-mono">SĐT: {{ $po->supplier->phone }}</div>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono">
                                    {{ date('d/m/Y', strtotime($po->order_date)) }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                    {{ $po->expected_delivery_date ? date('d/m/Y', strtotime($po->expected_delivery_date)) : '-' }}
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-right font-mono font-bold text-rose-600">
                                    {{ number_format($po->grand_total, 0, ',', '.') }} đ
                                </td>
                                <td class="py-2.5 px-3 border-r border-slate-200 text-center">
                                    @php
                                        $statusClasses = [
                                            'draft' => 'bg-slate-100 text-slate-700 border-slate-300',
                                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'approved' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'delivered' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        ];
                                        $statusLabels = [
                                            'draft' => 'Nháp',
                                            'pending' => 'Chờ duyệt',
                                            'approved' => 'Đã duyệt',
                                            'delivered' => 'Đã giao',
                                            'completed' => 'Hoàn thành',
                                            'rejected' => 'Từ chối',
                                        ];
                                    @endphp
                                    <span class="px-2 py-0.5 border rounded-none text-[10px] font-bold uppercase tracking-wider {{ $statusClasses[$po->status] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $statusLabels[$po->status] ?? ucfirst($po->status) }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center align-middle">
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($po->status === 'draft')
                                            <!-- Nút Gửi Duyệt -->
                                            <form action="{{ route('purchase-orders.submit', $po->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn gửi đơn mua dược liệu này đi duyệt?');">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] uppercase tracking-wider rounded-none transition" title="Gửi duyệt">
                                                    Gửi duyệt
                                                </button>
                                            </form>

                                            <!-- Nút Chỉnh Sửa -->
                                            <a href="{{ route('purchase-orders.edit', $po->id) }}" class="p-1 text-slate-600 hover:text-blue-600 transition" title="Chỉnh sửa">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>

                                            <!-- Nút Xóa -->
                                            <form action="{{ route('purchase-orders.destroy', $po->id) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đơn nháp này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Xóa đơn hàng">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @else
                                            <!-- Xem chi tiết -->
                                            <a href="{{ route('purchase-orders.show', $po->id) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase tracking-wider rounded-none transition">
                                                Chi tiết
                                            </a>
                                            @if($canManageLockedPurchaseOrders)
                                                <a href="{{ route('purchase-orders.edit', $po->id) }}" class="p-1 text-slate-600 hover:text-blue-600 transition" title="IT chỉnh sửa đơn">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                </a>
                                                <form action="{{ route('purchase-orders.destroy', $po->id) }}" method="POST" class="inline" onsubmit="return confirm('IT xóa đơn mua hàng này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="IT xóa đơn hàng">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-slate-500 py-6 text-xs italic">
                                    Chưa có đơn mua dược liệu nào trong hệ thống.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- PHÂN TRANG -->
                <div class="p-3 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select />
                    @if($purchaseOrders->hasPages())
                        {{ $purchaseOrders->links() }}
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
