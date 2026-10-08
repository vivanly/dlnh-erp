<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Kho vận</p>
                <h1 class="mt-0.5 text-lg font-bold tracking-tight text-slate-900">Tồn kho tổng</h1>
            </div>
            <span class="hidden text-xs text-slate-500 sm:inline">Tổng hợp theo sản phẩm</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            <form method="GET" action="{{ route('warehouse.stock') }}" class="erp-panel space-y-3 p-4">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
                    <input type="search" name="product" value="{{ $productQuery }}" placeholder="Tìm tên sản phẩm hoặc SKU..." class="erp-field">
                    <select name="lot_type" class="erp-field">
                        <option value="all" {{ $lotType === 'all' ? 'selected' : '' }}>Tất cả loại lô</option>
                        <option value="supplier" {{ $lotType === 'supplier' ? 'selected' : '' }}>Lô nhà cung cấp</option>
                        <option value="internal" {{ $lotType === 'internal' ? 'selected' : '' }}>Lô nội bộ</option>
                    </select>
                    <select name="hsd_status" class="erp-field">
                        <option value="all" {{ $hsdFilter === 'all' ? 'selected' : '' }}>Mọi trạng thái HSD</option>
                        <option value="expiring" {{ $hsdFilter === 'expiring' ? 'selected' : '' }}>Sắp hết HSD (30 ngày)</option>
                        <option value="expired" {{ $hsdFilter === 'expired' ? 'selected' : '' }}>Đã hết HSD</option>
                        <option value="valid" {{ $hsdFilter === 'valid' ? 'selected' : '' }}>Còn hạn trên 30 ngày</option>
                    </select>
                    <select name="sort_by" class="erp-field">
                        <option value="product_name" {{ $sortBy === 'product_name' ? 'selected' : '' }}>Sắp xếp: Tên sản phẩm</option>
                        <option value="quantity" {{ $sortBy === 'quantity' ? 'selected' : '' }}>Sắp xếp: Số lượng tồn</option>
                        <option value="exp_date" {{ $sortBy === 'exp_date' ? 'selected' : '' }}>Sắp xếp: HSD gần nhất</option>
                    </select>
                    <select name="sort_direction" class="erp-field">
                        <option value="asc" {{ $sortDirection === 'asc' ? 'selected' : '' }}>Tăng dần / gần trước</option>
                        <option value="desc" {{ $sortDirection === 'desc' ? 'selected' : '' }}>Giảm dần / xa trước</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-blue-700 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16M7 12h10m-7 7h4"></path></svg>
                        Áp dụng bộ lọc
                    </button>
                    @if(request()->query())
                        <a href="{{ route('warehouse.stock') }}" class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">Xóa bộ lọc</a>
                    @endif
                </div>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-2">
                <div class="erp-kpi" style="--erp-kpi-color:#2563eb">
                    <div class="text-xs font-semibold text-slate-500">Sản phẩm có tồn</div>
                    <div class="mt-2 font-mono text-2xl font-bold text-slate-900">{{ number_format($products->count()) }}</div>
                </div>
                <div class="erp-kpi" style="--erp-kpi-color:#7c3aed">
                    <div class="text-xs font-semibold text-slate-500">Số lô còn tồn</div>
                    <div class="mt-2 font-mono text-2xl font-bold text-slate-900">{{ number_format($stockRows->count()) }}</div>
                </div>
                <div class="erp-kpi" style="--erp-kpi-color:#059669">
                    <div class="text-xs font-semibold text-slate-500">Tổng tồn thực tế</div>
                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1">
                        @forelse($stockByUnit as $unit => $quantity)
                            <span class="text-sm font-bold text-emerald-700">{{ number_format($quantity, 2, ',', '.') }} {{ $unit }}</span>
                        @empty
                            <span class="text-sm font-bold text-slate-400">0</span>
                        @endforelse
                    </div>
                </div>
                <div class="erp-kpi" style="--erp-kpi-color:#d97706">
                    <div class="text-xs font-semibold text-slate-500">Tồn khả dụng sau giữ chỗ</div>
                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1">
                        @forelse($availableStockByUnit as $unit => $quantity)
                            <span class="text-sm font-bold text-emerald-700">{{ number_format($quantity, 2, ',', '.') }} {{ $unit }}</span>
                        @empty
                            <span class="text-sm font-bold text-slate-400">0</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="erp-panel overflow-x-auto">
                <table class="erp-stock-table w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500">
                            <th class="p-3">Sản phẩm</th>
                            <th class="p-3 text-center">Cơ cấu lô</th>
                            <th class="p-3">Tổng tồn</th>
                            <th class="p-3">HSD gần nhất</th>
                            <th class="p-3 text-center">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($products as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3">
                                    <a href="{{ route('warehouse.stock.product', ['product' => $item['product_id']] + request()->query()) }}" class="font-semibold text-blue-700 hover:underline">{{ $item['product_name'] }}</a>
                                    <div class="text-[10px] text-slate-500">SKU: {{ $item['product_sku'] }}</div>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="font-mono font-semibold text-slate-700">Tổng {{ number_format($item['lot_count']) }}</div>
                                    <div class="mt-1 flex flex-wrap justify-center gap-1">
                                        <span class="inline-flex rounded border border-sky-200 bg-sky-50 px-1.5 py-0.5 text-[10px] font-semibold text-sky-700">Lô NCC: {{ number_format($item['supplier_lot_count']) }}</span>
                                        <span class="inline-flex rounded border border-violet-200 bg-violet-50 px-1.5 py-0.5 text-[10px] font-semibold text-violet-700">Lô nội bộ: {{ number_format($item['internal_lot_count']) }}</span>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-wrap gap-x-3 gap-y-1">
                                        @foreach($item['quantity_by_unit'] as $unit => $quantity)
                                            <span class="font-mono font-bold text-emerald-700">{{ number_format($quantity, 2, ',', '.') }} {{ $unit }}</span>
                                        @endforeach
                                    </div>
                                    <div class="mt-1 text-[10px] text-slate-500">Khả dụng:
                                        @foreach($item['available_quantity_by_unit'] as $unit => $quantity)
                                            <span class="font-mono font-semibold text-emerald-700">{{ number_format($quantity, 2, ',', '.') }} {{ $unit }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-3 font-mono text-slate-700">
                                    @if($item['earliest_exp_date'])
                                        {{ date('d/m/Y', strtotime($item['earliest_exp_date'])) }}
                                    @else
                                        <span class="text-slate-400">---</span>
                                    @endif
                                </td>
                                <td class="p-3 text-center">
                                    <a href="{{ route('warehouse.stock.product', ['product' => $item['product_id']] + request()->query()) }}" class="inline-flex items-center gap-1 rounded-md border border-slate-200 px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                        Xem lô hàng
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"></path></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Không tìm thấy sản phẩm còn tồn phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>