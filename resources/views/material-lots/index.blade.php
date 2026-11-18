<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
            @if($stage === 'qa')
                Dược liệu thô · QA cập nhật COA
            @elseif($type === 'raw_material')
                QC nhập phiếu kiểm nghiệm và kết luận OK / NG
            @else
                {{ $typeLabel }} · QC xác định OK / NG
            @endif
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-medium">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-red-50 border border-red-300 text-red-800 text-xs font-medium">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-3 bg-red-50 border border-red-300 text-red-800 text-xs font-medium">{{ $errors->first() }}</div>@endif

            @if($stage === 'qc' && $type !== 'raw_material')
            <div class="bg-white border border-slate-300 p-0">
                <div class="flex flex-wrap gap-0 border-b border-slate-300">
                    <a href="{{ route('qc.material-lots.index', ['type' => 'ingredient']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'ingredient' ? 'border-blue-700 bg-blue-600 text-white' : 'border-transparent bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Nguyên liệu</a>
                    <a href="{{ route('qc.material-lots.index', ['type' => 'accessory']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'accessory' ? 'border-blue-700 bg-blue-600 text-white' : 'border-transparent bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Phụ liệu</a>
                    <a href="{{ route('qc.material-lots.index', ['type' => 'excipient']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'excipient' ? 'border-blue-700 bg-blue-600 text-white' : 'border-transparent bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Tá dược</a>
                    <a href="{{ route('qc.material-lots.index', ['type' => 'packaging']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'packaging' ? 'border-blue-700 bg-blue-600 text-white' : 'border-transparent bg-slate-50 text-slate-600 hover:bg-slate-100' }}">Bao bì</a>
                </div>
            </div>
            @endif

            <!-- Filter & Search -->
            <form method="GET" action="{{ $stage === 'qa' ? route('qa.material-lots.index') : route('qc.material-lots.index') }}" class="bg-white border border-slate-300 p-3 flex flex-wrap items-center gap-2">
                @if($stage === 'qc')
                    <input type="hidden" name="type" value="{{ $type }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm số lô, tên/mã vật tư..." class="w-full sm:w-80 text-xs border-slate-300 py-1.5">
                @if($stage === 'qc')
                    <select name="status" class="text-xs border-slate-300 py-1.5">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="pending_qc_inspection" @selected(request('status') === 'pending_qc_inspection')>Chờ QC kiểm tra</option>
                        <option value="pending_reinspection" @selected(request('status') === 'pending_reinspection')>Chờ kiểm tra lại</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Không đạt</option>
                        <option value="qc_passed_pending_receipt" @selected(request('status') === 'qc_passed_pending_receipt')>Đạt, chờ Kho nhập</option>
                        <option value="active" @selected(request('status') === 'active')>Đã nhập kho</option>
                    </select>
                @endif
                <button class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs uppercase tracking-wider">Tìm</button>
            </form>

            <!-- Table -->
            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2.5 border-r">{{ $typeLabel }}</th>
                            <th class="p-2.5 border-r">Đơn Mua / NCC</th>
                            <th class="p-2.5 border-r text-center">SL Nhập</th>
                            <th class="p-2.5 border-r">{{ $type === 'raw_material' ? 'Lô / NSX / HSD / COA' : 'Quản lý tồn' }}</th>
                            <th class="p-2.5 border-r text-center">Trạng Thái</th>
                            <th class="p-2.5">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($lots as $lot)
                            @php 
                                $isRaw = $lot->material_type === 'raw_material'; 
                                $statusColors = [
                                    'temporary_stored' => 'bg-slate-100 text-slate-800',
                                    'pending_qa' => 'bg-amber-100 text-amber-800',
                                    'pending_qc_inspection' => 'bg-blue-100 text-blue-800',
                                    'pending_reinspection' => 'bg-orange-100 text-orange-800',
                                    'qc_passed_pending_receipt' => 'bg-lime-100 text-lime-800',
                                    'active' => 'bg-emerald-100 text-emerald-800',
                                    'rejected' => 'bg-rose-100 text-rose-800',
                                ];
                            @endphp
                            <tr class="align-top hover:bg-slate-50">
                                <td class="p-2.5 border-r font-medium">
                                    <span class="inline-block mb-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded {{ $isRaw ? 'bg-emerald-100 text-emerald-800' : 'bg-violet-100 text-violet-800' }}">
                                        {{ \App\Models\CatalogItem::labelForType($lot->material_type) }}
                                    </span>
                                    <div class="font-semibold">{{ $lot->catalog_item->name ?? '---' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $lot->catalog_item->sku ?? '' }}</div>
                                </td>
                                <td class="p-2.5 border-r">
                                    <div class="font-semibold">{{ $lot->purchaseOrderItem->purchaseOrder->po_number ?? '---' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $lot->purchaseOrderItem->purchaseOrder->supplier->name ?? '---' }}</div>
                                    @if($lot->materialReceipt)
                                        <div class="mt-1 text-[10px] text-slate-600">Tiếp nhận tạm <a href="{{ route('material-receipts.show', $lot->materialReceipt) }}" target="_blank" class="font-mono font-semibold text-blue-700 underline">{{ $lot->materialReceipt->receipt_code }}</a></div>
                                    @endif
                                </td>
                                <td class="p-2.5 border-r text-center font-mono font-bold">{{ rtrim(rtrim(number_format($lot->quantity, 2), '0'), '.') }}<br><span class="text-slate-500 text-[10px]">{{ $lot->unit }}</span></td>
                                <td class="p-2.5 border-r text-sm">
                                    @if($isRaw)
                                        <div class="font-mono font-bold text-blue-700">{{ $lot->batch_number ?: 'Chưa có lô' }}</div>
                                        <div class="text-[10px] text-slate-500">NSX: {{ $lot->mfg_date?->format('d/m/Y') ?? '---' }} | HSD: {{ $lot->exp_date?->format('d/m/Y') ?? '---' }}</div>
                                        @if($lot->coa_file)
                                            <a href="{{ route('material-lots.coa', $lot) }}" target="_blank" class="text-[10px] text-blue-600 font-semibold underline">Xem COA</a>
                                        @else
                                            <span class="text-[10px] text-amber-700 font-semibold">Chưa có COA</span>
                                        @endif
                                    @else
                                        <div class="text-[10px] text-slate-500">Xuất theo FIFO</div>
                                    @endif
                                </td>
                                <td class="p-2.5 border-r text-center">
                                    <span class="inline-block border px-2 py-1 text-[10px] font-bold rounded {{ $statusColors[$lot->status] ?? 'bg-slate-100 text-slate-800' }}">{{ $lot->status_label }}</span>
                                    @if($lot->qc_note)<div class="text-[10px] text-slate-600 mt-1 p-1 bg-slate-50">{{ $lot->qc_note }}</div>@endif
                                    @foreach($lot->inspections->where('inspection_stage', 'qc_inspection') as $inspection)
                                        <div class="mt-2 border-t border-slate-200 pt-1 text-left text-[10px]">
                                            <div class="font-semibold">{{ $inspection->result === 'passed' ? 'QC: OK' : ($inspection->result === 'failed' ? 'QC: NG' : $inspection->result) }}</div>
                                            @if($inspection->report_number)
                                                <div>Phiếu kiểm nghiệm: {{ $inspection->report_number }} · {{ $inspection->report_date?->format('d/m/Y') }}</div>
                                                @if($inspection->report_file)
                                                    <a href="{{ route('material-lots.inspection-report', $inspection) }}" target="_blank" rel="noopener" class="text-blue-700 underline">Xem phiếu kiểm nghiệm</a>
                                                @endif
                                            @endif
                                            <div class="text-slate-500">{{ $inspection->checker->name ?? '---' }} · {{ $inspection->checked_at?->format('d/m/Y H:i') }}</div>
                                        </div>
                                    @endforeach
                                </td>
                                <td class="p-2.5 space-y-1">
                                    @if($lot->status === 'active' && $lot->stock_receipt_code)
                                        <a href="{{ route('material-lots.stock-receipt', $lot) }}" target="_blank" class="block w-full bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 text-center text-[11px] font-bold uppercase rounded">Phiếu nhập {{ $lot->stock_receipt_code }}</a>
                                    @elseif($stage === 'qa' && $lot->status === 'pending_qa' && $canQa && $isRaw)
                                        <form method="POST" action="{{ route('material-lots.update-coa', $lot) }}" enctype="multipart/form-data" class="space-y-1">
                                            @csrf @method('PATCH')
                                            <input type="text" name="batch_number" value="{{ old('batch_number', $lot->batch_number) }}" placeholder="Lô NCC" required class="w-full text-xs border-slate-300 py-1 font-mono">
                                            <div class="flex gap-1">
                                                <input type="date" name="mfg_date" value="{{ $lot->mfg_date?->format('Y-m-d') }}" required class="flex-1 text-xs border-slate-300 py-1" title="NSX">
                                                <input type="date" name="exp_date" value="{{ $lot->exp_date?->format('Y-m-d') }}" required class="flex-1 text-xs border-slate-300 py-1" title="HSD">
                                            </div>
                                            <input type="file" name="coa_file" accept="application/pdf" class="w-full text-[10px]">
                                            @if($lot->coa_file)
                                                <a href="{{ route('material-lots.coa', $lot) }}" target="_blank" rel="noopener" class="block text-[10px] text-blue-700 underline">Xem COA hiện tại</a>
                                            @endif
                                            <button class="w-full px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] uppercase rounded">Lưu COA</button>
                                        </form>
                                    @elseif($stage === 'qc' && $isRaw && in_array($lot->status, ['pending_qc_inspection', 'pending_reinspection'], true) && $canQc)
                                        <form method="POST" action="{{ route('material-lots.inspect', $lot) }}" enctype="multipart/form-data" class="space-y-1">
                                            @csrf
                                            <input type="text" name="qc_test_report" maxlength="255" placeholder="Số phiếu kiểm nghiệm" required class="w-full text-xs border-slate-300 py-1">
                                            <input type="date" name="qc_date" required class="w-full text-xs border-slate-300 py-1" title="Ngày ra phiếu">
                                            <input type="file" name="qc_test_report_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required class="w-full text-[10px]">
                                            <textarea name="qc_note" class="w-full text-xs border-slate-300 py-1" placeholder="Nhận xét / lý do nếu NG" rows="2"></textarea>
                                            <div class="flex gap-1">
                                                <button name="result" value="passed" class="flex-1 px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] uppercase">OK</button>
                                                <button name="result" value="failed" class="flex-1 px-2 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] uppercase">NG</button>
                                            </div>
                                        </form>
                                    @elseif($stage === 'qc' && !$isRaw && in_array($lot->status, ['pending_qc_inspection', 'pending_reinspection'], true) && $canQc)
                                        <form method="POST" action="{{ route('material-lots.approve', $lot) }}" class="mb-1" onsubmit="return confirm('Xác nhận lô đạt chất lượng?');">
                                            @csrf
                                            <button class="w-full px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] uppercase rounded">OK</button>
                                        </form>
                                        <form method="POST" action="{{ route('material-lots.reject', $lot) }}" class="space-y-1" onsubmit="return confirm('Kết luận lô không đạt?');">
                                            @csrf
                                            <input type="text" name="qc_note" placeholder="Lý do từ chối" required class="w-full text-xs border-slate-300 py-1">
                                            <button class="w-full px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] uppercase rounded">NG</button>
                                        </form>
                                    @elseif($stage === 'qc' && $lot->status === 'rejected' && $canQc && $lot->supplierReturns->isEmpty())
                                        <form method="POST" action="{{ route('material-lots.reinspect', $lot) }}" class="space-y-1" onsubmit="return confirm('Yêu cầu kiểm tra lại?');">
                                            @csrf
                                            <input type="text" name="note" placeholder="Lý do kiểm tra lại" required class="w-full text-xs border-slate-300 py-1">
                                            <textarea name="corrective_action" class="w-full text-xs border-slate-300 py-1" placeholder="Hành động khắc phục" rows="2"></textarea>
                                            <button class="w-full px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] uppercase rounded">Yêu cầu kiểm tra lại</button>
                                        </form>
                                        <a href="{{ route('supplier-returns.index') }}" class="block w-full px-2 py-1 text-center text-[10px] font-bold uppercase text-rose-700 underline">Lập phiếu trả nhà cung cấp</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-6 text-center text-slate-500">{{ $stage === 'qa' ? 'Không có dược liệu thô chờ cập nhật COA.' : 'Không có ' . strtolower($typeLabel) . ' chờ QC xử lý.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $lots->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
