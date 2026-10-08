<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Trả hàng nhà cung cấp · nguyên liệu thô &amp; phụ liệu</h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-2 bg-emerald-50 border border-emerald-300 text-emerald-700 text-xs font-semibold">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-2 bg-rose-50 border border-rose-300 text-rose-700 text-xs font-semibold">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-2 bg-rose-50 border border-rose-300 text-rose-700 text-xs font-semibold">{{ $errors->first() }}</div>@endif

            <form method="GET" class="bg-white border border-slate-300 p-3 flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Mã PO, tên hoặc mã hàng..." class="text-xs border-slate-300 w-64">
                <button class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold uppercase">Tìm</button>
                <a href="{{ route('supplier-returns.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase">Xóa lọc</a>
            </form>

            <section class="bg-white border border-slate-300 overflow-x-auto">
                <div class="border-b border-slate-300 bg-slate-100 px-3 py-2"><h3 class="text-xs font-bold uppercase text-slate-700">Hàng đã nhập kho theo đơn mua</h3></div>
                <table class="w-full text-left text-xs">
                    <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500">
                        <th class="p-2.5">PO / Nhà cung cấp</th><th class="p-2.5">Hàng hóa</th><th class="p-2.5 text-right">Đã nhận</th><th class="p-2.5 text-right">Đã trả</th><th class="p-2.5 text-right">Có thể trả</th><th class="p-2.5">Lập đơn trả</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($items as $item)
                            <tr>
                                <td class="p-2.5"><span class="font-mono font-semibold">{{ $item->purchaseOrder->po_number }}</span><span class="block text-[10px] text-slate-500">{{ $item->purchaseOrder->supplier->name ?? '---' }}</span></td>
                                <td class="p-2.5">{{ $item->catalog_item->name ?? '---' }} <span class="font-mono text-slate-500">({{ $item->catalog_item->sku ?? '' }})</span><span class="block text-[10px] text-slate-500">{{ $item->item_type_label }}</span></td>
                                <td class="p-2.5 text-right font-mono">{{ number_format($item->received_total, 2) }} {{ $item->unit }}</td>
                                <td class="p-2.5 text-right font-mono text-rose-600">{{ number_format($item->returned_total, 2) }}</td>
                                <td class="p-2.5 text-right font-mono font-bold">{{ number_format($item->returnable, 2) }}</td>
                                <td class="p-2.5">
                                    @if($canManage && $item->returnable > 0)
                                        <form method="POST" action="{{ route('supplier-returns.store', $item) }}" class="grid grid-cols-2 gap-1 min-w-[320px]">
                                            @csrf
                                            <select name="batch_number" required class="col-span-2 text-[10px] border-slate-300 py-1">
                                                @foreach($item->lots->where('returnable', '>', 0) as $lot)
                                                    <option value="{{ $lot->batch }}">{{ $item->material_type === 'accessory' ? 'Đợt' : 'Lô' }} {{ $lot->batch ?: '---' }} · trả được {{ number_format($lot->returnable, 2) }}</option>
                                                @endforeach
                                            </select>
                                            <input type="number" step="0.0001" min="0.0001" max="{{ $item->returnable }}" name="quantity" required placeholder="Số lượng trả" class="text-[10px] border-slate-300 py-1">
                                            <input type="date" name="qc_date" required value="{{ today()->toDateString() }}" class="text-[10px] border-slate-300 py-1">
                                            <input name="qc_test_report" required maxlength="255" placeholder="Số phiếu kiểm nghiệm" class="text-[10px] border-slate-300 py-1">
                                            <input name="reason" required maxlength="1000" placeholder="Lý do trả" class="text-[10px] border-slate-300 py-1">
                                            <button class="col-span-2 px-2 py-1 bg-rose-700 text-white text-[10px] font-bold uppercase">Lập đơn trả NCC</button>
                                        </form>
                                    @else
                                        <span class="text-slate-400 italic text-[10px]">---</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-5 text-center text-slate-500">Chưa có nguyên liệu thô/phụ liệu nào được nhập kho.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-3 flex items-center justify-between"><x-per-page-select />{{ $items->links() }}</div>
            </section>

            <section class="bg-white border border-slate-300 overflow-x-auto">
                <div class="border-b border-slate-300 bg-slate-100 px-3 py-2"><h3 class="text-xs font-bold uppercase text-slate-700">Đơn trả nhà cung cấp</h3></div>
                <table class="w-full text-left text-xs">
                    <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500"><th class="p-2.5">Mã đơn</th><th class="p-2.5">Nhà cung cấp / hàng</th><th class="p-2.5 text-right">Số lượng</th><th class="p-2.5">Lý do / PKN</th><th class="p-2.5">Trạng thái</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($returnOrders as $returnOrder)
                            <tr>
                                <td class="p-2.5 font-mono font-semibold">{{ $returnOrder->return_code }}</td>
                                <td class="p-2.5">{{ $returnOrder->supplier->name ?? '---' }}<span class="block text-[10px] text-slate-500">{{ $returnOrder->catalog_item->name ?? 'Lô NCC #'.$returnOrder->supplier_batch_id }} · {{ $returnOrder->purchaseOrder->po_number ?? '' }}</span></td>
                                <td class="p-2.5 text-right font-mono">{{ number_format($returnOrder->quantity, 4) }} {{ $returnOrder->unit }}</td>
                                <td class="p-2.5">{{ $returnOrder->reason }}<span class="block text-[10px] text-slate-500">{{ $returnOrder->qc_test_report }} · {{ optional($returnOrder->qc_date)->format('d/m/Y') }}</span></td>
                                <td class="p-2.5">
                                    @if($returnOrder->status === 'pending_dispatch')
                                        <span class="block mb-1">Chờ giao trả NCC</span>
                                        @if($canManage)
                                            <form method="POST" action="{{ route('supplier-returns.dispatch', $returnOrder) }}" onsubmit="return confirm('Xác nhận đã giao trả hàng cho NCC?');">@csrf
                                                <button class="px-2 py-1 bg-emerald-700 text-white text-[10px] font-bold uppercase">Đã giao trả</button>
                                            </form>
                                        @endif
                                    @else
                                        Đã giao trả NCC
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-5 text-center text-slate-500">Chưa có đơn trả NCC.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-3">{{ $returnOrders->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
