<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                    {{ request('status') === 'pending' ? 'Duyệt đơn mua dược liệu' : 'Quản Lý Đơn Mua Dược Liệu (Purchase Orders)' }}
                </h2>
                <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-none text-xs font-semibold">
                    Tổng: {{ $purchaseOrders->total() }} đơn
                </span>
            </div>

            <!-- CHỈ HIỆN KHI LÀ KINH DOANH, IT HOẶC ADMIN -->
            @php $canSubmitOrders = auth()->user()?->canSubmitOrders() ?? false; $isPurchaseApprovalQueue = request()->routeIs('purchase-orders.index') && request('status') === 'pending'; @endphp

            @if($canSubmitOrders && !$isPurchaseApprovalQueue)
                <a href="{{ route('purchase-orders.create') }}" class="erp-action-create-link inline-flex items-center gap-1.5 px-3 py-1.5 font-bold text-xs uppercase tracking-wider transition shadow-none">
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
            <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                <form action="{{ route('purchase-orders.index') }}" method="GET" style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:8px;">
                    @if($isPurchaseApprovalQueue)<input type="hidden" name="status" value="pending">@endif
                    <div style="flex:1 1 420px; max-width:560px; min-width:260px;">
                        <label class="mb-1 block text-[11px] font-semibold text-slate-600">Từ khóa</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã PO hoặc nhà cung cấp..." style="height:36px; width:100%; box-sizing:border-box; padding:6px 10px; font-size:13px;" class="rounded-md border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    </div>
                    <div style="flex:0 1 250px; min-width:180px;">
                        <label class="mb-1 block text-[11px] font-semibold text-slate-600">Nhóm hàng</label>
                        <select name="item_type" style="height:36px; width:100%; box-sizing:border-box; padding:4px 10px; font-size:13px;" class="rounded-md border-slate-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="">-- Tất cả nhóm hàng --</option>
                            <option value="raw_material" {{ request('item_type') === 'raw_material' ? 'selected' : '' }}>Dược liệu thô</option>
                            <option value="accessory" {{ request('item_type') === 'accessory' ? 'selected' : '' }}>Phụ liệu</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:8px; flex:0 0 auto;">
                        <a href="{{ route('purchase-orders.index', $isPurchaseApprovalQueue ? ['status' => 'pending'] : []) }}" style="height:36px; display:inline-flex; align-items:center; padding:0 12px; font-size:12px;" class="rounded-md border border-slate-300 bg-white font-semibold text-slate-700 transition hover:bg-slate-50">
                            Xóa lọc
                        </a>
                        <button type="submit" style="height:36px; padding:0 14px; font-size:12px;" class="inline-flex items-center justify-center rounded-md bg-blue-700 font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Lọc
                        </button>
                    </div>
                </form>
            </div>

            <!-- BẢNG DANH SÁCH ĐƠN HÀNG -->
            @if($isPurchaseApprovalQueue && $canApprovePurchaseOrder)
                <div class="space-y-3">
                    @forelse($purchaseOrders as $po)
                        <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <div>
                                    <a href="{{ route('purchase-orders.show', $po->id) }}" class="text-base font-bold text-blue-700 hover:underline">{{ $po->po_number }}</a>
                                    <div class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ $po->supplier->name ?? 'N/A' }}@if($po->supplier?->address) <span class="font-normal text-slate-600">· {{ $po->supplier->address }}</span>@endif
                                    </div>
                                    @if($po->supplier?->phone)<div class="text-xs text-slate-500">SĐT: {{ $po->supplier->phone }}</div>@endif
                                    <div class="mt-1 text-sm text-slate-600">Ngày đặt {{ date('d/m/Y', strtotime($po->order_date)) }} · Dự kiến giao {{ $po->expected_delivery_date ? date('d/m/Y', strtotime($po->expected_delivery_date)) : 'chưa có' }}</div>
                                </div>
                                <div class="text-right text-xs text-slate-500">TỔNG TIỀN<div class="text-sm font-bold text-slate-900">{{ number_format($po->grand_total, 0, ',', '.') }} đ</div></div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-100 text-[10px] uppercase text-slate-600"><tr><th class="p-2.5">Hàng hóa</th><th class="p-2.5">Nhóm</th><th class="p-2.5">Mã hàng</th><th class="p-2.5 text-right">Số lượng</th><th class="p-2.5 text-right">Thành tiền</th></tr></thead>
                                    <tbody class="divide-y divide-slate-200">
                                        @foreach($po->items as $item)
                                            @php $catalogItem = $item->rawMaterial ?? $item->accessory ?? $item->product; @endphp
                                            <tr><td class="p-2.5 font-semibold">{{ $catalogItem?->name ?? '---' }}</td><td class="p-2.5">{{ $item->material_type === 'accessory' ? 'Phụ liệu' : 'Dược liệu thô' }}</td><td class="p-2.5 font-mono">{{ $catalogItem?->sku ?? '---' }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $item->quantity, 2) }} {{ $item->unit }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $item->total_price, 0, ',', '.') }} đ</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 bg-slate-50 px-4 py-3">
                                <form method="POST" action="{{ route('purchase-orders.reject', $po->id) }}" class="flex min-w-0 flex-1 gap-2">@csrf<input name="rejection_reason" required maxlength="500" placeholder="Lý do từ chối bắt buộc" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm"><button class="rounded-md bg-rose-700 px-4 py-2 text-xs font-bold text-white">TỪ CHỐI</button></form>
                                <form method="POST" action="{{ route('purchase-orders.approve', $po->id) }}" onsubmit="return confirm('Duyệt đơn mua {{ $po->po_number }}?');">@csrf<button class="rounded-md bg-emerald-700 px-5 py-2 text-xs font-bold text-white">DUYỆT ĐƠN</button></form>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Không có đơn mua chờ duyệt.</div>
                    @endforelse
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2"><x-per-page-select /> @if($purchaseOrders->hasPages()){{ $purchaseOrders->links() }}@endif</div>
            @else
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
                                    <div class="font-bold text-slate-900">{{ $po->supplier->name ?? 'N/A' }}@if($po->supplier?->address) <span class="font-normal text-slate-600">· {{ $po->supplier->address }}</span>@endif</div>
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
                                        @if($canSubmitOrders && $po->status === 'draft')
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
            @endif

        </div>
    </div>
</x-app-layout>
