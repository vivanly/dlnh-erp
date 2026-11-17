<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Phiếu nhập kho {{ $materialLot->stock_receipt_code }}</h2>
            <button type="button" onclick="window.print()" class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white">In phiếu</button>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 p-4">
        <section class="border border-slate-300 bg-white p-5">
            <div class="mb-4 border-b border-slate-200 pb-3 text-center">
                <h1 class="text-lg font-bold uppercase">Phiếu nhập kho vật tư</h1>
                <p class="mt-1 font-mono text-sm">{{ $materialLot->stock_receipt_code }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <dt class="text-slate-500">Ngày nhập kho</dt>
                <dd>{{ $materialLot->stock_received_at?->format('d/m/Y H:i') }}</dd>
                <dt class="text-slate-500">Đơn mua / nhà cung cấp</dt>
                <dd>{{ $materialLot->purchaseOrderItem->purchaseOrder->po_number ?? '---' }} · {{ $materialLot->purchaseOrderItem->purchaseOrder->supplier->name ?? '---' }}</dd>
                <dt class="text-slate-500">Loại hàng</dt>
                <dd>{{ \App\Models\CatalogItem::labelForType($materialLot->material_type) }}</dd>
                <dt class="text-slate-500">Mã / tên hàng</dt>
                <dd>{{ $materialLot->catalog_item->sku ?? '---' }} · {{ $materialLot->catalog_item->name ?? '---' }}</dd>
                <dt class="text-slate-500">Số lượng nhập</dt>
                <dd class="font-mono font-bold">{{ number_format((float) $materialLot->quantity, 4) }} {{ $materialLot->unit }}</dd>
                @if($materialLot->material_type === 'raw_material')
                    <dt class="text-slate-500">Lô nhà cung cấp</dt>
                    <dd class="font-mono">{{ $materialLot->batch_number }}</dd>
                    <dt class="text-slate-500">NSX / HSD</dt>
                    <dd>{{ $materialLot->mfg_date?->format('d/m/Y') }} / {{ $materialLot->exp_date?->format('d/m/Y') }}</dd>
                @else
                    <dt class="text-slate-500">Cách quản lý tồn</dt>
                    <dd>FIFO theo số lượng từng lần nhập</dd>
                @endif
                <dt class="text-slate-500">COA</dt>
                <dd><a href="{{ route('material-lots.coa', $materialLot) }}" class="text-blue-700 underline">Xem COA</a></dd>
                <dt class="text-slate-500">QC xác nhận</dt>
                <dd>{{ $materialLot->qcBy->name ?? '---' }} · {{ $materialLot->qc_at?->format('d/m/Y H:i') }}</dd>
                <dt class="text-slate-500">Kho lập phiếu</dt>
                <dd>{{ $materialLot->stockReceiver->name ?? '---' }}</dd>
            </dl>
        </section>
        @if($materialLot->materialReceipt)
            <section class="border border-slate-300 bg-white p-4 text-sm">
                <h2 class="mb-2 font-bold uppercase">Tiếp nhận tạm {{ $materialLot->materialReceipt->receipt_code }}</h2>
                <p>QA sơ bộ: {{ $materialLot->materialReceipt->preliminaryQa->name ?? '---' }}</p>
                <p>QC sơ bộ: {{ $materialLot->materialReceipt->preliminaryQc->name ?? '---' }}</p>
                @if($materialLot->materialReceipt->preliminary_note)<p class="mt-2 text-slate-600">{{ $materialLot->materialReceipt->preliminary_note }}</p>@endif
            </section>
        @endif
    </div>

    <style>
        @media print {
            nav, aside, header button { display: none !important; }
            body { background: white !important; }
        }
    </style>
</x-app-layout>
