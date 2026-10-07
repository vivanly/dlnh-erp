<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                Chi Tiết Phiếu Nhập & Kiểm Định COA: <span class="text-blue-600 font-mono">{{ $goodsReceipt->receipt_code }}</span>
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('qa.coas.index') }}" class="px-3 py-1.5 bg-slate-200 border border-slate-300 font-bold text-xs text-slate-700 uppercase hover:bg-slate-300 transition">
                    Quay lại danh sách
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
                <div class="p-3 bg-rose-50 border border-rose-300 text-rose-800 text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif

            <!-- THÔNG TIN CHUNG PHIẾU NHẬN -->
            <div class="bg-white border border-slate-300 p-4 space-y-3">
                <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider pb-2 border-b border-slate-200">Thông tin chung</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="text-slate-500 block uppercase text-[10px]">Mã đơn mua hàng (PO):</span>
                        <a href="{{ route('purchase-orders.show', $goodsReceipt->purchaseOrder->id) }}" class="font-mono font-bold text-blue-600 hover:underline">
                            {{ $goodsReceipt->purchaseOrder->po_number }}
                        </a>
                    </div>
                    <div>
                        <span class="text-slate-500 block uppercase text-[10px]">Nhà cung cấp:</span>
                        <span class="font-bold text-slate-800">{{ $goodsReceipt->purchaseOrder->supplier->name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block uppercase text-[10px]">Thủ kho tiếp nhận:</span>
                        <span class="font-bold text-slate-800">{{ $goodsReceipt->receiver->name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block uppercase text-[10px]">Ngày nhận thực tế:</span>
                        <span class="font-mono font-bold text-slate-800">{{ date('d/m/Y', strtotime($goodsReceipt->receipt_date)) }}</span>
                    </div>
                </div>

                @if($goodsReceipt->notes)
                <div class="pt-2 border-t border-slate-100 text-xs">
                    <span class="text-slate-500 uppercase text-[10px] block">Ghi chú:</span>
                    <span class="text-slate-700 italic">{{ $goodsReceipt->notes }}</span>
                </div>
                @endif
            </div>

            <!-- DANH SÁCH CHI TIẾT SẢN PHẨM & LÔ GỐC / QA CẬP NHẬT COA -->
            <div class="bg-white border border-slate-300 overflow-hidden">
                <div class="p-3 bg-slate-100 border-b border-slate-300 flex justify-between items-center">
                    <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider">Danh sách sản phẩm và Quản lý Lô / COA (QA)</h3>
                    <span class="text-[11px] text-slate-500 italic">Bộ phận QA có thể cập nhật số lô chính thức và tải file COA PDF tại bảng này</span>
                </div>

                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2.5 border-r">Dược liệu / Sản phẩm</th>
                            <th class="p-2.5 border-r text-center w-24">SL Nhận</th>
                            <th class="p-2.5 border-r text-center w-24">SL Lỗi/Trả</th>
                            <th class="p-2.5 border-r">Thông tin Lô Gốc & Cập nhật COA (QA)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($goodsReceipt->items as $item)
                        <tr>
                            <!-- Tên sản phẩm -->
                            <td class="p-2.5 border-r font-medium">
                                <span class="font-bold text-slate-800">{{ $item->herb->name ?? 'Dược liệu' }}</span>
                                @if($item->return_reason)
                                    <div class="text-[10px] text-rose-600 mt-0.5">Lý do lỗi: {{ $item->return_reason }}</div>
                                @endif
                            </td>

                            <!-- Số lượng nhận -->
                            <td class="p-2.5 border-r text-center font-mono font-bold text-emerald-600">
                                {{ $item->received_quantity }}
                            </td>

                            <!-- Số lượng lỗi -->
                            <td class="p-2.5 border-r text-center font-mono text-rose-600">
                                {{ $item->returned_quantity }}
                            </td>

                            <!-- Khu vực Quản lý Lô & COA cho từng SupplierBatch thuộc dòng nhận hàng này -->
                            <td class="p-2.5 space-y-3">
                                @forelse($item->supplierBatches as $batch)
                                <div class="bg-slate-50 border border-slate-200 p-3 rounded-none space-y-2">
                                    
                                    @if(auth()->user()->isQADepartment() || auth()->user()->isITDepartment())
                                        <!-- HIỂN THỊ FORM CHỈNH SỬA DÀNH CHO QA / IT / ADMIN -->
                                        <form action="{{ route('supplier-batches.update-coa', $batch->id) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                                            @csrf
                                            @method('PATCH')

                                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 items-center">
                                                <div>
                                                    <label class="text-[10px] text-slate-500 uppercase block font-bold">Số lô nhà cung cấp:</label>
                                                    <input type="text" name="batch_number" value="{{ old('batch_number', $batch->batch_number) }}" class="w-full text-xs border-slate-300 font-mono py-1" required>
                                                </div>

                                                <div>
                                                    <label class="text-[10px] text-slate-500 uppercase block font-bold">Ngày sản xuất (MFG):</label>
                                                    <input type="date" name="mfg_date" value="{{ old('mfg_date', $batch->mfg_date) }}" class="w-full text-xs border-slate-300 py-1" required>
                                                </div>

                                                <div>
                                                    <label class="text-[10px] text-slate-500 uppercase block font-bold">Hạn sử dụng (EXP):</label>
                                                    <input type="date" name="exp_date" value="{{ old('exp_date', $batch->exp_date) }}" class="w-full text-xs border-slate-300 py-1" required>
                                                </div>

                                                <div>
                                                    <label class="text-[10px] text-slate-500 uppercase block font-bold">Tồn hiện tại:</label>
                                                    <span class="font-mono font-bold text-blue-600 text-xs block py-1">{{ $batch->current_quantity }}</span>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                                                <div class="flex items-center gap-2">
                                                    <label class="text-[10px] text-slate-500 uppercase font-bold">File PDF COA:</label>
                                                    <input type="file" name="coa_file" accept=".pdf" class="text-[11px] text-slate-600">
                                                    @if($batch->coa_file)
                                                        <a href="{{ route('private-documents.supplier-coa', $batch) }}" target="_blank" rel="noopener" class="text-[11px] text-blue-600 font-bold underline hover:text-blue-800">
                                                            [Xem COA hiện tại]
                                                        </a>
                                                    @else
                                                        <span class="text-[10px] text-rose-500 italic">Chưa có COA</span>
                                                    @endif
                                                </div>

                                                <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold text-[11px] uppercase tracking-wider transition">
                                                    Lưu Lô & COA
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <!-- HIỂN THỊ DẠNG READ-ONLY CHO CÁC BỘ PHẬN KHÁC (VD: KHO, KINH DOANH...) -->
                                        <div class="space-y-1 text-xs">
                                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                <div><span class="text-slate-500 text-[10px] block uppercase">Số lô:</span> <span class="font-mono font-bold text-slate-800">{{ $batch->batch_number ?? 'N/A' }}</span></div>
                                                <div><span class="text-slate-500 text-[10px] block uppercase">NSX:</span> <span class="font-mono text-slate-800">{{ $batch->mfg_date ? date('d/m/Y', strtotime($batch->mfg_date)) : 'N/A' }}</span></div>
                                                <div><span class="text-slate-500 text-[10px] block uppercase">HSD:</span> <span class="font-mono text-slate-800">{{ $batch->exp_date ? date('d/m/Y', strtotime($batch->exp_date)) : 'N/A' }}</span></div>
                                                <div><span class="text-slate-500 text-[10px] block uppercase">Tồn:</span> <span class="font-mono font-bold text-blue-600">{{ $batch->current_quantity }}</span></div>
                                            </div>
                                            <div class="pt-1 flex items-center justify-between border-t border-slate-200">
                                                <div>
                                                    <span class="text-slate-500 text-[10px] uppercase font-bold">Trạng thái COA:</span>
                                                    @if($batch->coa_file)
                                                        <a href="{{ route('private-documents.supplier-coa', $batch) }}" target="_blank" rel="noopener" class="text-blue-600 font-bold underline ml-1">Xem File COA PDF</a>
                                                    @else
                                                        <span class="text-amber-600 italic ml-1">Đang chờ QA cập nhật</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>
                                @empty
                                    <span class="text-slate-400 italic text-xs">Chưa có thông tin lô gốc nào được khởi tạo.</span>
                                @endforelse
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>