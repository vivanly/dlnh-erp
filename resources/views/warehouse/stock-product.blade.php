<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">{{ $product->name }}</h2>
                <p class="mt-1 text-xs text-slate-500">SKU: {{ $product->sku ?: '---' }} · Chi tiết tồn theo lô</p>
            </div>
            <a href="{{ route('warehouse.stock', request()->only('product', 'hsd_status', 'lot_type', 'sort_by', 'sort_direction')) }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase hover:bg-slate-300">Quay lại sản phẩm</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            <form method="GET" action="{{ route('warehouse.stock.product', ['product' => $product->id]) }}" class="bg-white border border-slate-300 p-3 flex flex-col sm:flex-row sm:items-end gap-2">
                <input type="hidden" name="product" value="{{ $productQuery }}">
                <div class="w-full sm:w-56">
                    <label class="block text-[10px] uppercase font-bold text-slate-600">Loại lô</label>
                    <select name="lot_type" class="mt-1 w-full text-xs border-slate-300 rounded-none py-1.5">
                        <option value="all" {{ $lotType === 'all' ? 'selected' : '' }}>Tất cả loại lô</option>
                        <option value="supplier" {{ $lotType === 'supplier' ? 'selected' : '' }}>Lô nhà cung cấp</option>
                        <option value="internal" {{ $lotType === 'internal' ? 'selected' : '' }}>Lô nội bộ</option>
                        <option value="production" {{ $lotType === 'production' ? 'selected' : '' }}>Lô thành phẩm</option>
                    </select>
                </div>
                <div class="w-full sm:w-56">
                    <label class="block text-[10px] uppercase font-bold text-slate-600">Trạng thái HSD</label>
                    <select name="hsd_status" class="mt-1 w-full text-xs border-slate-300 rounded-none py-1.5">
                        <option value="all" {{ $hsdFilter === 'all' ? 'selected' : '' }}>Mọi trạng thái</option>
                        <option value="expiring" {{ $hsdFilter === 'expiring' ? 'selected' : '' }}>Sắp hết HSD (30 ngày)</option>
                        <option value="expired" {{ $hsdFilter === 'expired' ? 'selected' : '' }}>Đã hết HSD</option>
                        <option value="valid" {{ $hsdFilter === 'valid' ? 'selected' : '' }}>Còn hạn trên 30 ngày</option>
                    </select>
                </div>
                <div class="w-full sm:w-56">
                    <label class="block text-[10px] uppercase font-bold text-slate-600">Sắp xếp</label>
                    <select name="sort_by" class="mt-1 w-full text-xs border-slate-300 rounded-none py-1.5">
                        <option value="exp_date" {{ $sortBy === 'exp_date' ? 'selected' : '' }}>Hạn sử dụng</option>
                        <option value="quantity" {{ $sortBy === 'quantity' ? 'selected' : '' }}>Số lượng tồn</option>
                        <option value="lot_code" {{ $sortBy === 'lot_code' ? 'selected' : '' }}>Mã lô</option>
                    </select>
                </div>
                <div class="w-full sm:w-44">
                    <label class="block text-[10px] uppercase font-bold text-slate-600">Thứ tự</label>
                    <select name="sort_direction" class="mt-1 w-full text-xs border-slate-300 rounded-none py-1.5">
                        <option value="asc" {{ $sortDirection === 'asc' ? 'selected' : '' }}>Tăng dần</option>
                        <option value="desc" {{ $sortDirection === 'desc' ? 'selected' : '' }}>Giảm dần</option>
                    </select>
                </div>
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold uppercase hover:bg-slate-700">Áp dụng</button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <div class="bg-white border border-slate-300 p-3">
                    <div class="text-[10px] uppercase font-bold text-slate-500">Số lô còn tồn</div>
                    <div class="mt-1 text-lg font-bold text-slate-800">{{ number_format($stockRows->count()) }}</div>
                </div>
                <div class="bg-white border border-slate-300 p-3">
                    <div class="text-[10px] uppercase font-bold text-slate-500">Tổng tồn thực tế</div>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1">
                        @forelse($stockByUnit as $unit => $quantity)
                            <span class="text-sm font-bold text-emerald-700">{{ number_format($quantity, 2, ',', '.') }} {{ $unit }}</span>
                        @empty
                            <span class="text-sm font-bold text-slate-400">0</span>
                        @endforelse
                    </div>
                </div>
                <div class="bg-white border border-slate-300 p-3">
                    <div class="text-[10px] uppercase font-bold text-slate-500">Tồn khả dụng sau giữ chỗ</div>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1">
                        @forelse($availableStockByUnit as $unit => $quantity)
                            <span class="text-sm font-bold text-emerald-700">{{ number_format($quantity, 2, ',', '.') }} {{ $unit }}</span>
                        @empty
                            <span class="text-sm font-bold text-slate-400">0</span>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full min-w-[850px] text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-300 text-[10px] uppercase text-slate-600">
                            <th class="p-2.5 border-r">Loại lô</th>
                            <th class="p-2.5 border-r">Mã lô</th>
                            <th class="p-2.5 border-r text-right">SL ban đầu</th>
                            <th class="p-2.5 border-r text-right">Tồn thực tế</th>
                            <th class="p-2.5 border-r text-right">Đã giữ</th>
                            <th class="p-2.5 border-r text-right">Khả dụng</th>
                            <th class="p-2.5 border-r">Ngày sản xuất</th>
                            <th class="p-2.5 border-r">Hạn sử dụng</th>
                            <th class="p-2.5 border-r">QA</th>
                            <th class="p-2.5">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($stockRows as $row)
                            <tr class="hover:bg-slate-50">
                                <td class="p-2.5 border-r">
                                    @php
                                        $lotTypeClass = $row['lot_type'] === 'Lô NCC'
                                        ? 'border-sky-200 bg-sky-50 text-sky-700'
                                        : 'border-violet-200 bg-violet-50 text-violet-700';
                                    @endphp
                                    <span class="inline-flex rounded border px-2 py-1 text-[10px] font-semibold {{ $lotTypeClass }}">{{ $row['lot_type'] }}</span>
                                </td>
                                <td class="p-2.5 border-r font-mono font-semibold text-blue-700">{{ $row['lot_code'] ?: '---' }}</td>
                                <td class="p-2.5 border-r text-right font-mono">{{ number_format($row['initial_quantity'], 2, ',', '.') }} {{ $row['product_unit'] }}</td>
                                <td class="p-2.5 border-r text-right font-mono font-bold text-emerald-700">{{ number_format($row['quantity'], 2, ',', '.') }} {{ $row['product_unit'] }}</td>
                                <td class="p-2.5 border-r text-right font-mono">{{ number_format($row['reserved_quantity'], 2, ',', '.') }} {{ $row['product_unit'] }}</td>
                                <td class="p-2.5 border-r text-right font-mono font-bold text-emerald-700">{{ number_format($row['available_quantity'], 2, ',', '.') }} {{ $row['product_unit'] }}</td>
                                <td class="p-2.5 border-r font-mono">{{ $row['mfg_date'] ? date('d/m/Y', strtotime($row['mfg_date'])) : '---' }}</td>
                                <td class="p-2.5 border-r font-mono">{{ $row['exp_date'] ? date('d/m/Y', strtotime($row['exp_date'])) : '---' }}</td>
                                <td class="p-2.5 border-r text-[10px]">{{ $row['qa_status'] }}</td>
                                <td class="p-2.5">
                                    @php
                                        $statusClass = match($row['status']) {
                                            'Hết HSD' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'Sắp hết HSD' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'Còn HSD' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-block border px-2 py-1 text-[10px] font-semibold {{ $statusClass }}">{{ $row['status'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="p-8 text-center text-slate-500">Sản phẩm này không có lô còn tồn theo bộ lọc hiện tại.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>