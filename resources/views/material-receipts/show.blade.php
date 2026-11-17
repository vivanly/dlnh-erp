<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Phiếu tiếp nhận tạm {{ $materialReceipt->receipt_code }}</h2>
            <button type="button" onclick="window.print()" class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white">In phiếu</button>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-3 p-4">
        @if(session('success'))<div class="border border-emerald-300 bg-emerald-50 p-2 text-xs font-semibold text-emerald-700">{{ session('success') }}</div>@endif
        <section class="grid grid-cols-2 gap-3 border border-slate-300 bg-white p-4 text-sm">
            <div class="text-slate-500">Đơn mua / nhà cung cấp</div>
            <div>{{ $materialReceipt->purchaseOrder->po_number }} · {{ $materialReceipt->purchaseOrder->supplier->name ?? '---' }}</div>
            <div class="text-slate-500">Ngày tiếp nhận</div>
            <div>{{ $materialReceipt->received_at?->format('d/m/Y H:i') }}</div>
            <div class="text-slate-500">Kho tiếp nhận</div>
            <div>{{ $materialReceipt->receiver->name ?? '---' }}</div>
            <div class="text-slate-500">QA kiểm tra sơ bộ</div>
            <div>{{ $materialReceipt->preliminaryQa->name ?? '---' }}</div>
            <div class="text-slate-500">QC kiểm tra sơ bộ</div>
            <div>{{ $materialReceipt->preliminaryQc->name ?? '---' }}</div>
            @if($materialReceipt->preliminary_note)
                <div class="text-slate-500">Ghi chú sơ bộ</div>
                <div>{{ $materialReceipt->preliminary_note }}</div>
            @endif
        </section>

        <section class="overflow-x-auto border border-slate-300 bg-white">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-100 font-bold uppercase text-slate-700">
                    <tr>
                        <th class="p-2">Loại / hàng hóa</th>
                        <th class="p-2 text-right">Tiếp nhận tạm</th>
                        <th class="p-2 text-right">Trả ở bước sơ bộ</th>
                        <th class="p-2">Ghi chú</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($rows as $row)
                        <tr>
                            <td class="p-2">{{ $row->item->item_type_label }} · {{ $row->item->catalog_item->name ?? '---' }} <span class="font-mono text-slate-500">({{ $row->item->catalog_item->sku ?? '' }})</span></td>
                            <td class="p-2 text-right font-mono">{{ number_format($row->received_quantity, 4) }} {{ $row->item->unit }}</td>
                            <td class="p-2 text-right font-mono text-rose-700">{{ number_format($row->returned_quantity, 4) }} {{ $row->item->unit }}</td>
                            <td class="p-2">{{ $row->item->notes ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
        <p class="text-xs text-amber-800">Đây là phiếu tiếp nhận tạm; hàng chưa được tính vào tồn sử dụng cho đến khi QC đạt và Kho lập phiếu nhập kho chính thức.</p>
    </div>

    <style>
        @media print {
            nav, aside, header button { display: none !important; }
            body { background: white !important; }
        }
    </style>
</x-app-layout>
