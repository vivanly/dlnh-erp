<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                    Lập Đơn Mua Hàng Mới
                </h2>
                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-none text-xs font-semibold">
                    Purchase Order
                </span>
            </div>
            <a href="{{ route('purchase-orders.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-200 border border-slate-300 rounded-none font-bold text-xs text-slate-700 uppercase tracking-wider hover:bg-slate-300 transition shadow-none">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Quay lại danh sách
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">

            @if($errors->any())
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs rounded-none">
                    <span class="font-bold block mb-1">Vui lòng kiểm tra lại các lỗi sau:</span>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('purchase-orders.store') }}" method="POST" id="purchase-order-form">
                @csrf
                
                <!-- KHUNG CHỨA THÔNG TIN CHUNG -->
                <div class="bg-white border border-slate-300 rounded-none shadow-none p-4 mb-3">
                    <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Thông tin chung đơn hàng
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                        <div>
                            <label class="block font-bold text-xs text-slate-700 uppercase mb-1">Loại hàng đặt mua <span class="text-rose-600">*</span></label>
                            <select name="item_type" id="po-item-type" class="w-full text-xs border-slate-300 rounded-none py-1.5">
                                <option value="raw_material" @selected(old('item_type', 'raw_material') === 'raw_material')>Nguyên liệu thô</option>
                                <option value="accessory" @selected(old('item_type', 'raw_material') === 'accessory')>Phụ liệu</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-xs text-slate-700 uppercase mb-1">Mã đơn mua <span class="text-rose-600">*</span></label>
                            <input type="text" name="po_number" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 font-mono uppercase" placeholder="VD: PO-2026-001" value="{{ old('po_number') }}" required>
                        </div>

                        <div class="sm:col-span-1">
                            <label class="block font-bold text-xs text-slate-700 uppercase mb-1">Nhà cung cấp <span class="text-rose-600">*</span></label>
                            <select id="supplier-select" name="supplier_id" class="w-full text-xs border-slate-300 rounded-none" required>
                                <option value="">-- Gõ tên hoặc SĐT nhà cung cấp --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-xs text-slate-700 uppercase mb-1">Ngày đặt hàng <span class="text-rose-600">*</span></label>
                            <input type="date" name="order_date" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div>
                            <label class="block font-bold text-xs text-slate-700 uppercase mb-1">Dự kiến giao hàng</label>
                            <input type="date" name="expected_delivery_date" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" value="{{ old('expected_delivery_date') }}">
                        </div>
                    </div>
                </div>

                <!-- KHUNG CHỨA DANH MỤC VỊ THUỐC -->
                <div class="bg-white border border-slate-300 rounded-none shadow-none mb-3">
                    <div class="p-3 border-b border-slate-300 bg-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            Danh mục nguyên liệu thô / phụ liệu đặt mua
                        </h3>
                        <button type="button" id="add-item-btn" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition shadow-none">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                            Thêm dòng hàng
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="items-table">
                            <thead>
                                <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                    <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300">Hàng hóa <span class="text-rose-600">*</span></th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 w-28 text-center">Số lượng <span class="text-rose-600">*</span></th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 w-20 text-center">ĐVT</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 w-36 text-right">Đơn giá (VNĐ) <span class="text-rose-600">*</span></th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 w-36 text-right">Thành tiền</th>
                                    <th class="py-2.5 px-3 text-center w-16">Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="items-container" class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                                <tr class="item-row hover:bg-blue-50/20 transition">
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono row-index font-bold text-slate-600">1</td>
                                    <td class="py-2 px-3 border-r border-slate-200">
                                        <input type="hidden" name="items[0][item_type]" value="{{ old('item_type', 'raw_material') }}" class="item-type-select">
                                        <select name="items[0][item_id]" class="w-full text-xs border-slate-300 rounded-none product-select" required>
                                            <option value="">-- Gõ tìm tên / mã SKU --</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                                        <input type="number" step="0.01" name="items[0][quantity]" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1 text-center quantity-input font-mono" value="1" min="0.01" required>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                                        <input type="text" name="items[0][unit]" class="w-full text-xs border-slate-200 bg-slate-100 rounded-none text-center unit-input font-mono" value="Kg" readonly>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-right">
                                        <input type="number" step="1000" name="items[0][unit_price]" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1 text-right price-input font-mono" value="0" min="0" required>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-right align-middle">
                                        <input type="text" class="w-full text-xs border-slate-200 bg-slate-100 rounded-none text-right item-total-text font-mono font-bold text-rose-600" value="0" readonly>
                                        <input type="hidden" name="items[0][total_price]" class="item-total-input" value="0">
                                    </td>
                                    <td class="py-2 px-3 text-center align-middle">
                                        <button type="button" class="text-slate-400 hover:text-rose-600 font-bold cursor-pointer remove-row-btn" disabled>
                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- THANH TOÁN & GHI CHÚ -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                    <div class="sm:col-span-2 bg-white border border-slate-300 rounded-none p-3 flex flex-col justify-between">
                        <div>
                            <label class="block font-bold text-xs text-slate-700 uppercase mb-1">Ghi chú đơn hàng</label>
                            <textarea name="notes" rows="4" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none resize-none" placeholder="Nhập ghi chú hoặc yêu cầu đặc biệt khi mua hàng...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-300 rounded-none p-3 space-y-2 text-xs">
                        <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider pb-2 border-b border-slate-200">Tổng kết thanh toán</h3>
                        
                        <div class="flex justify-between items-center text-slate-700">
                            <span>Cộng tiền hàng:</span>
                            <span id="subtotal-text" class="font-mono font-bold">0 đ</span>
                        </div>

                        <div class="flex justify-between items-center text-slate-700">
                            <span>Tiền thuế (VAT / Khác):</span>
                            <div class="w-32">
                                <input type="number" name="tax_amount" id="tax-amount-input" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1 text-right font-mono" value="0" min="0" step="1000">
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200 flex justify-between items-center text-sm">
                            <span class="font-bold text-slate-800 uppercase">Tổng thanh toán:</span>
                            <span id="grand-total-text" class="font-mono font-bold text-rose-600 text-base">0 đ</span>
                        </div>
                    </div>
                </div>

                <!-- NÚT LƯU -->
                <div class="flex justify-end gap-2">
                    <a href="{{ route('purchase-orders.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition">
                        Hủy bỏ
                    </a>
                    <button type="submit" class="px-5 py-2 bg-blue-600 border border-blue-700 text-white text-xs uppercase font-bold rounded-none hover:bg-blue-700 transition shadow-none flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                        Lưu Đơn Mua Hàng
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
    $(document).ready(function() {
        let rowIndex = 0;

        // Khởi tạo autocomplete AJAX cho nhà cung cấp
        $('#supplier-select').autocompleteSelect({
                theme: 'bootstrap-5',
                placeholder: '-- Gõ tên hoặc SĐT nhà cung cấp --',
                allowClear: true,
                ajax: {
                    url: '{{ route("api.suppliers.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (response) {
                        let items = response.data || response;
                        return {
                            results: items.map(function (item) {
                                return { id: item.id, text: item.name + (item.phone ? '  | ' + item.phone + ' ' : '') };
                            })
                        };
                    },
                    cache: true
                }
            });

        // Hàm khởi tạo autocomplete AJAX cho sản phẩm
        function initProductSelect(element) {
            if ($(element).length > 1) { $(element).each(function () { initProductSelect(this); }); return; }
            let $el =$(element);
            $el.autocompleteSelect({
                    theme: 'bootstrap-5',
                    placeholder: '-- Chọn hàng hóa --',
                    ajax: {
                        url: '{{ route("api.purchase-items.search") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term, type: $(element).closest('tr').find('.item-type-select').val() || 'raw_material' };
                        },
                        processResults: function (response) {
                            let items = response.data || response;
                            return {
                                results: items.map(function (item) {
                                    return {
                                        id: item.id,
                                        text: item.sku + ' | ' + (item.name || ' ') + '' + ' | ' + (item.classification || ' ') + ' ',
                                        unit: item.unit || 'Kg',
                                        purchase_price: item.purchase_price || item.price || 0
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                }).on('autocomplete:select', function (e) {
                    let data = e.params.data;
                    let row = $(this).closest('tr');
                    row.find('.unit-input').val(data.unit);
                    row.find('.price-input').val(data.purchase_price);
                    calculateRowTotal(row);
                });
        }

        $(document).on('change', '#po-item-type', function () {
            $('.item-type-select').val($(this).val());
            $('#items-container .ajax-autocomplete-clear').trigger('click');
        });

        initProductSelect('.product-select');

        // Hàm cập nhật lại số thứ tự (STT) khi thêm hoặc xóa dòng
        function updateRowIndexes() {
            $('.item-row').each(function(index) {$(this).find('.row-index').text(index + 1);
            });
        }

        // Thêm dòng mới
        $('#add-item-btn').click(function() {
            rowIndex++;
            let newRow = `
                <tr class="item-row hover:bg-blue-50/20 transition">
                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono row-index font-bold text-slate-600"></td>
                    <td class="py-2 px-3 border-r border-slate-200">
                        <input type="hidden" name="items[${rowIndex}][item_type]" value="${$('#po-item-type').val()}" class="item-type-select">
                                        <select name="items[${rowIndex}][item_id]" class="w-full text-xs border-slate-300 rounded-none product-select" required>
                            <option value="">-- Gõ tìm tên / mã SKU --</option>
                        </select>
                    </td>
                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                        <input type="number" step="0.01" name="items[${rowIndex}][quantity]" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1 text-center quantity-input font-mono" value="1" min="0.01" required>
                    </td>
                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                        <input type="text" name="items[${rowIndex}][unit]" class="w-full text-xs border-slate-200 bg-slate-100 rounded-none text-center unit-input font-mono" value="Kg" readonly>
                    </td>
                    <td class="py-2 px-3 border-r border-slate-200 text-right">
                        <input type="number" step="1000" name="items[${rowIndex}][unit_price]" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1 text-right price-input font-mono" value="0" min="0" required>
                    </td>
                    <td class="py-2 px-3 border-r border-slate-200 text-right align-middle">
                        <input type="text" class="w-full text-xs border-slate-200 bg-slate-100 rounded-none text-right item-total-text font-mono font-bold text-rose-600" value="0" readonly>
                        <input type="hidden" name="items[${rowIndex}][total_price]" class="item-total-input" value="0">
                    </td>
                    <td class="py-2 px-3 text-center align-middle">
                        <button type="button" class="text-slate-400 hover:text-rose-600 font-bold cursor-pointer remove-row-btn">
                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </td>
                </tr>
            `;
            $('#items-container').append(newRow);
            initProductSelect($('#items-container tr:last-child .product-select'));
            updateRowIndexes();
            toggleRemoveButtons();
        });

        // Xóa dòng
        $(document).on('click', '.remove-row-btn', function() {$(this).closest('tr').remove();
            updateRowIndexes();
            toggleRemoveButtons();
            calculateGrandTotal();
        });

        function toggleRemoveButtons() {
            let rowCount = $('.item-row').length;
            if (rowCount <= 1) {
                $('.remove-row-btn').prop('disabled', true).addClass('text-slate-300').removeClass('text-slate-400 hover:text-rose-600');             } else {$('.remove-row-btn').prop('disabled', false).removeClass('text-slate-300').addClass('text-slate-400 hover:text-rose-600');
            }
        }

        // Sự kiện nhập liệu tính toán
        $(document).on('input', '.quantity-input, .price-input', function() {
            let row = $(this).closest('tr');
            calculateRowTotal(row);
        });

        $(document).on('input', '#tax-amount-input', function() {
            calculateGrandTotal();
        });

        function calculateRowTotal(row) {
            let qty = parseFloat(row.find('.quantity-input').val()) || 0;
            let price = parseFloat(row.find('.price-input').val()) || 0;
            let total = qty * price;
            
            row.find('.item-total-input').val(total);
            row.find('.item-total-text').val(formatNumber(total));
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let subtotal = 0;
            $('.item-row').each(function() {
                let qty = parseFloat($(this).find('.quantity-input').val()) || 0;
                let price = parseFloat($(this).find('.price-input').val()) || 0;
                subtotal += (qty * price);
            });

            let tax = parseFloat($('#tax-amount-input').val()) || 0;
            let grandTotal = subtotal + tax;

            $('#subtotal-text').text(formatNumber(subtotal) + ' đ');
            $('#grand-total-text').text(formatNumber(grandTotal) + ' đ');
        }

        function formatNumber(num) {
            return num.toLocaleString('vi-VN', { maximumFractionDigits: 2 });
        }
    });
    </script>
</x-app-layout>