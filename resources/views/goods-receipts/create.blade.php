<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                Tiếp Nhận Hàng Chờ QC (PO: <span class="text-blue-600 font-mono">{{ $purchaseOrder->po_number }}</span>)
            </h2>
            <a href="{{ route('purchase-orders.show', $purchaseOrder->id) }}" class="px-3 py-1.5 bg-slate-200 border border-slate-300 font-bold text-xs text-slate-700 uppercase hover:bg-slate-300 transition">
                Quay lại
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            
            <form action="{{ route('goods-receipts.store', $purchaseOrder->id) }}" method="POST" class="space-y-3">
                @csrf

                <!-- THÔNG TIN CHUNG PHIẾU NHẬN -->
                <div class="bg-white border border-slate-300 p-4 space-y-3">
                    <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider pb-2 border-b border-slate-200">Thông tin đợt nhận hàng</h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <!-- Thêm ô nhập Mã phiếu nhập kho -->
                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Mã phiếu nhập kho:</label>
                            <input type="text" name="receipt_code" value="{{ old('receipt_code') }}" placeholder="Để trống nếu tự động sinh" class="w-full text-xs border-slate-300 font-mono @error('receipt_code') border-rose-500 @enderror">
                            @error('receipt_code')
                                <span class="text-rose-600 text-[10px] mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 uppercase mb-1">Ngày nhận thực tế:</label>
                            <input type="date" name="receipt_date" value="{{ old('receipt_date', date('Y-m-d')) }}" class="w-full text-xs border-slate-300 font-mono" required>
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block font-bold text-slate-700 uppercase mb-1">Ghi chú đợt nhận:</label>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Ví dụ: Giao đủ đợt 1..." class="w-full text-xs border-slate-300">
                        </div>
                    </div>
                </div>

                <!-- CHI TIẾT SẢN PHẨM KIỂM ĐỊNH -->
                <div class="bg-white border border-slate-300 overflow-hidden">
                    <div class="p-3 bg-slate-100 border-b border-slate-300">
                        <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider">Kho kiểm đếm · lô đạt sẽ chuyển QC trước khi dùng</h3>
                    </div>
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                                <th class="p-2.5 border-r">Tên vị dược liệu</th>
                                <th class="p-2.5 border-r text-center w-28">SL Theo PO</th>
                                <th class="p-2.5 border-r text-center w-28">Còn xử lý</th>
                                <th class="p-2.5 border-r text-center w-32">SL đưa vào chờ QC</th>
                                <th class="p-2.5 border-r text-center w-32">SL trả tại điểm nhận</th>
                                <th class="p-2.5">Lý do trả tại điểm nhận</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td class="p-2.5 border-r font-medium">
                                    {{ $item->catalog_item->name ?? 'Dược liệu' }}
                                    <input type="hidden" name="items[{{ $item->id }}][product_id]" value="{{ $item->product_id }}">
                                </td>
                                <!-- Thêm class js-po-qty và thuộc tính data-po-qty -->
                                <td class="p-2.5 border-r text-center font-mono font-bold">
                                    {{ $item->quantity }} {{ $item->unit }}
                                </td>
                                <td class="p-2.5 border-r text-center font-mono">
                                    {{ number_format($item->remaining_quantity, 2) }} {{ $item->unit }}
                                </td>
                                <td class="p-2.5 border-r text-center">
                                    <input type="number" min="0" max="{{ $item->remaining_quantity }}" step="0.01" name="items[{{ $item->id }}][received_quantity]" value="{{ old('items.'.$item->id.'.received_quantity', $item->remaining_quantity) }}" class="w-full text-xs text-center border-slate-300 font-mono py-1" required>
                                </td>
                                <td class="p-2.5 border-r text-center">
                                    <input type="number" min="0" max="{{ $item->remaining_quantity }}" step="0.01" name="items[{{ $item->id }}][returned_quantity]" value="{{ old('items.'.$item->id.'.returned_quantity', 0) }}" class="w-full text-xs text-center border-slate-300 font-mono py-1 text-rose-600">
                                </td>
                                <td class="p-2.5">
                                    <input type="text" name="items[{{ $item->id }}][return_reason]" value="{{ old('items.'.$item->id.'.return_reason') }}" placeholder="Ghi rõ lỗi nếu có..." class="w-full text-xs border-slate-300 py-1">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- NÚT HOÀN TẤT -->
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider transition">
                        Lưu Phiếu Nhận Đợt Này
                    </button>
                </div>

            </form>

        </div>
    </div>

</x-app-layout>