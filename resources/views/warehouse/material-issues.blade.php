<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Xuất kho đơn nguyên liệu thô</h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-2 bg-emerald-50 border border-emerald-300 text-emerald-700 text-xs font-semibold">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-2 bg-rose-50 border border-rose-300 text-rose-700 text-xs font-semibold">{{ session('error') }}</div>@endif

            @forelse($orders as $order)
                @php($enough = $order->items->every(fn ($i) => (float) $i->quantity <= $i->stock_balance + 0.0001))
                <form id="issue-form-{{ $order->id }}" method="POST" action="{{ route('warehouse.material-issues.issue', $order) }}" onsubmit="return confirm('Xác nhận xuất kho nguyên liệu thô theo các lô đã chọn?');">@csrf</form>
                <div class="bg-white border border-slate-300">
                    <div class="p-3 bg-slate-100 border-b border-slate-300 flex items-center justify-between text-xs">
                        <div><span class="font-mono font-bold text-blue-700">{{ $order->order_code }}</span> · {{ $order->customer->name ?? '---' }} · giao dự kiến {{ optional($order->delivery_date)->format('d/m/Y') }}</div>
                        @if($canManageWarehouse)
                            <button form="issue-form-{{ $order->id }}" class="px-3 py-1.5 text-white text-xs font-bold uppercase {{ $enough ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-400 cursor-not-allowed' }}" @disabled(!$enough)>Xuất kho</button>
                        @endif
                    </div>
                    <table class="w-full text-left border-collapse text-xs">
                        <thead><tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2 border-r">Nguyên liệu thô</th><th class="p-2 border-r text-right">Cần xuất</th><th class="p-2 border-r text-right">Tồn kho</th><th class="p-2">Chọn lô NCC xuất</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="p-2 border-r">{{ $item->rawMaterial->name ?? '---' }} <span class="font-mono text-slate-500">({{ $item->rawMaterial->sku ?? '' }})</span></td>
                                    <td class="p-2 border-r text-right font-mono">{{ number_format((float) $item->quantity, 2) }} {{ $item->rawMaterial->unit ?? '' }}</td>
                                    <td class="p-2 text-right font-mono font-bold {{ (float) $item->quantity <= $item->stock_balance + 0.0001 ? 'text-emerald-700' : 'text-rose-600' }}">{{ number_format($item->stock_balance, 2) }}</td>
                                    <td class="p-2">
                                        @foreach($item->lots as $lot)
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="font-mono w-48">{{ $lot->batch ?: '(không số lô)' }} · tồn {{ number_format($lot->balance, 2) }}@if($lot->exp_date) · HSD {{ \Illuminate\Support\Carbon::parse($lot->exp_date)->format('d/m/Y') }}@endif</span>
                                                <input form="issue-form-{{ $order->id }}" type="hidden" name="lots[{{ $item->id }}][{{ $loop->index }}][batch]" value="{{ $lot->batch }}">
                                                <input form="issue-form-{{ $order->id }}" type="number" step="0.0001" min="0" max="{{ $lot->balance }}" name="lots[{{ $item->id }}][{{ $loop->index }}][quantity]" value="{{ $lot->suggested }}" class="w-28 text-xs text-right border-slate-300 py-1 font-mono">
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="bg-white border border-slate-300 p-6 text-center text-xs text-slate-500">Không có đơn nguyên liệu thô nào chờ xuất kho.</div>
            @endforelse

            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>