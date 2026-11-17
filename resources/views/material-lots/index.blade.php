<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
            @if($type === 'raw_material')
                Dược Liệu Thô · QA Cập Nhật COA → QC Kiểm Tra → Kho Nhập Chính
            @else
                {{ $typeLabel }} · QC Kiểm Tra → Kho Nhập Chính
            @endif
        </h2>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-medium">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-red-50 border border-red-300 text-red-800 text-xs font-medium">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-3 bg-red-50 border border-red-300 text-red-800 text-xs font-medium">{{ $errors->first() }}</div>@endif

            <!-- Tabs Loại Vật Tư -->
            <div class="bg-white border border-slate-300 p-0">
                <div class="flex flex-wrap gap-0 border-b border-slate-300">
                    <a href="{{ route('material-lots.index', ['type' => 'raw_material']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'raw_material' ? 'border-emerald-500 text-emerald-700 bg-emerald-50' : 'border-transparent text-slate-600 hover:bg-slate-50' }}">🌿 Dược Liệu Thô</a>
                    <a href="{{ route('material-lots.index', ['type' => 'ingredient']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'ingredient' ? 'border-blue-500 text-blue-700 bg-blue-50' : 'border-transparent text-slate-600 hover:bg-slate-50' }}">📦 Nguyên Liệu</a>
                    <a href="{{ route('material-lots.index', ['type' => 'accessory']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'accessory' ? 'border-violet-500 text-violet-700 bg-violet-50' : 'border-transparent text-slate-600 hover:bg-slate-50' }}">🔧 Phụ Liệu</a>
                    <a href="{{ route('material-lots.index', ['type' => 'excipient']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'excipient' ? 'border-purple-500 text-purple-700 bg-purple-50' : 'border-transparent text-slate-600 hover:bg-slate-50' }}">⚗️ Tá Dược</a>
                    <a href="{{ route('material-lots.index', ['type' => 'packaging']) }}" class="flex-1 text-center px-3 py-2 text-xs font-bold uppercase border-b-2 {{ $type === 'packaging' ? 'border-pink-500 text-pink-700 bg-pink-50' : 'border-transparent text-slate-600 hover:bg-slate-50' }}">📮 Bao Bì</a>
                </div>
            </div>

            <!-- Filter & Search -->
            <form method="GET" class="bg-white border border-slate-300 p-3 flex flex-wrap items-center gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm số lô, tên/mã vật tư..." class="w-full sm:w-80 text-xs border-slate-300 py-1.5">
                <select name="status" class="text-xs border-slate-300 py-1.5">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="temporary_stored" @selected(request('status') === 'temporary_stored')>Kho Tạm</option>
                    <option value="pending_qa" @selected(request('status') === 'pending_qa')>Chờ QA</option>
                    <option value="pending_qc_inspection" @selected(request('status') === 'pending_qc_inspection')>Chờ QC</option>
                    <option value="pending_reinspection" @selected(request('status') === 'pending_reinspection')>Kiểm Tra Lại</option>
                    <option value="qc_passed_pending_receipt" @selected(request('status') === 'qc_passed_pending_receipt')>QC Đạt - Chờ Kho</option>
                    <option value="active" @selected(request('status') === 'active')>Đã Vào Tồn</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Từ Chối</option>
                </select>
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
                            <th class="p-2.5 border-r">{{ $type === 'raw_material' ? 'Lô / NSX / HSD / COA' : 'FIFO' }}</th>
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
                                        <div class="font-mono font-bold text-blue-700">{{ $lot->batch_number ?: '❌ Chưa có lô' }}</div>
                                        <div class="text-[10px] text-slate-500">NSX: {{ $lot->mfg_date?->format('d/m/Y') ?? '---' }} | HSD: {{ $lot->exp_date?->format('d/m/Y') ?? '---' }}</div>
                                        @if($lot->coa_file)
                                            <a href="{{ route('material-lots.coa', $lot) }}" target="_blank" class="text-[10px] text-blue-600 font-semibold underline">📄 Xem COA</a>
                                        @else
                                            <span class="text-[10px] text-amber-700 font-semibold">❌ Chưa có COA</span>
                                        @endif
                                    @else
                                        <div class="text-[10px] text-slate-500">Xuất theo FIFO</div>
                                    @endif
                                </td>
                                <td class="p-2.5 border-r text-center">
                                    <span class="inline-block border px-2 py-1 text-[10px] font-bold rounded {{ $statusColors[$lot->status] ?? 'bg-slate-100 text-slate-800' }}">{{ $lot->status_label }}</span>
                                    @if($lot->qc_note)<div class="text-[10px] text-slate-600 mt-1 p-1 bg-slate-50">{{ $lot->qc_note }}</div>@endif
                                </td>
                                <td class="p-2.5 space-y-1">
                                    @if($lot->status === 'active' && $lot->stock_receipt_code)
                                        <a href="{{ route('material-lots.stock-receipt', $lot) }}" target="_blank" class="block w-full bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 text-center text-[11px] font-bold uppercase rounded">📋 {{ $lot->stock_receipt_code }}</a>
                                    @elseif($lot->status === 'pending_qa' && $canQa && $isRaw)
                                        <form method="POST" action="{{ route('material-lots.update-coa', $lot) }}" enctype="multipart/form-data" class="space-y-1">
                                            @csrf @method('PATCH')
                                            <input type="text" name="batch_number" value="{{ old('batch_number', $lot->batch_number) }}" placeholder="Lô NCC" required class="w-full text-xs border-slate-300 py-1 font-mono">
                                            <div class="flex gap-1">
                                                <input type="date" name="mfg_date" value="{{ $lot->mfg_date?->format('Y-m-d') }}" required class="flex-1 text-xs border-slate-300 py-1" title="NSX">
                                                <input type="date" name="exp_date" value="{{ $lot->exp_date?->format('Y-m-d') }}" required class="flex-1 text-xs border-slate-300 py-1" title="HSD">
                                            </div>
                                            <input type="file" name="coa_file" accept="application/pdf" class="w-full text-[10px]">
                                            <button class="w-full px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] uppercase rounded">💾 QA Lưu</button>
                                        </form>
                                    @elseif(in_array($lot->status, ['pending_qc_inspection', 'pending_reinspection']) && $canQc && !$lot->needsQa())
                                        <form method="POST" action="{{ route('material-lots.approve', $lot) }}" class="mb-1" onsubmit="return confirm('✅ XÁC NHẬN LÔ ĐẠT CHẤT LƯỢNG?');">
                                            @csrf
                                            <textarea name="qc_note" class="w-full text-xs border-slate-300 py-1 mb-1" placeholder="Ghi chú QC (tùy chọn)" rows="2"></textarea>
                                            <button class="w-full px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] uppercase rounded">✅ Xác Nhận Đạt</button>
                                        </form>
                                        <form method="POST" action="{{ route('material-lots.reject', $lot) }}" class="space-y-1" onsubmit="return confirm('❌ KẾT LUẬN LÔ KHÔNG ĐẠT?');">
                                            @csrf
                                            <input type="text" name="qc_note" placeholder="Lý do từ chối" required class="w-full text-xs border-slate-300 py-1">
                                            <textarea name="detailed_findings" class="w-full text-xs border-slate-300 py-1" placeholder="Chi tiết phát hiện (tùy chọn)" rows="2"></textarea>
                                            <button class="w-full px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] uppercase rounded">❌ Từ Chối</button>
                                        </form>
                                    @elseif($lot->status === 'rejected' && $canQc && $lot->supplierReturns->isEmpty())
                                        <form method="POST" action="{{ route('material-lots.reinspect', $lot) }}" class="space-y-1" onsubmit="return confirm('🔄 YÊU CẦU KIỂM TRA LẠI?');">
                                            @csrf
                                            <input type="text" name="note" placeholder="Lý do kiểm tra lại" required class="w-full text-xs border-slate-300 py-1">
                                            <textarea name="corrective_action" class="w-full text-xs border-slate-300 py-1" placeholder="Hành động khắc phục" rows="2"></textarea>
                                            <button class="w-full px-2 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold text-[10px] uppercase rounded">🔄 Kiểm Lại</button>
                                        </form>
                                        <a href="{{ route('supplier-returns.index') }}" class="block w-full px-2 py-1 text-center text-[10px] font-bold uppercase text-rose-700 underline">📤 Trả NCC</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-6 text-center text-slate-500">❌ Không có {{ strtolower($typeLabel) }} nào cần xử lý</td></tr>
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

            <div class="bg-white border border-slate-300 overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2.5 border-r">{{ $typeLabel }}</th>
                            <th class="p-2.5 border-r">Đơn mua / NCC</th>
                            <th class="p-2.5 border-r text-center">SL nhập</th>
                            <th class="p-2.5 border-r">{{ $type === 'raw_material' ? 'Số lô NCC / NSX / HSD / COA' : 'FIFO' }}</th>
                            <th class="p-2.5 border-r text-center w-40">Trạng thái</th>
                            <th class="p-2.5 w-96">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($lots as $lot)
                            @php $pending = in_array($lot->status, ['pending_qa', 'pending_reinspection'], true); $isRaw = $lot->material_type === 'raw_material'; @endphp
                            <tr class="align-top">
                                <td class="p-2.5 border-r font-medium"><span class="block text-[10px] font-bold uppercase {{ $isRaw ? 'text-emerald-700' : 'text-violet-700' }}">{{ \App\Models\CatalogItem::labelForType($lot->material_type) }}</span>{{ $lot->catalog_item->name ?? '---' }} <span class="font-mono text-slate-500">({{ $lot->catalog_item->sku ?? '' }})</span></td>
                                <td class="p-2.5 border-r">
                                    <div class="font-mono font-semibold">{{ $lot->purchaseOrderItem->purchaseOrder->po_number ?? '---' }}</div>
                                    <div class="text-[10px] text-slate-500">{{ $lot->purchaseOrderItem->purchaseOrder->supplier->name ?? '' }}</div>
                                    @if($lot->materialReceipt)
                                        <div class="mt-1 text-[10px] text-slate-600">Tiếp nhận tạm <a href="{{ route('material-receipts.show', $lot->materialReceipt) }}" target="_blank" class="font-mono font-semibold text-blue-700 underline">{{ $lot->materialReceipt->receipt_code }}</a></div>
                                        <div class="text-[10px] text-slate-500">QA: {{ $lot->materialReceipt->preliminaryQa->name ?? '---' }} · QC: {{ $lot->materialReceipt->preliminaryQc->name ?? '---' }}</div>
                                        @if($lot->materialReceipt->preliminary_note)<div class="text-[10px] text-slate-500">{{ $lot->materialReceipt->preliminary_note }}</div>@endif
                                    @endif
                                </td>
                                <td class="p-2.5 border-r text-center font-mono font-bold">{{ rtrim(rtrim(number_format($lot->quantity, 4), '0'), '.') }} {{ $lot->unit }}</td>
                                <td class="p-2.5 border-r">
                                    @if($isRaw)
                                    <div class="font-mono font-bold text-blue-600">{{ $lot->batch_number ?: 'Chưa có số lô' }}</div>
                                    <div class="text-[10px] text-slate-500">NSX {{ $lot->mfg_date?->format('d/m/Y') ?? '---' }} | HSD {{ $lot->exp_date?->format('d/m/Y') ?? '---' }}</div>
                                    @if($lot->coa_file)<a href="{{ route('material-lots.coa', $lot) }}" target="_blank" class="text-[10px] text-blue-600 underline">Xem COA</a>@else<span class="text-[10px] text-amber-700">Chưa có COA</span>@endif
                                    @else
                                    <div class="text-[10px] text-slate-500">Không yêu cầu COA; xuất theo FIFO từng lần nhập</div>
                                    @endif
                                </td>
                                <td class="p-2.5 border-r text-center">
                                    <span class="inline-block border px-2 py-0.5 text-[10px] font-bold {{ $lot->status === 'active' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : ($lot->status === 'rejected' ? 'bg-rose-100 text-rose-800 border-rose-300' : 'bg-amber-100 text-amber-800 border-amber-300') }}">{{ $lot->status_label }}</span>
                                    @if($lot->qc_note)<div class="text-[10px] text-slate-500 mt-1">{{ $lot->qc_note }}</div>@endif
                                    @if($lot->status === 'qc_passed_pending_receipt')
                                        <div class="mt-1 text-[10px] font-semibold text-amber-700">Đang chờ Kho lập phiếu nhập chính thức.</div>
                                    @endif
                                    @foreach($lot->inspections as $inspection)
                                        <div class="mt-1 border-t border-slate-100 pt-1 text-[10px] text-slate-500">
                                            {{ ['passed' => 'Đạt', 'failed' => 'Không đạt', 'reinspection_requested' => 'Yêu cầu kiểm tra lại'][$inspection->result] ?? $inspection->result }}
                                            · {{ $inspection->checker->name ?? '---' }} · {{ $inspection->checked_at?->format('d/m/Y H:i') }}
                                            @if($inspection->note)<span class="block">{{ $inspection->note }}</span>@endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="p-2.5">
                                    @if($lot->status === 'active' && $lot->stock_receipt_code)
                                        <a href="{{ route('material-lots.stock-receipt', $lot) }}" target="_blank" class="mb-2 block w-full bg-emerald-50 px-2 py-1.5 text-center text-[11px] font-bold text-emerald-800 underline">Phiếu nhập kho {{ $lot->stock_receipt_code }}</a>
                                    @endif
                                    @if($pending && $canQa && $lot->needsQa())
                                        <form method="POST" action="{{ route('material-lots.update-coa', $lot) }}" enctype="multipart/form-data" class="grid grid-cols-2 gap-1 mb-2">
                                            @csrf @method('PATCH')
                                            @if($isRaw)
                                                <input type="text" name="batch_number" value="{{ old('batch_number', $lot->batch_number) }}" placeholder="Số lô NCC" required class="col-span-2 text-xs border-slate-300 py-1 font-mono">
                                                <input type="date" name="mfg_date" value="{{ $lot->mfg_date?->format('Y-m-d') }}" required class="text-xs border-slate-300 py-1" title="NSX">
                                                <input type="date" name="exp_date" value="{{ $lot->exp_date?->format('Y-m-d') }}" required class="text-xs border-slate-300 py-1" title="HSD">
                                            @endif
                                            <input type="file" name="coa_file" accept="application/pdf" @required(!$lot->coa_file) class="col-span-2 text-[11px]">
                                            <button class="col-span-2 px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] uppercase">QA lưu COA{{ $isRaw ? ' / lô NCC' : '' }}</button>
                                        </form>
                                    @endif
                                    @if($pending && $canQc)
                                        @if(!$lot->needsQa())
                                            <form method="POST" action="{{ route('material-lots.approve', $lot) }}" class="mb-1" onsubmit="return confirm('QC xác nhận lô này đạt chất lượng? Kho sẽ lập phiếu nhập chính thức ở bước tiếp theo.');">
                                                @csrf
                                                <button class="w-full px-2 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] uppercase">QC xác nhận đạt</button>
                                            </form>
                                            <form method="POST" action="{{ route('material-lots.reject', $lot) }}" class="flex gap-1" onsubmit="return confirm('Kết luận lô này KHÔNG đạt?');">
                                                @csrf
                                                <input type="text" name="qc_note" placeholder="Lý do không đạt" required class="flex-1 text-xs border-slate-300 py-1">
                                                <button class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white font-bold text-[11px] uppercase">Không đạt</button>
                                            </form>
                                        @else
                                            <div class="text-[10px] text-amber-700">Chờ QA cập nhật COA trước khi QC kết luận.</div>
                                        @endif
                                    @endif
                                    @if($lot->status === 'rejected')
                                        @if($canQc && $lot->supplierReturns->isEmpty())
                                            <form method="POST" action="{{ route('material-lots.reinspect', $lot) }}" class="mb-1" onsubmit="return confirm('Chuyển lô này về hàng chờ QC kiểm tra lại?');">
                                                @csrf
                                                <input type="text" name="note" placeholder="Lý do / nội dung kiểm tra lại" required class="mb-1 w-full text-xs border-slate-300 py-1">
                                                <button class="w-full px-2 py-1.5 bg-amber-600 text-[11px] font-bold uppercase text-white hover:bg-amber-700">Yêu cầu kiểm tra lại</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('supplier-returns.index') }}" class="block w-full px-2 py-1.5 text-center text-[11px] font-bold uppercase text-rose-700 underline">Lập phiếu trả NCC</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-6 text-center text-slate-500">Chưa có {{ strtolower($typeLabel) }} nào chờ kiểm.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $lots->links() }}
        </div>
    </div>
</x-app-layout>
