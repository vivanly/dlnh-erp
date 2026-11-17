<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Vật tư QC đạt chờ nhập kho chính thức</h2>
    </x-slot>

    <div class="space-y-3 px-2 py-2">
        @if(session('success'))<div class="border border-emerald-300 bg-emerald-50 p-2 text-xs font-semibold text-emerald-700">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="border border-rose-300 bg-rose-50 p-2 text-xs font-semibold text-rose-700">{{ session('error') }}</div>@endif
        <div class="overflow-x-auto border border-slate-300 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-300 bg-slate-100 font-bold uppercase text-slate-700">
                    <tr>
                        <th class="p-2">PO / nhà cung cấp</th>
                        <th class="p-2">Loại / hàng hóa</th>
                        <th class="p-2">Lô NCC / COA</th>
                        <th class="p-2 text-right">Số lượng</th>
                        <th class="p-2">QC kết luận</th>
                        <th class="p-2">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($lots as $lot)
                        <tr>
                            <td class="p-2">{{ $lot->purchaseOrderItem->purchaseOrder->po_number ?? '---' }}<span class="block text-[10px] text-slate-500">{{ $lot->purchaseOrderItem->purchaseOrder->supplier->name ?? '---' }}</span></td>
                            <td class="p-2">{{ \App\Models\CatalogItem::labelForType($lot->material_type) }} · {{ $lot->catalog_item->name ?? '---' }} <span class="block font-mono text-[10px] text-slate-500">{{ $lot->catalog_item->sku ?? '' }}</span></td>
                            <td class="p-2">{{ $lot->material_type === 'raw_material' ? $lot->batch_number : 'FIFO theo lần nhập' }}<span class="block text-[10px] text-slate-500">COA: {{ $lot->coa_file ? 'Đã lưu' : 'Thiếu' }}</span></td>
                            <td class="p-2 text-right font-mono font-bold">{{ number_format((float) $lot->quantity, 4) }} {{ $lot->unit }}</td>
                            <td class="p-2">{{ $lot->qcBy->name ?? '---' }}<span class="block text-[10px] text-slate-500">{{ $lot->qc_at?->format('d/m/Y H:i') }}</span></td>
                            <td class="p-2">
                                <form method="POST" action="{{ route('material-lots.stock-receipt.post', $lot) }}" onsubmit="return confirm('Xác nhận lập phiếu nhập kho chính thức và cộng tồn?');">
                                    @csrf
                                    <button class="whitespace-nowrap bg-emerald-700 px-3 py-1.5 text-[10px] font-bold uppercase text-white hover:bg-emerald-800">Lập phiếu nhập kho</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-5 text-center text-slate-500">Không có vật tư QC đạt nào đang chờ Kho nhập.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $lots->links() }}
    </div>
</x-app-layout>
