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
                    @forelse($productionOrder->finishedBatches as $finishedBatch)
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
                                <strong>{{ $material->product->name }}</strong>
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
                        <p class="mt-1 text-xs text-amber-800">Chọn trực tiếp lô nguyên liệu NCC và nhập số lượng thực xuất cho từng lô (không phụ thuộc BOM). Số lượng khả dụng đã trừ các phần đang được giữ cho đơn khác.</p>
                    </div>
                    <div class="divide-y divide-slate-200">
                        <div class="border-b border-slate-200 p-3">
                            <label for="supplier-batch-search" class="mb-1 block text-xs font-semibold text-slate-700">Tìm sản phẩm hoặc lô nhà cung cấp</label>
                            <input id="supplier-batch-search" type="search" class="w-full max-w-md text-xs border-slate-300 rounded-none py-1.5" placeholder="Nhập tên sản phẩm hoặc mã lô NCC...">
                        </div>
                        @forelse($supplierBatches->where('available_quantity', '>', 0)->groupBy('product_id') as $productBatches)
                            <section class="supplier-batch-group p-3" data-searchable="{{ $productBatches->first()->product->name }} {{ $productBatches->pluck('batch_number')->implode(' ') }}">
                                <div class="mb-2 text-xs"><strong>{{ $productBatches->first()->product->name }}</strong></div>
                                <div class="overflow-x-auto border border-slate-200">
                                    <table class="w-full text-[11px]">
                                        <thead class="bg-slate-100 text-left uppercase text-slate-600">
                                            <tr>
                                                <th class="px-2 py-1.5">Lô NCC</th>
                                                <th class="px-2 py-1.5">Hạn dùng</th>
                                                <th class="px-2 py-1.5 text-right">Khả dụng</th>
                                                <th class="px-2 py-1.5 w-40 text-right">Số lượng xuất</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200">
                                            @foreach($productBatches as $batch)
                                                <tr>
                                                    <td class="px-2 py-1.5 font-mono font-semibold">{{ $batch->batch_number }}</td>
                                                    <td class="px-2 py-1.5">{{ $batch->exp_date?->format('d/m/Y') ?? 'Không hạn' }}</td>
                                                    <td class="px-2 py-1.5 text-right font-mono">{{ number_format($batch->available_quantity, 4) }} {{ $batch->product->unit }}</td>
                                                    <td class="px-2 py-1.5">
                                                        <input type="number" name="lots[{{ $batch->id }}]" value="{{ old("lots.{$batch->id}", 0) }}" min="0" max="{{ $batch->available_quantity }}" step="0.0001" class="w-full text-right font-mono text-xs border-slate-300 rounded-none" aria-label="Số lượng xuất từ lô {{ $batch->batch_number }}">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @empty
                            <div class="p-3 text-xs text-slate-500">Chưa có lô nguyên liệu đạt QC còn khả dụng.</div>
                        @endforelse
                        <div id="supplier-batch-search-empty" class="hidden p-3 text-xs text-slate-500">Không tìm thấy sản phẩm hoặc lô NCC phù hợp.</div>
                    </div>
                    <div class="p-3 border-t border-slate-200 flex justify-end">
                        <button class="px-4 py-2 bg-amber-600 text-xs font-bold uppercase text-white hover:bg-amber-700">Lập phiếu xuất và trừ kho</button>
                    </div>
                </form>
            @endif

            @if($canIssueMaterials && $productionOrder->status === 'materials_issued')
                <form method="POST" action="{{ route('production-orders.receive-finished-batch', $productionOrder) }}" class="bg-white border border-slate-300 p-4 space-y-3" onsubmit="return confirm('Xác nhận sản lượng thực tế và lượng nguyên liệu trả kho?');">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 border-b border-slate-200 p-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold">Sản lượng thực tế Sản xuất
                            <input type="number" name="actual_quantity" min="0.0001" step="0.0001" value="{{ old('actual_quantity', $productionOrder->planned_quantity) }}" required class="mt-1 w-full text-right font-mono text-xs border-slate-300 rounded-none">
                        </label>
                        <label class="text-xs font-semibold">Ngày sản xuất<input type="date" name="mfg_date" value="{{ date('Y-m-d') }}" class="mt-1 w-full text-xs border-slate-300 rounded-none"></label>
                    </div>
                    <div class="border-t border-slate-200 pt-3">
                        <h3 class="text-xs font-bold uppercase text-slate-700">Nguyên liệu chưa dùng trả kho</h3>
                        <p class="mt-1 text-[10px] text-slate-500">Nhập lượng trả theo từng lô; lượng tiêu hao truy xuất bằng lượng xuất trừ lượng trả.</p>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($productionOrder->materials as $material)
                                @foreach($material->lots as $allocation)
                                    @if((float) $allocation->issued_quantity > 0)
                                        @php $sourceLotCode = $allocation->supplierBatch?->batch_number ?? 'Lô NCC'; @endphp
                                        <label class="flex items-center justify-between gap-3 border border-slate-200 p-2 text-xs">
                                            <span>{{ $material->product->name }} · {{ $sourceLotCode }}<span class="block text-[10px] text-slate-500">Đã xuất {{ number_format((float) $allocation->issued_quantity, 4) }} {{ $material->unit }}</span></span>
                                            <input type="number" name="returned_materials[{{ $allocation->id }}]" value="{{ old('returned_materials.'.$allocation->id, 0) }}" min="0" max="{{ $allocation->issued_quantity }}" step="0.0001" class="w-32 text-right font-mono text-xs border-slate-300 rounded-none" aria-label="Lượng trả về lô {{ $sourceLotCode }}">
                                        </label>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    <div class="flex justify-end"><button class="px-3 py-2 bg-emerald-600 text-xs font-bold uppercase text-white hover:bg-emerald-700">Chốt sản lượng sản xuất</button></div>
                </form>
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
    <script>
        const supplierBatchSearch = document.getElementById('supplier-batch-search');
        if (supplierBatchSearch) {
            supplierBatchSearch.addEventListener('input', function () {
                const search = this.value.trim().toLocaleLowerCase();
                const groups = document.querySelectorAll('.supplier-batch-group');
                let visibleGroups = 0;

                groups.forEach(function (group) {
                    const matches = group.dataset.searchable.toLocaleLowerCase().includes(search);
                    group.hidden = !matches;
                    visibleGroups += matches ? 1 : 0;
                });

                const emptyMessage = document.getElementById('supplier-batch-search-empty');
                emptyMessage.classList.toggle('hidden', visibleGroups > 0 || search === '');
            });
        }
    </script>
</x-app-layout>