<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Lệnh sản xuất <span class="font-mono text-blue-700">{{ $productionOrder->production_code }}</span></h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $productionOrder->product->name }}
                    @if($productionOrder->order)
                        · Đơn bán {{ $productionOrder->order->order_code }}
                    @elseif($productionOrder->monthlyPlanLine?->plan)
                        · Kế hoạch tháng {{ $productionOrder->monthlyPlanLine->plan->plan_month->format('m/Y') }}
                    @endif
                    · Sản lượng kế hoạch {{ number_format($productionOrder->planned_quantity, 4) }} {{ $productionOrder->unit }}
                </p>
            </div>
            <a href="{{ route('production-orders.index') }}" class="px-3 py-1.5 bg-slate-200 text-xs font-bold uppercase text-slate-700 hover:bg-slate-300">Quay lại</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="border border-slate-300 bg-white">
                <div class="border-b border-slate-300 bg-slate-100 p-3">
                    <h3 class="text-xs font-bold uppercase text-slate-700">Lô thành phẩm</h3>
                    <p class="mt-1 text-[10px] text-slate-500">Mã lô chưa được tạo trước khi sản xuất. Sau khi chốt sản lượng, QA cấp một hoặc nhiều mã lô cho lượng hoàn thành; Kho nhập theo từng mã.</p>
                </div>
                <div class="divide-y divide-slate-200">
                    @forelse($productionOrder->allFinishedBatches as $finishedBatch)
                        <div class="flex flex-wrap items-center justify-between gap-2 p-3 text-xs">
                            <strong class="font-mono">{{ $finishedBatch->batch_number }}</strong>
                            <span>Cỡ lô {{ number_format((float) $finishedBatch->planned_quantity, 4) }} {{ $finishedBatch->unit }} · Đã xếp nhập {{ number_format((float) $finishedBatch->initial_quantity, 4) }} · {{ $finishedBatch->status }}</span>
                        </div>
                    @empty
                        <div class="p-3 text-xs text-slate-500">Chưa có lô thành phẩm. QA sẽ cấp mã sau khi lệnh hoàn thành.</div>
                    @endforelse
                </div>
                @if($productionOrder->materials->isNotEmpty())
                    <div class="divide-y divide-slate-200">
                        @foreach($productionOrder->materials as $material)
                            <div class="flex items-center justify-between gap-3 p-3 text-xs">
                                <strong>{{ $material->display_name }}</strong>
                                <span>Đã xuất: <strong class="font-mono">{{ number_format($material->issued_quantity, 4) }} {{ $material->unit }}</strong></span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            @if($canIssueMaterials && $productionOrder->status === 'released')
                <form method="POST" action="{{ route('production-orders.issue-materials', $productionOrder) }}" class="bg-white border border-slate-300" onsubmit="return confirm('Xác nhận phiếu xuất nguyên liệu theo số lượng thực tế?');">
                    @csrf
                    <div class="p-3 bg-amber-50 border-b border-amber-200">
                        <h3 class="text-xs font-bold uppercase text-amber-900">Kho xác nhận phiếu xuất nguyên liệu sản xuất</h3>
                        <p class="mt-1 text-xs text-amber-800">Chọn lô NCC của nguyên liệu thô hoặc đợt mua của phụ liệu (tồn lấy từ kho nguyên liệu) và nhập số lượng thực xuất cho từng lô (không phụ thuộc BOM). Số lượng khả dụng đã trừ các phần đang được giữ cho đơn khác.</p>
                    </div>
                    <div class="divide-y divide-slate-200">
                        <div class="p-3">
                            <h4 class="mb-2 text-xs font-bold uppercase text-slate-700">Nguyên liệu thô (chọn lô NCC) &amp; phụ liệu (chọn loại, trừ theo tổng)</h4>
                            @forelse($materialLots->groupBy(fn ($l) => $l->type . '|' . $l->id) as $group)
                                @php $first = $group->first(); @endphp
                                <div class="mb-3 text-xs"><strong>{{ $first->item->name }}</strong> <span class="text-slate-500">· {{ $first->type === 'accessory' ? 'Phụ liệu' : 'Nguyên liệu thô' }}</span>
                                    <table class="mt-1 w-full border border-slate-200 text-[11px]">
                                        <thead class="bg-slate-100 text-left uppercase text-slate-600"><tr><th class="px-2 py-1.5">{{ $first->type === 'accessory' ? 'Tồn tổng' : 'Lô NCC' }}</th><th class="px-2 py-1.5">Hạn dùng</th><th class="px-2 py-1.5 text-right">Tồn</th><th class="px-2 py-1.5 w-40 text-right">Số lượng xuất</th></tr></thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @foreach($group as $lot)
                                                @php $i = $loop->parent->index . '_' . $loop->index; @endphp
                                                <tr>
                                                    <td class="px-2 py-1.5 font-mono font-semibold">{{ $first->type === 'accessory' ? 'Toàn bộ' : ($lot->batch ?: '(không số lô)') }}</td>
                                                    <td class="px-2 py-1.5">{{ $lot->exp_date ? \Illuminate\Support\Carbon::parse($lot->exp_date)->format('d/m/Y') : 'Không hạn' }}</td>
                                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format($lot->balance, 4) }} {{ $lot->item->unit }}</td>
                                                    <td class="px-2 py-1.5">
                                                        <input type="hidden" name="material_lots[{{ $i }}][type]" value="{{ $lot->type }}"><input type="hidden" name="material_lots[{{ $i }}][id]" value="{{ $lot->id }}"><input type="hidden" name="material_lots[{{ $i }}][batch]" value="{{ $lot->batch }}">
                                                        <input type="number" name="material_lots[{{ $i }}][quantity]" value="0" min="0" max="{{ $lot->balance }}" step="0.0001" class="w-full text-right font-mono text-xs border-slate-300 rounded-none">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @empty
                                <div class="text-xs text-slate-500">Chưa có nguyên liệu thô/phụ liệu còn tồn.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="p-3 border-t border-slate-200 flex justify-end">
                        <button class="px-4 py-2 bg-amber-600 text-xs font-bold uppercase text-white hover:bg-amber-700">Lập phiếu xuất và trừ kho</button>
                    </div>
                </form>
            @endif

            @if($canWorkInProduction && $productionOrder->status === 'materials_issued')
                <form method="POST" action="{{ route('production-orders.report-output', $productionOrder) }}" class="space-y-3 border border-slate-300 bg-white p-4" onsubmit="return confirm('Xac nhan san luong thuc te va nguyen lieu du kien tra kho?');">
                    @csrf
                    <div class="border-b border-slate-200 bg-emerald-50 p-3">
                        <h3 class="text-xs font-bold uppercase text-emerald-900">San xuat xac nhan san luong hoan thanh</h3>
                        <p class="mt-1 text-xs text-emerald-800">Nhap san luong thuc te va luong nguyen lieu chua su dung du kien tra kho. Kho se kiem tra luong tra thuc te truoc khi hoan tat lenh.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 p-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold">San luong thuc te <span class="text-rose-600">*</span>
                            <input type="number" name="actual_quantity" min="0.0001" step="0.0001" value="{{ old('actual_quantity', $productionOrder->planned_quantity) }}" required class="mt-1 w-full border-slate-300 text-right font-mono text-sm">
                        </label>
                        <label class="text-xs font-semibold">Ngay san xuat
                            <input type="date" name="mfg_date" value="{{ date('Y-m-d') }}" class="mt-1 w-full border-slate-300 text-sm">
                        </label>
                    </div>
                    <div class="border-t border-slate-200 pt-3">
                        <h3 class="text-xs font-bold uppercase text-slate-700">Nguyen lieu chua su dung, du kien tra kho</h3>
                        <p class="mt-1 text-[11px] text-slate-500">Nhap luong du kien tra theo tung lo. Luong tieu hao bang luong da xuat tru luong kho xac nhan tra.</p>
                        <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
                            @foreach($productionOrder->materials as $material)
                                @foreach($material->lots as $allocation)
                                    @if((float) $allocation->issued_quantity > 0)
                                        @php $sourceLotCode = $allocation->supplierBatch?->batch_number ?? $allocation->batch_number ?? 'Lo nha cung cap'; @endphp
                                        <label class="flex items-center justify-between gap-3 border border-slate-200 p-2 text-xs">
                                            <span>{{ $material->display_name }} - {{ $sourceLotCode }}<span class="block text-[10px] text-slate-500">Da xuat {{ number_format((float) $allocation->issued_quantity, 4) }} {{ $material->unit }}</span></span>
                                            <input type="number" name="reported_returned_materials[{{ $allocation->id }}]" value="{{ old('reported_returned_materials.'.$allocation->id, 0) }}" min="0" max="{{ $allocation->issued_quantity }}" step="0.0001" class="w-32 border-slate-300 text-right font-mono text-xs" aria-label="Luong du kien tra cua lo {{ $sourceLotCode }}">
                                        </label>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end border-t border-slate-200 pt-3"><button class="bg-emerald-700 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-800">Gui xac nhan san luong</button></div>
                </form>
            @endif
            @if($canIssueMaterials && $productionOrder->status === 'production_reported')
                <form method="POST" action="{{ route('production-orders.confirm-completion', $productionOrder) }}" class="bg-white border border-slate-300 p-4 space-y-3" onsubmit="return confirm('Xác nhận lượng nguyên liệu trả kho và hoàn thành lệnh sản xuất?');">
                    @csrf
                    <div class="border-b border-slate-200 p-3">
                        <h3 class="text-xs font-bold uppercase text-emerald-900">Kho kiểm tra và xác nhận hoàn thành lệnh</h3>
                        <p class="mt-1 text-xs text-slate-600">Sản xuất đã báo cáo {{ number_format((float) $productionOrder->actual_quantity, 4) }} {{ $productionOrder->unit }}. Kho xác nhận lượng nguyên liệu thực tế nhận lại; lượng tiêu hao được tính bằng lượng đã xuất trừ lượng trả kho.</p>
                    </div>
                    <div class="space-y-2">
                        @foreach($productionOrder->materials as $material)
                            @foreach($material->lots as $allocation)
                                @if((float) $allocation->issued_quantity > 0)
                                    @php $sourceLotCode = $allocation->supplierBatch?->batch_number ?? $allocation->batch_number ?? 'Lô nhà cung cấp'; @endphp
                                    <label class="flex items-center justify-between gap-3 border border-slate-200 p-2 text-xs">
                                            <span>{{ $material->display_name }} · {{ $sourceLotCode }}<span class="block text-[10px] text-slate-500">SX du kien tra {{ number_format((float) $allocation->reported_returned_quantity, 4) }} / da xuat {{ number_format((float) $allocation->issued_quantity, 4) }} {{ $material->unit }}</span></span>
                                            <input type="number" name="returned_materials[{{ $allocation->id }}]" value="{{ old('returned_materials.'.$allocation->id, $allocation->reported_returned_quantity) }}" min="0" max="{{ $allocation->issued_quantity }}" step="0.0001" class="w-32 text-right font-mono text-xs border-slate-300 rounded-none" aria-label="Kho xac nhan luong tra cua lo {{ $sourceLotCode }}">
                                    </label>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                    <div class="flex justify-end border-t border-slate-200 pt-3"><button class="px-4 py-2 bg-emerald-700 text-xs font-bold uppercase text-white hover:bg-emerald-800">Xác nhận hoàn thành lệnh</button></div>
                </form>
            @endif

            @if($productionOrder->status === 'production_reported')
                <div class="border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900">Sản xuất đã báo cáo sản lượng lúc {{ $productionOrder->production_reported_at?->format('d/m/Y H:i') }}. Đang chờ Kho xác nhận.</div>
            @endif

            @if($productionOrder->status === 'completed')
                <section class="border border-amber-300 bg-amber-50 p-3 text-xs text-amber-900">
                    Còn {{ number_format((float) $productionOrder->pending_finished_quantity, 4) }} {{ $productionOrder->unit }} sản lượng thực tế chưa nhập vào lô thành phẩm.
                </section>
            @endif

            @if($productionOrder->status === 'completed')
                <div class="bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-800">Lệnh đã hoàn tất với sản lượng thực tế {{ number_format($productionOrder->actual_quantity, 4) }} {{ $productionOrder->unit }}.</div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('click', function (event) {
            if (event.target.closest('.add-qa-lot')) {
                const rows = document.querySelector('.qa-lot-rows');
                const template = rows.querySelector('.qa-lot-row');
                const rowIndex = Number(rows.dataset.nextIndex || 0);
                const newRow = template.cloneNode(true);
                newRow.querySelectorAll('input').forEach(function (inputField) {
                    inputField.name = inputField.name.replace(/finished_lots\[\d+\]/, 'finished_lots[' + rowIndex + ']');
                    inputField.value = '';
                });
                rows.append(newRow);
                rows.dataset.nextIndex = String(rowIndex + 1);
            }

            if (event.target.closest('.remove-qa-lot')) {
                const rows = event.target.closest('.qa-lot-rows');
                if (rows.querySelectorAll('.qa-lot-row').length > 1) event.target.closest('.qa-lot-row').remove();
            }
        });
    </script>
    @endpush
</x-app-layout>
