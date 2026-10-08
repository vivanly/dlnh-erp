<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                Nhập kho nguyên liệu thô / phụ liệu (PO: <span class="text-blue-600 font-mono">{{ $purchaseOrder->po_number }}</span>)
            </h2>
            <a href="{{ route('warehouse.purchase-orders') }}" class="px-3 py-1.5 bg-slate-200 border border-slate-300 font-bold text-xs text-slate-700 uppercase hover:bg-slate-300 transition">Quay lại</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('error'))<div class="p-2 bg-rose-50 border border-rose-300 text-rose-700 text-xs font-semibold">{{ session('error') }}</div>@endif
            <form action="{{ route('material-receipts.store', $purchaseOrder) }}" method="POST" class="space-y-3">
                @csrf
                <div class="bg-white border border-slate-300 overflow-x-auto">
                    <div class="p-3 bg-slate-100 border-b border-slate-300">
                        <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider">Lượng đạt sẽ được cộng thẳng vào tồn kho</h3>
                    </div>
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                                <th class="p-2.5 border-r">Hàng hóa</th>
                                <th class="p-2.5 border-r text-center">SL theo PO</th>
                                <th class="p-2.5 border-r text-center">Còn xử lý</th>
                                <th class="p-2.5 border-r text-center w-28">SL nhập kho</th>
                                <th class="p-2.5 border-r text-center w-28">SL trả lại</th>
                                <th class="p-2.5 border-r w-40">Lô NCC (chỉ nguyên liệu thô)</th>
                                <th class="p-2.5 border-r w-32">NSX</th>
                                <th class="p-2.5 border-r w-32">HSD</th>
                                <th class="p-2.5">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($items as $item)
                                <tr>
                                    <td class="p-2.5 border-r font-medium">{{ $item->catalog_item->name ?? '---' }} <span class="text-slate-500 font-mono">({{ $item->catalog_item->sku ?? '' }})</span>
                                        <span class="block text-[10px] text-slate-500">{{ $item->item_type_label }}</span></td>
                                    <td class="p-2.5 border-r text-center font-mono font-bold">{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td class="p-2.5 border-r text-center font-mono">{{ number_format($item->remaining_quantity, 2) }} {{ $item->unit }}</td>
                                    <td class="p-2.5 border-r"><input type="number" min="0" max="{{ $item->remaining_quantity }}" step="0.01" name="items[{{ $item->id }}][received_quantity]" value="{{ old('items.'.$item->id.'.received_quantity', $item->remaining_quantity) }}" class="w-full text-xs text-center border-slate-300 font-mono py-1" required></td>
                                    <td class="p-2.5 border-r"><input type="number" min="0" max="{{ $item->remaining_quantity }}" step="0.01" name="items[{{ $item->id }}][returned_quantity]" value="{{ old('items.'.$item->id.'.returned_quantity', 0) }}" class="w-full text-xs text-center border-slate-300 font-mono py-1 text-rose-600" required></td>
                                    <td class="p-2.5 border-r"><input type="text" name="items[{{ $item->id }}][batch_number]" value="{{ old('items.'.$item->id.'.batch_number') }}" maxlength="255" @if($item->material_type === 'raw_material') required placeholder="Số lô NCC" @else disabled placeholder="Quản lý theo tổng" @endif class="w-full text-xs border-slate-300 font-mono py-1"></td>
                                    <td class="p-2.5 border-r"><input type="date" @disabled($item->material_type === 'accessory') name="items[{{ $item->id }}][mfg_date]" value="{{ old('items.'.$item->id.'.mfg_date') }}" class="w-full text-xs border-slate-300 py-1"></td>
                                    <td class="p-2.5 border-r"><input type="date" @disabled($item->material_type === 'accessory') name="items[{{ $item->id }}][exp_date]" value="{{ old('items.'.$item->id.'.exp_date') }}" class="w-full text-xs border-slate-300 py-1"></td>
                                    <td class="p-2.5"><input type="text" name="items[{{ $item->id }}][note]" value="{{ old('items.'.$item->id.'.note') }}" class="w-full text-xs border-slate-300 py-1"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition">Lưu nhập kho</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>