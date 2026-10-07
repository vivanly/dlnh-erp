<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Định mức nguyên liệu (BOM)</h2>
            <div class="flex items-center gap-2">
                @if($canManageBoms)
                    <a href="{{ route('boms.plan') }}" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-bold uppercase hover:bg-blue-700">Tính nhu cầu MRP</a>
                    <a href="{{ route('boms.create') }}" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold uppercase hover:bg-emerald-700">Tạo phiên bản BOM</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('boms.index') }}" class="bg-white border border-slate-300 p-3 flex items-center gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm sản phẩm hoặc SKU..." class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold uppercase">Tìm</button>
            </form>

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 border-b border-slate-300 text-[10px] font-bold uppercase text-slate-700">
                            <th class="p-2.5 border-r">Thành phẩm</th>
                            <th class="p-2.5 border-r text-center">Phiên bản</th>
                            <th class="p-2.5 border-r text-right">Sản lượng định mức</th>
                            <th class="p-2.5 border-r text-right">Thu hồi</th>
                            <th class="p-2.5 border-r">Nguyên liệu / Định mức</th>
                            <th class="p-2.5 text-center">Trạng thái</th>
                            <th class="p-2.5 text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($boms as $bom)
                            <tr>
                                <td class="p-2.5 border-r font-semibold">{{ $bom->product->name ?? 'Sản phẩm' }}<span class="block text-[10px] text-slate-500">{{ $bom->product->sku ?? '' }}</span></td>
                                <td class="p-2.5 border-r text-center font-mono">v{{ $bom->version }}</td>
                                <td class="p-2.5 border-r text-right font-mono">{{ number_format($bom->output_quantity, 4) }} {{ $bom->output_unit }}</td>
                                <td class="p-2.5 border-r text-right font-mono">{{ number_format($bom->yield_rate * 100, 2) }}%</td>
                                <td class="p-2.5 border-r">
                                    @foreach($bom->items as $item)
                                        <div class="{{ !$loop->first ? 'mt-1 pt-1 border-t border-slate-100' : '' }}">
                                            {{ $item->componentProduct->name ?? 'Nguyên liệu' }}:
                                            <span class="font-mono font-semibold">{{ number_format($item->quantity, 4) }} {{ $item->unit }}</span>
                                        </div>
                                    @endforeach
                                </td>
                                <td class="p-2.5 text-center">
                                    <span class="px-2 py-0.5 border text-[10px] font-bold {{ $bom->status === 'approved' && $bom->is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($bom->status === 'pending_approval' ? 'bg-amber-50 text-amber-700 border-amber-200' : ($bom->status === 'rejected' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-50 text-slate-500 border-slate-200')) }}">
                                        {{ $bom->status === 'pending_approval' ? 'Chờ duyệt' : ($bom->status === 'rejected' ? 'Từ chối' : ($bom->is_active ? 'Đang áp dụng' : 'Đã duyệt · cũ')) }}
                                    </span>
                                    @if($bom->rejection_reason)<span class="mt-1 block text-[10px] text-rose-700">{{ $bom->rejection_reason }}</span>@endif
                                </td>
                                <td class="p-2.5 text-center">
                                    @if($bom->status === 'pending_approval' && $canApproveBoms)
                                        <div class="flex min-w-48 flex-col gap-1">
                                            <form method="POST" action="{{ route('boms.approve', $bom) }}" onsubmit="return confirm('Duyệt BOM và đưa vào áp dụng?');">@csrf<button class="w-full bg-emerald-700 px-2 py-1 text-[10px] font-bold uppercase text-white">Duyệt BOM</button></form>
                                            <form method="POST" action="{{ route('boms.reject', $bom) }}" class="flex gap-1">@csrf<input name="reason" required maxlength="2000" placeholder="Lý do từ chối" class="min-w-0 flex-1 border-slate-300 px-1 py-1 text-[10px]"><button class="bg-rose-700 px-2 py-1 text-[10px] font-bold uppercase text-white">Từ chối</button></form>
                                        </div>
                                    @else
                                        <span class="text-[10px] text-slate-500">{{ $bom->approvedBy->name ?? ($bom->submittedBy->name ?? '---') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-8 text-center text-slate-500">Chưa có định mức BOM.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <x-per-page-select />
                @if($boms->hasPages()){{ $boms->links() }}@endif
            </div>
        </div>
    </div>
</x-app-layout>