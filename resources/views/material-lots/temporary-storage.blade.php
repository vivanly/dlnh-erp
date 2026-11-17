<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">KHO TẠM · Danh sách hàng chờ kiểm tra</h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-medium">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-red-50 border border-red-300 text-red-800 text-xs font-medium">{{ session('error') }}</div>@endif

            <form method="GET" class="bg-white border border-slate-300 p-3 flex flex-wrap items-center gap-2">
                <select name="type" class="text-xs border-slate-300 py-1.5" onchange="this.form.submit()">
                    <option value="">-- Tất cả loại --</option>
                    <option value="raw_material" @selected(request('type') === 'raw_material')>Dược liệu thô</option>
                    <option value="ingredient" @selected(request('type') === 'ingredient')>Nguyên Liệu</option>
                    <option value="accessory" @selected(request('type') === 'accessory')>Phụ Liệu</option>
                    <option value="excipient" @selected(request('type') === 'excipient')>Tá Dược</option>
                    <option value="packaging" @selected(request('type') === 'packaging')>Bao Bì</option>
                </select>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm tên/mã vật tư, NCC..." class="w-full sm:w-80 text-xs border-slate-300 py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs uppercase tracking-wider">Tìm</button>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2.5 border-r">Vật Tư</th>
                            <th class="p-2.5 border-r">NCC / Đơn Mua</th>
                            <th class="p-2.5 border-r text-center">SL Nhập</th>
                            <th class="p-2.5 border-r">Vị Trí Lưu Trữ</th>
                            <th class="p-2.5 border-r">Ngày Nhập Tạm</th>
                            <th class="p-2.5 border-r">Yêu Cầu</th>
                            <th class="p-2.5">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($lots as $lot)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="p-2.5 border-r font-medium">
                                    <span class="inline-block mb-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full {{ $lot->material_type === 'raw_material' ? 'bg-emerald-100 text-emerald-800' : 'bg-violet-100 text-violet-800' }}">
                                        {{ \App\Models\CatalogItem::labelForType($lot->material_type) }}
                                    </span>
                                    <div class="font-semibold">{{ $lot->catalog_item->name ?? '---' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $lot->catalog_item->sku ?? '' }}</div>
                                </td>
                                <td class="p-2.5 border-r">
                                    <div class="font-semibold">{{ $lot->purchaseOrderItem->purchaseOrder->supplier->name ?? '---' }}</div>
                                    <div class="text-[10px] text-slate-500">PO: {{ $lot->purchaseOrderItem->purchaseOrder->po_number ?? '---' }}</div>
                                </td>
                                <td class="p-2.5 border-r text-center font-mono font-bold text-lg">
                                    {{ rtrim(rtrim(number_format($lot->quantity, 2), '0'), '.') }}<br>
                                    <span class="text-slate-500">{{ $lot->unit }}</span>
                                </td>
                                <td class="p-2.5 border-r">
                                    <div class="font-mono font-semibold text-blue-700">{{ $lot->temporary_location_code ?? 'Chưa ghi' }}</div>
                                    <div class="text-[10px] text-slate-500">Kho Tạm</div>
                                </td>
                                <td class="p-2.5 border-r">
                                    <div class="text-[10px] font-semibold">{{ $lot->temporary_stored_at?->format('d/m/Y H:i') ?? '---' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $lot->temporaryStoredBy->name ?? '---' }}</div>
                                </td>
                                <td class="p-2.5 border-r">
                                    @if($lot->material_type === 'raw_material')
                                        <span class="inline-block px-2 py-1 bg-amber-100 text-amber-800 text-[10px] font-bold rounded">Chờ QA + QC</span>
                                    @else
                                        <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-[10px] font-bold rounded">Chờ QC</span>
                                    @endif
                                </td>
                                <td class="p-2.5">
                                    <form method="POST" action="{{ route('material-lots.move-from-temporary', $lot) }}" onsubmit="return confirm('Chuyển lô này từ kho tạm sang kiểm tra?');" class="inline">
                                        @csrf
                                        <button class="inline-block px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white font-bold text-[11px] uppercase rounded">Chuyển sang kiểm tra</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-slate-500">Không có lô hàng nào trong kho tạm</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $lots->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
