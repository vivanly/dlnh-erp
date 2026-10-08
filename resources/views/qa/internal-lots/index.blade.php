<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Quản lý số lô nội bộ</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $batches->total() }} lô trong hệ thống</p>
            </div>
            @if($canManageLots)
            <a href="{{ route('qa.internal-lots.create') }}" class="inline-flex items-center gap-2 bg-emerald-700 px-3 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-800" aria-label="Tạo lô nội bộ mới">
                <span aria-hidden="true">+</span> Tạo lô
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif

            <form method="GET" action="{{ route('qa.internal-lots.index') }}" class="flex flex-wrap items-center gap-2 border border-slate-300 bg-white p-3">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm số lô, sản phẩm, nguồn gốc, PPCB, PKN, giấy phép" class="w-full max-w-xl border-slate-300 py-1.5 text-xs">
                <select name="status" class="border-slate-300 py-1.5 text-xs">
                    <option value="">Mọi trạng thái</option>
                    @foreach(['planned' => 'Dự kiến', 'pending_qa' => 'Chờ Kho nhập', 'active' => 'Đang hoạt động', 'closed' => 'Đã chốt'] as $status => $label)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="bg-slate-800 px-3 py-1.5 text-xs font-bold uppercase text-white hover:bg-slate-700">Tìm kiếm</button>
                @if(request()->hasAny(['search', 'status']))<a href="{{ route('qa.internal-lots.index') }}" class="px-2 py-1.5 text-xs text-slate-600 hover:text-slate-900">Xóa lọc</a>@endif
            </form>
            <div class="flex justify-end"><x-per-page-select /></div>

            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full min-w-[1450px] border-collapse text-left text-xs">
                    <thead><tr class="border-b border-slate-300 bg-slate-100 text-[10px] uppercase text-slate-600">
                        <th class="p-2.5">Sản phẩm / mã SP</th><th class="p-2.5">Nguồn gốc</th><th class="p-2.5">PPCB</th><th class="p-2.5">Số lô</th><th class="p-2.5">NSX / HSD</th><th class="p-2.5">PKN / ngày cấp</th><th class="p-2.5">Giấy phép</th><th class="p-2.5 text-right">Tồn / chờ nhập / cỡ lô</th><th class="p-2.5">Nguồn tạo / trạng thái</th><th class="p-2.5">Thao tác</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($batches as $batch)
                            <tr class="align-top hover:bg-slate-50">
                                <td class="p-2.5"><strong>{{ $batch->product->name ?? '---' }}</strong><span class="block font-mono text-[10px] text-slate-500">{{ $batch->product->sku ?? '---' }}</span></td>
                                <td class="p-2.5">{{ $batch->origin ?: '---' }}</td>
                                <td class="p-2.5">{{ $batch->ppcb ? $batch->ppcb->ma . ' · ' . $batch->ppcb->ten_ppcb : '---' }}</td>
                                <td class="p-2.5 font-mono font-semibold">{{ $batch->batch_number }}</td>
                                <td class="p-2.5 whitespace-nowrap">{{ optional($batch->mfg_date)->format('d/m/Y') ?: '---' }}<span class="block text-slate-500">{{ optional($batch->exp_date)->format('d/m/Y') ?: '---' }}</span></td>
                                <td class="p-2.5">{{ $batch->qc_test_report ?: 'Chưa có PKN' }}<span class="block text-slate-500">{{ optional($batch->qc_date)->format('d/m/Y') ?: '---' }} · {{ $batch->qc_result === 'passed' ? 'Đạt' : ($batch->qc_result === 'failed' ? 'Không đạt' : 'Chờ QC') }}</span></td>
                                <td class="p-2.5">{{ $batch->license_number ?: '---' }}</td>
                                <td class="p-2.5 text-right font-mono whitespace-nowrap">{{ number_format((float) $batch->current_quantity, 4) }} / {{ number_format((float) $batch->pending_warehouse_quantity, 4) }} / {{ number_format((float) $batch->planned_quantity, 4) }} {{ $batch->unit }}</td>
                                <td class="p-2.5">
                                    @if($batch->orderAllocations->isNotEmpty())
                                        @foreach($batch->orderAllocations as $allocation)
                                            <span class="block font-mono">{{ $allocation->productionOrder?->production_code }} · {{ number_format((float) $allocation->quantity, 4) }} {{ $batch->unit }}</span>
                                        @endforeach
                                    @else
                                        <span>{{ $batch->productionOrder?->production_code ?? 'Lô QA tạo độc lập' }}</span>
                                    @endif
                                    <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">{{ ['planned' => 'Dự kiến', 'pending_qa' => 'Chờ kho nhập', 'active' => 'Đang hoạt động', 'closed' => 'Đã chốt'][$batch->status] ?? $batch->status }}</span>
                                </td>
                                <td class="p-2.5 whitespace-nowrap">
                                    @if($canManageLots)
                                    <a href="{{ route('qa.internal-lots.edit', $batch) }}" class="inline-block border border-slate-300 px-2 py-1 text-[10px] font-semibold text-slate-700 hover:bg-slate-100">Sửa</a>
                                    @php
                                        $appendableOrders = $availableProductionOrders->where('product_id', $batch->product_id);
                                        $canAppendProduction = in_array($batch->status, ['pending_qa', 'active'], true) && !$batch->qa_approved_at && (float) $batch->initial_quantity < (float) $batch->planned_quantity;
                                    @endphp
                                    @if($canAppendProduction && $appendableOrders->isNotEmpty())
                                        <form method="POST" action="{{ route('qa.internal-lots.allocate-production-order', $batch) }}" class="mt-2 flex max-w-xs flex-col gap-1 border-t border-slate-200 pt-2">
                                            @csrf
                                            <select name="production_order_id" required class="w-full border-slate-300 py-1 text-[10px]">
                                                <option value="">Chọn lệnh sản xuất</option>
                                                @foreach($appendableOrders as $availableOrder)
                                                    <option value="{{ $availableOrder->id }}">{{ $availableOrder->production_code }} · {{ number_format((float) $availableOrder->pending_finished_quantity, 4) }} {{ $availableOrder->unit }} còn</option>
                                                @endforeach
                                            </select>
                                            <div class="flex gap-1">
                                                <input type="number" name="quantity" min="0.0001" step="0.0001" max="{{ min((float) $appendableOrders->max('pending_finished_quantity'), max(0, (float) $batch->planned_quantity - (float) $batch->initial_quantity)) }}" placeholder="Số lượng" aria-label="Số lượng cần phân bổ" required class="w-24 border-slate-300 text-right font-mono text-[10px]">
                                                <button class="border border-emerald-700 px-2 py-1 text-[10px] font-semibold text-emerald-800">Phân bổ</button>
                                            </div>
                                        </form>
                                    @endif
                                    @if($batch->can_delete)
                                        <form method="POST" action="{{ route('qa.internal-lots.destroy', $batch) }}" class="inline" onsubmit="return confirm('Xóa lô chưa nhập kho và chưa phát sinh giao dịch?');">@csrf @method('DELETE')<button class="ml-1 border border-rose-300 px-2 py-1 text-[10px] font-semibold text-rose-700 hover:bg-rose-50">Xóa</button></form>
                                    @endif
                                    @else
                                        <span class="text-slate-400">Chỉ xem</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="p-8 text-center text-slate-500">Không tìm thấy lô nội bộ phù hợp.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="flex justify-end border-t border-slate-200 bg-slate-50 p-3">{{ $batches->links() }}</div>
            </div>

            <section class="border border-slate-300 bg-white">
                <div class="border-b border-slate-300 bg-slate-100 p-3">
                    <h3 class="text-xs font-bold uppercase text-slate-700">Gợi ý chốt lô đã hết</h3>
                    <p class="mt-1 text-[10px] text-slate-500">Chỉ gợi ý khi tồn thực tế và lượng chờ nhập đều bằng 0, đã có PKN đạt và không còn lượng giữ cho đơn chưa giao.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[600px] text-left text-xs">
                        <thead><tr class="border-b border-slate-200 text-[10px] uppercase text-slate-500"><th class="p-2.5">Số lô</th><th class="p-2.5">Sản phẩm</th><th class="p-2.5">PKN</th><th class="p-2.5">Thao tác</th></tr></thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($lotsToClose as $batch)
                                <tr>
                                    <td class="p-2.5 font-mono font-semibold">{{ $batch->batch_number }}</td>
                                    <td class="p-2.5">{{ $batch->product->name ?? '---' }}</td>
                                    <td class="p-2.5">{{ $batch->qc_test_report }} · {{ optional($batch->qc_date)->format('d/m/Y') }}</td>
                                    <td class="p-2.5">@if($canManageLots)<form method="POST" action="{{ route('qa.internal-lots.close', $batch) }}" onsubmit="return confirm('Chốt lô đã hết tồn và không còn đơn chưa giao?');">@csrf @method('PATCH')<button class="bg-slate-800 px-2 py-1 text-[10px] font-bold uppercase text-white">Chốt lô</button></form>@else<span class="text-slate-400">Chỉ xem</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-5 text-center text-slate-500">Chưa có lô nào đủ điều kiện chốt.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $lotsToClose->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
