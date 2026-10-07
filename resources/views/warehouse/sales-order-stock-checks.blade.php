<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Đơn bán chờ Kho xác nhận tồn</h2>
                <p class="mt-1 text-xs text-slate-500">Đối chiếu tồn theo từng lô, nhập số lượng thực tế Kho xác nhận cho mỗi sản phẩm. Đơn thiếu hàng vẫn chuyển QA; bước này không giữ hoặc trừ tồn kho.</p>
            </div>
            <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold">{{ $orders->total() }} đơn</span>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif

            <form method="GET" action="{{ route('warehouse.sales-order-stock-checks') }}" class="bg-white border border-slate-300 p-3 flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã đơn, khách hàng hoặc sản phẩm..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white hover:bg-slate-700">Tìm</button>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead><tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] uppercase text-slate-700"><th class="p-2.5 border-r">Đơn / Khách hàng</th><th class="p-2.5 border-r">Ngày nhận / Hẹn giao</th><th class="p-2.5 border-r">Sản phẩm / Nhu cầu</th><th class="p-2.5 border-r">Tồn khả dụng theo lô</th><th class="p-2.5">Xác nhận</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($orders as $order)
                            <tr class="align-top hover:bg-blue-50/30">
                                <td class="p-2.5 border-r"><strong class="font-mono text-blue-700">{{ $order->order_code }}</strong><span class="block">{{ $order->customer->name ?? '---' }}</span><span class="block text-[10px] text-slate-500">{{ $order->province_city }} · {{ $order->contact_person ?: '---' }}</span></td>
                                <td class="p-2.5 border-r">{{ date('d/m/Y', strtotime($order->order_date)) }}<span class="block text-slate-500">Hẹn {{ date('d/m/Y', strtotime($order->delivery_date)) }}</span></td>
                                <td class="p-2.5 border-r">
                                    @foreach($order->items as $item)
                                        <div class="mb-2 last:mb-0"><strong>{{ $item->product->name ?? '---' }}</strong><span class="block text-[10px] text-slate-500">{{ $item->product->sku ?? '---' }} · Cần {{ number_format((float) $item->quantity, 4) }} {{ $item->product->unit ?? '' }}</span></div>
                                    @endforeach
                                </td>
                                <td class="p-2.5 border-r">
                                    @foreach($order->items as $item)
                                        @php($availableQuantity = (float) $item->stock_check_available)
                                        <div class="mb-2 last:mb-0"><strong>{{ $item->product->name ?? '---' }}</strong><span class="block text-[10px] {{ $availableQuantity >= (float) $item->quantity ? 'text-emerald-700' : 'text-amber-700' }}">Khả dụng {{ number_format($availableQuantity, 4) }} / {{ number_format((float) $item->quantity, 4) }} {{ $item->product->unit ?? '' }}</span>
                                            @forelse($item->stock_check_lots as $lot)
                                                <span class="block text-[10px] text-slate-500">{{ $lot['type'] }} {{ $lot['code'] }} · còn khả dụng {{ number_format($lot['available_quantity'], 4) }} · HSD {{ $lot['exp_date'] ? date('d/m/Y', strtotime($lot['exp_date'])) : '---' }}</span>
                                            @empty
                                                <span class="block text-[10px] text-rose-600">Chưa có lô khả dụng</span>
                                            @endforelse
                                        </div>
                                    @endforeach
                                </td>
                                <td class="p-2.5">
                                    @if($canManageWarehouse)
                                    <form id="stock-check-{{ $order->id }}" method="POST" action="{{ route('warehouse.sales-order-stock-checks.confirm', $order) }}" onsubmit="return confirm('Lưu số lượng Kho xác nhận và chuyển đơn sang QA? Thao tác này không giữ hoặc trừ tồn.');">
                                        @csrf
                                    </form>
                                    @else
                                        <span class="text-slate-400">Chỉ xem</span>
                                    @endif
                                    @foreach($order->items as $item)
                                        @php($availableQuantity = (float) $item->stock_check_available)
                                        @php($confirmedQuantity = (float) old('confirmed_quantities.' . $item->id, $availableQuantity))
                                        @php($shortageQuantity = max(0, (float) $item->quantity - $confirmedQuantity))
                                        <label for="confirmed-{{ $item->id }}" class="mb-2 block last:mb-0">
                                            <span class="block text-[10px] text-slate-600">Kho xác nhận ({{ $item->product->unit ?? '' }}):</span>
                                            <input id="confirmed-{{ $item->id }}" form="stock-check-{{ $order->id }}" type="number" name="confirmed_quantities[{{ $item->id }}]" min="0" step="0.0001" value="{{ number_format($confirmedQuantity, 4, '.', '') }}" data-stock-required="{{ (float) $item->quantity }}" data-stock-shortage-target="shortage-{{ $item->id }}" required class="w-32 text-xs border-slate-300 rounded-none py-1">
                                            <span class="block text-[10px] text-slate-500">Cần {{ number_format((float) $item->quantity, 4) }} {{ $item->product->unit ?? '' }}</span>
                                            <span id="shortage-{{ $item->id }}" role="status" class="block text-[10px] font-semibold {{ $shortageQuantity > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $shortageQuantity > 0 ? 'Thiếu ' . number_format($shortageQuantity, 4) : 'Đủ hàng' }}</span>
                                        </label>
                                    @endforeach
                                    <button form="stock-check-{{ $order->id }}" class="mt-2 px-3 py-1.5 bg-blue-600 text-[10px] font-bold uppercase text-white hover:bg-blue-700">Lưu xác nhận, chuyển QA</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Không có đơn nào chờ Kho xác nhận tồn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-300 flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                @if($orders->hasPages()){{ $orders->links() }}@endif
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
    <script>
        document.querySelectorAll('[data-stock-shortage-target]').forEach((input) => {
            const output = document.getElementById(input.dataset.stockShortageTarget);
            const updateShortage = () => {
                const confirmed = Number(input.value);
                const required = Number(input.dataset.stockRequired);
                if (input.value === '' || !Number.isFinite(confirmed)) {
                    output.textContent = 'Nhập số lượng Kho xác nhận';
                    output.className = 'block text-[10px] font-semibold text-slate-500';
                    return;
                }

                const shortage = Math.max(0, required - confirmed);
                output.textContent = shortage > 0 ? `Thiếu ${shortage.toFixed(4)}` : 'Đủ hàng';
                output.className = `block text-[10px] font-semibold ${shortage > 0 ? 'text-amber-700' : 'text-emerald-700'}`;
            };

            input.addEventListener('input', updateShortage);
            updateShortage();
        });
    </script>
@endpush