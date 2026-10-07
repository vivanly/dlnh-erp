<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                Quản lý & Cập nhật COA (Chứng chỉ chất lượng NCC)
            </h2>
            <div class="space-x-2">
                <a href="{{ route('qa.batches.index') }}" class="px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white font-bold text-xs uppercase tracking-wider transition inline-block">
                    ← Quay lại Lô Nhà Cung Cấp
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">

            @if(session('success'))
                <div class="p-3 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 bg-red-50 border border-red-300 text-red-800 text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif

            <!-- THANH TÌM KIẾM & LỌC TRẠNG THÁI COA -->
            <div class="bg-white border border-slate-300 p-3">
                <form action="{{ route('qa.coas.index') }}" method="GET" class="flex flex-wrap gap-2 items-center justify-between">
                    <div class="flex items-center gap-2 flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo số lô NCC hoặc tên dược liệu..." class="w-full sm:w-80 text-xs border-slate-300 py-1.5">
                        
                        <select name="status" class="text-xs border-slate-300 py-1.5">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="missing" {{ request('status') == 'missing' ? 'selected' : '' }}>Chưa có COA (Cần bổ sung)</option>
                            <option value="uploaded" {{ request('status') == 'uploaded' ? 'selected' : '' }}>Đã có COA</option>
                        </select>

                        <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs uppercase tracking-wider transition">
                            Lọc dữ liệu
                        </button>
                    </div>
                </form>
            </div>

            <!-- BẢNG DANH SÁCH QUẢN LÝ COA -->
            <div class="bg-white border border-slate-300 overflow-hidden">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2.5 border-r w-12 text-center">STT</th>
                            <th class="p-2.5 border-r w-32">Mã Đơn Mua</th>
                            <th class="p-2.5 border-r">Dược liệu / Sản phẩm</th>
                            <th class="p-2.5 border-r">Số lô NCC</th>
                            <th class="p-2.5 border-r">Nhà cung cấp / Phiếu nhập</th>
                            <th class="p-2.5 border-r text-center w-32">Trạng thái COA</th>
                            <th class="p-2.5 text-center w-40">Thao tác / Cập nhật</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($batches as $index => $batch)
                        <tr>
                            <td class="p-2.5 border-r text-center font-mono text-slate-500">
                                {{ $batches->firstItem() + $index }}
                            </td>
                            <td class="p-2.5 border-r font-mono text-slate-700 font-semibold">
                                {{ $batch->goodsReceiptItem->goodsReceipt->purchaseOrder->po_number ?? 'N/A' }}
                            </td>
                            <td class="p-2.5 border-r font-medium text-slate-800">
                                {{ $batch->product->name ?? 'N/A' }}
                                <div class="text-[10px] text-slate-500 font-normal">
                                    HSD: {{ $batch->exp_date ? date('d/m/Y', strtotime($batch->exp_date)) : '---' }}
                                </div>
                            </td>
                            <td class="p-2.5 border-r font-mono font-bold text-blue-600">
                                {{ $batch->batch_number }}
                            </td>
                            <td class="p-2.5 border-r">
                                <div class="font-medium">{{ $batch->goodsReceiptItem->goodsReceipt->purchaseOrder->supplier->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">
                                    Phiếu: {{ $batch->goodsReceiptItem->goodsReceipt->receipt_code ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="p-2.5 border-r text-center">
                                @if($batch->coa_file)
                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase border border-emerald-300 inline-block">
                                        Đã có COA
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase border border-amber-300 animate-pulse inline-block">
                                        Chưa có COA
                                    </span>
                                @endif
                            </td>
                            <td class="p-2.5 text-center space-x-1">
                                <!-- Xem file COA nếu đã có (Ai cũng xem được) -->
                                @if($batch->coa_file)
                                    <a href="{{ route('private-documents.supplier-coa', $batch) }}" target="_blank" rel="noopener" class="px-2 py-1 bg-slate-700 hover:bg-slate-800 text-white font-bold text-[10px] uppercase transition inline-block">
                                        Xem file
                                    </a>
                                @endif

                                <!-- KIỂM TRA PHÂN QUYỀN: CHỈ QA HOẶC IT MỚI ĐƯỢC TẢI LÊN / THAY THẾ COA -->
                                @if($canManageCoas)
                                    @if(method_exists(auth()->user(), 'isQADepartment') && auth()->user()->isQADepartment() ||
                                    method_exists(auth()->user(), 'isITDepartment') && auth()->user()->isITDepartment())
                                        <a href="{{ route('goods-receipts.show', $batch->goodsReceiptItem->goodsReceipt_id ?? $batch->goodsReceiptItem->goods_receipt_id) }}" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[10px] uppercase transition inline-block">
                                            {{ $batch->coa_file ? 'Thay thế' : 'Tải lên COA' }}
                                        </a>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[10px]">Chỉ xem</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-4 text-center text-slate-400 italic">
                                Không tìm thấy chứng chỉ COA nào phù hợp.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <div class="p-3 bg-slate-50 border-t border-slate-300 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select />
                    @if($batches->hasPages())
                        {{ $batches->links() }}
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>