<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Tính nhu cầu nguyên liệu (MRP)</h2>
            <a href="{{ route('boms.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs font-bold uppercase hover:bg-slate-300">Danh sách BOM</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            <form method="GET" action="{{ route('boms.plan') }}" class="bg-white border border-slate-300 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <label class="text-xs font-semibold text-slate-700">Thành phẩm
                        <select name="product_id" required class="mt-1 w-full text-xs border-slate-300 rounded-none">
                            <option value="">Chọn thành phẩm</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }} · {{ $product->sku }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-xs font-semibold text-slate-700">Số lượng cần sản xuất
                        <div class="mt-1 flex items-center gap-2">
                            <input type="number" name="quantity" value="{{ request('quantity') }}" min="0.0001" step="0.0001" required class="w-full text-xs border-slate-300 rounded-none">
                            <span class="shrink-0 text-slate-500">ĐVT gốc</span>
                        </div>
                    </label>
                    <button type="submit" class="px-3 py-2 bg-slate-800 text-white text-xs font-bold uppercase hover:bg-slate-700">Tính nguyên liệu</button>
                </div>
            </form>

            @if($error)
                <div class="p-3 bg-amber-50 border-l-4 border-amber-500 text-amber-900 text-xs">{{ $error }}</div>
            @endif

            @if($bom)
                <section class="bg-white border border-slate-300">
                    <div class="p-3 bg-slate-100 border-b border-slate-300 flex flex-wrap justify-between gap-2 text-xs">
                        <div><span class="text-slate-500">Thành phẩm:</span> <strong>{{ $bom->product->name }}</strong> · BOM v{{ $bom->version }}</div>
                        <div><span class="text-slate-500">Sản lượng yêu cầu:</span> <strong class="font-mono">{{ number_format((float) request('quantity'), 4) }} {{ $bom->product->unit }}</strong></div>
                        <div><span class="text-slate-500">Thu hồi:</span> <strong>{{ number_format($bom->yield_rate * 100, 2) }}%</strong></div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-600"><th class="p-2.5 border-r">Nguyên liệu cần chuẩn bị</th><th class="p-2.5 border-r text-right">Nhu cầu</th><th class="p-2.5">Đơn vị gốc</th></tr></thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($requirements as $requirement)
                                    <tr><td class="p-2.5 border-r font-medium">{{ $requirement['product_name'] }} <span class="text-slate-500">({{ $requirement['sku'] }})</span></td><td class="p-2.5 border-r text-right font-mono font-bold">{{ number_format($requirement['quantity'], 4) }}</td><td class="p-2.5">{{ $requirement['unit'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 border-t border-slate-200 text-[11px] text-slate-500">Đây là kế hoạch nguyên liệu; chưa giữ hoặc trừ tồn kho.</div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>