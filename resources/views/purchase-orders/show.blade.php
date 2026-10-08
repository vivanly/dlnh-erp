<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                    Chi Tiết Đơn Mua Hàng: <span class="text-blue-600 font-mono">{{ $purchaseOrder->po_number }}</span>
                </h2>
                @if($purchaseOrder->status == 'draft')
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-300 rounded-none text-xs font-semibold">Nháp (Draft)</span>
                @elseif($purchaseOrder->status == 'pending')
                    <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-none text-xs font-semibold">Chờ duyệt</span>
                @elseif($purchaseOrder->status == 'approved')
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-none text-xs font-semibold">Đã duyệt / Chờ giao</span>
                @elseif($purchaseOrder->status == 'delivered')
                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-none text-xs font-semibold">Đã giao (Chờ QC)</span>
                @elseif($purchaseOrder->status == 'completed')
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-none text-xs font-semibold">Hoàn thành</span>
                @elseif($purchaseOrder->status == 'rejected')
                    <span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-none text-xs font-semibold">Bị từ chối</span>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchase-orders.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-200 border border-slate-300 rounded-none font-bold text-xs text-slate-700 uppercase hover:bg-slate-300 transition">
                    Quay lại
                </a>
                <!-- Cho phép chỉnh sửa khi ở trạng thái Nháp hoặc Bị từ chối -->
                @if(in_array($purchaseOrder->status, ['draft', 'rejected'], true) || $canManageLockedPurchaseOrders)
                    <a href="{{ route('purchase-orders.edit', $purchaseOrder->id) }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-amber-500 border border-amber-600 rounded-none font-bold text-xs text-white uppercase hover:bg-amber-600 transition">
                        Chỉnh sửa đơn
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs rounded-none flex items-center justify-between">
                    <span class="font-medium">{{ session('success') }}</span>
                    <button type="button" class="text-emerald-700 hover:text-emerald-900 font-bold" onclick="this.parentElement.remove();">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs rounded-none flex items-center justify-between">
                    <span class="font-medium">{{ session('error') }}</span>
                    <button type="button" class="text-rose-700 hover:text-rose-900 font-bold" onclick="this.parentElement.remove();">&times;</button>
                </div>
            @endif

            <!-- THANH ĐIỀU HƯỚNG QUY TRÌNH (WORKFLOW ACTIONS) -->
            <div class="bg-white border border-slate-300 p-4 rounded-none flex flex-wrap items-center justify-between gap-3">
                <div>
                    <span class="block text-[11px] font-bold text-slate-500 uppercase">Trạng thái xử lý nghiệp vụ:</span>
                    <span class="text-xs font-semibold text-slate-800">
                        @if($purchaseOrder->status == 'draft') Đơn hàng đang ở dạng nháp, hãy kiểm tra lại thông tin trước khi gửi duyệt.
                        @elseif($purchaseOrder->status == 'pending') Đang chờ Giám đốc phòng kinh doanh xem xét phê duyệt.
                        @elseif($purchaseOrder->status == 'approved') Đã duyệt, Kho đang theo dõi tiến độ giao hàng.
                        @elseif($purchaseOrder->status == 'delivered') Kho đã xác nhận hàng đến, đang chờ lập phiếu nhập kho.
                        @elseif($purchaseOrder->status == 'completed') Đơn hàng đã hoàn tất nghiệm thu và nhập kho thành công.
                        @elseif($purchaseOrder->status == 'rejected') Đơn hàng đã bị từ chối. Vui lòng chỉnh sửa lại thông tin bên dưới.
                        @endif
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <!-- 1. Nút Gửi duyệt (Dành cho nhân viên khi ở trạng thái draft hoặc rejected) -->
                    @if(in_array($purchaseOrder->status, ['draft', 'rejected']))
                        <form action="{{ route('purchase-orders.submit', $purchaseOrder->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider rounded-none">
                                Gửi Duyệt Đơn Hàng
                            </button>
                        </form>
                    @endif

                    <!-- 2. Nút Duyệt / Từ chối (Dành cho Giám đốc khi ở trạng thái pending) -->
                    @if($purchaseOrder->status == 'pending')
                        <form action="{{ route('purchase-orders.approve', $purchaseOrder->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider rounded-none">
                                Phê Duyệt Đơn
                            </button>
                        </form>

                        <button type="button" onclick="document.getElementById('reject-form').classList.toggle('hidden')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs uppercase tracking-wider rounded-none">
                            Từ Chối
                        </button>
                    @endif

                </div>
            </div>

            <!-- Form từ chối ẩn (hiện khi bấm nút Từ chối) -->
            <div id="reject-form" class="hidden bg-rose-50 border border-rose-300 p-3 rounded-none">
                <form action="{{ route('purchase-orders.reject', $purchaseOrder->id) }}" method="POST" class="space-y-2">
                    @csrf
                    <label class="block font-bold text-xs text-rose-900 uppercase">Lý do từ chối đơn hàng:</label>
                    <textarea name="rejection_reason" rows="2" class="w-full text-xs border-rose-300 rounded-none focus:border-rose-500 focus:ring-0" placeholder="Nhập lý do để nhân viên biết đường sửa lại..." required></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="submit" class="px-3 py-1 bg-rose-700 text-white text-xs font-bold uppercase rounded-none">Xác Nhận Từ Chối</button>
                    </div>
                </form>
            </div>

            @if($purchaseOrder->status == 'rejected' && $purchaseOrder->rejection_reason)
                <div class="bg-rose-50 border border-rose-300 p-3 text-xs">
                    <span class="font-bold text-rose-900 uppercase block">Lý do từ chối:</span>
                    <p class="text-rose-700 italic mt-1">"{{ $purchaseOrder->rejection_reason }}"</p>
                </div>
            @endif

            <!-- THÔNG TIN CHUNG -->
            <div class="bg-white border border-slate-300 p-4 rounded-none">
                <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">Thông tin chung</h3>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <div>
                        <span class="block text-slate-500 uppercase font-semibold">Mã đơn mua (PO):</span>
                        <span class="font-mono font-bold text-slate-800 text-sm">{{ $purchaseOrder->po_number }}</span>
                    </div>
                    <div>
                        <span class="block text-slate-500 uppercase font-semibold">Nhà cung cấp:</span>
                        <span class="font-bold text-slate-800">{{ $purchaseOrder->supplier->name ?? 'N/A' }}</span>
                        <span class="block text-slate-500 text-[11px]">{{ $purchaseOrder->supplier->phone ?? '' }}</span>
                    </div>
                    <div>
                        <span class="block text-slate-500 uppercase font-semibold">Ngày đặt hàng:</span>
                        <span class="font-medium text-slate-800">{{ date('d/m/Y', strtotime($purchaseOrder->order_date)) }}</span>
                    </div>
                    <div>
                        <span class="block text-slate-500 uppercase font-semibold">Dự kiến giao:</span>
                        <span class="font-medium text-slate-800">{{ $purchaseOrder->expected_delivery_date ? date('d/m/Y', strtotime($purchaseOrder->expected_delivery_date)) : 'Không có' }}</span>
                    </div>
                </div>
            </div>

            <!-- DANH SÁCH VỊ THUỐC -->
            <div class="bg-white border border-slate-300 rounded-none overflow-hidden">
                <div class="p-3 border-b border-slate-300 bg-slate-100">
                    <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider">Danh mục dược liệu đặt mua & nhận kho</h3>
                </div>
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700">
                            <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                            <th class="py-2.5 px-3 border-r border-slate-300">Dược liệu</th>
                            <th class="py-2.5 px-3 border-r border-slate-300 w-20 text-center">Đơn vị tính</th>
                            <th class="py-2.5 px-3 border-r border-slate-300 w-24 text-center">SL Đặt mua</th>
                            <th class="py-2.5 px-3 border-r border-slate-300 w-24 text-center text-emerald-700">SL Nhập kho</th>
                            <th class="py-2.5 px-3 border-r border-slate-300 w-24 text-center text-rose-700">SL Trả lại</th>
                            <th class="py-2.5 px-3 border-r border-slate-300 w-32 text-right">Đơn giá</th>
                            <th class="py-2.5 px-3 w-32 text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-xs text-slate-800">
                        @foreach($purchaseOrder->items as $index => $item)
                            @php
                                // Tính tổng số lượng thực nhận và trả lại dựa trên purchase_order_item_id khớp với bảng goods_receipt_items
                                $receivedQty = 0;
                                $returnedQty = 0;

                                if ($purchaseOrder->goodsReceipts) {
                                    foreach ($purchaseOrder->goodsReceipts as $receipt) {
                                        foreach ($receipt->items as $rItem) {
                                            if ($rItem->purchase_order_item_id == $item->id) {
                                                $receivedQty += $rItem->received_quantity ?? 0;
                                                $returnedQty += $rItem->returned_quantity ?? 0;
                                            }
                                        }
                                    }
                                }
                                if ($item->material_type) {
                                    $receivedQty = (float) $item->materialMovements()->where('movement_type', 'RECEIVE_PURCHASE')->sum('quantity');
                                    $returnedQty = (float) $item->materialMovements()->where('movement_type', 'REJECT_PURCHASE')->sum('quantity');
                                }
                            @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="py-2 px-3 border-r border-slate-200 text-center font-mono">{{ $index + 1 }}</td>
                                <td class="py-2 px-3 border-r border-slate-200 font-medium">
                                    {{ $item->catalog_item->name ?? 'Sản phẩm không tồn tại' }} <span class="text-[10px] text-slate-500">[{{ $item->item_type_label }}]</span>
                                    <span class="block text-[10px] text-slate-500 font-mono">SKU: {{ $item->catalog_item->sku ?? '' }}</span>
                                </td>
                                <td class="py-2 px-3 border-r border-slate-200 text-center">{{ $item->unit }}</td>
                                <td class="py-2 px-3 border-r border-slate-200 text-center font-mono">{{ number_format($item->quantity, 2) }}</td>
                                
                                <!-- Số lượng thực nhận (Đạt) -->
                                <td class="py-2 px-3 border-r border-slate-200 text-center font-mono font-bold text-emerald-600">
                                    {{ number_format($receivedQty, 2) }}
                                </td>

                                <!-- Số lượng trả lại (Lỗi) -->
                                <td class="py-2 px-3 border-r border-slate-200 text-center font-mono font-bold text-rose-600">
                                    {{ number_format($returnedQty, 2) }}
                                </td>
                                <td class="py-2 px-3 border-r border-slate-200 text-right font-mono">{{ number_format($item->unit_price, 0, ',', '.') }} đ</td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-rose-600">{{ number_format($item->total_price, 0, ',', '.') }} đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- TỔNG KẾT & GHI CHÚ -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="bg-white border border-slate-300 p-3 text-xs">
                    <span class="font-bold text-slate-700 uppercase block mb-1">Ghi chú đơn hàng:</span>
                    <p class="text-slate-600 italic">{{ $purchaseOrder->notes ?: 'Không có ghi chú nào.' }}</p>
                </div>
                <div class="bg-white border border-slate-300 p-3 text-xs space-y-2">
                    <div class="flex justify-between items-center text-slate-700">
                        <span>Cộng tiền hàng:</span>
                        <span class="font-mono font-bold">{{ number_format($purchaseOrder->subtotal, 0, ',', '.') }} đ</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-700">
                        <span>Tiền thuế / Khác:</span>
                        <span class="font-mono font-bold">{{ number_format($purchaseOrder->tax_amount, 0, ',', '.') }} đ</span>
                    </div>
                    <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm">
                        <span class="font-bold text-slate-800 uppercase">Tổng thanh toán:</span>
                        <span class="font-mono font-bold text-rose-600 text-base">{{ number_format($purchaseOrder->grand_total, 0, ',', '.') }} đ</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>