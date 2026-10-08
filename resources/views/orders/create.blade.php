<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-900 tracking-tight">
                Lập Đơn Hàng Dược Liệu Mới (Kinh Doanh)
            </h2>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-2.5 py-1 bg-white border border-slate-300 text-slate-800 text-xs font-semibold rounded shadow-sm hover:bg-slate-50 transition">
                <svg class="w-3.5 h-3.5 mr-1 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Quay lại
            </a>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="w-full px-2 sm:px-3 lg:px-4">
            <form action="{{ route('orders.store') }}" method="POST" class="space-y-3">
                @csrf

                <!-- 1. THÔNG TIN CHUNG ĐƠN HÀNG -->
                <div class="bg-white border border-slate-200 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center justify-between mb-2.5 pb-1.5 border-b border-slate-100">
                        <h3 class="font-bold text-xs text-slate-900 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-blue-600 rounded-full"></span>
                            1. Thông tin chung đơn hàng
                        </h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Mã Đơn Hàng <span class="text-rose-500">*</span></label>
                            <input type="text" name="order_code" value="{{ old('order_code', $suggestedCode ?? '') }}" required class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 font-mono font-bold bg-slate-50 text-slate-900">
                            @error('order_code') <span class="text-rose-500 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Khách Hàng <span class="text-rose-500">*</span></label>
                            <select name="customer_id" id="customer_select" required class="w-full text-xs">
                                <option value="">-- Chọn hoặc tìm khách hàng --</option>
                            </select>
                            @error('customer_id') <span class="text-rose-500 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Mã Khách Hàng</label>
                            <input type="text" id="customer_code" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded-md py-1.5 px-2.5 font-mono">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Địa Chỉ Khách Hàng</label>
                            <input type="text" name="customer_address" id="customer_address" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-medium rounded-md py-1.5 px-2.5">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Loại Đơn Hàng <span class="text-rose-500">*</span></label>
                            <select name="order_type" required class="w-full text-xs font-medium border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 text-slate-900 bg-white">
                                <option value="">-- Chọn loại đơn hàng --</option>
                                <option value="DL" {{ old('order_type', 'DL') == 'DL' ? 'selected' : '' }}>Dược Liệu</option>
                                <option value="VT" {{ old('order_type') == 'VT' ? 'selected' : '' }}>Vị Thuốc</option>
                                <option value="NL" {{ old('order_type') == 'NL' ? 'selected' : '' }}>Nguyên Liệu Thô</option>
                            </select>
                                @error('order_type') <span class="text-rose-500 text-[11px] mt-0.5 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Ngày Nhận Đơn <span class="text-rose-500">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', date('Y-m-d')) }}" required class="w-full text-xs font-semibold border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 font-mono text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Dự Kiến Giao Hàng <span class="text-rose-500">*</span></label>
                            <input type="date" name="delivery_date" value="{{ old('delivery_date') }}" required class="w-full text-xs font-semibold border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 font-mono text-slate-900">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Tỉnh / Thành Phố <span class="text-rose-500">*</span></label>
                            <input type="text" name="province_city" value="{{ old('province_city') }}" required placeholder="Ví dụ: Hà Nội, TP.HCM..." class="w-full text-xs font-medium border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 text-slate-900">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Người Thông Tin / Liên Hệ</label>
                            <input type="text" name="contact_person" value="{{ old('contact_person') }}" placeholder="Tên và số điện thoại người nhận thông tin" class="w-full text-xs font-medium border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 text-slate-900">
                        </div>
                    </div>
                </div>

                <!-- 2. DANH MỤC VỊ THUỐC DƯỢC LIỆU YÊU CẦU -->
                <div class="bg-white border border-slate-200 rounded-lg p-3 shadow-sm">
                    <div class="flex items-center justify-between mb-2.5 pb-1.5 border-b border-slate-100">
                        <h3 class="font-bold text-xs text-slate-900 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 bg-blue-600 rounded-full"></span>
                            2. Danh mục vị thuốc dược liệu yêu cầu
                        </h3>
                        <button type="button" id="addRowBtn" class="inline-flex items-center px-2.5 py-1 bg-slate-900 hover:bg-black text-white text-xs font-semibold rounded shadow-sm transition">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Thêm vị thuốc
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="itemsTable">
                            <thead>
                                <tr class="bg-slate-50 border-y border-slate-200 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                    <th class="py-2 px-1.5 w-10 text-center">STT</th>
                                    <th class="py-2 px-1.5 min-w-[220px]">Vị thuốc / Dược liệu</th>
                                    <th class="py-2 px-1.5 w-24 text-center">Mã SKU</th>
                                    <th class="py-2 px-1.5 w-16 text-center">ĐVT</th>
                                    <th class="py-2 px-1.5 w-24 text-center">Loại hàng</th>
                                    <th class="py-2 px-1.5 w-24 text-center">Nguồn gốc</th>
                                    <th class="py-2 px-1.5 w-20 text-center">Số lượng</th>
                                    <th class="py-2 px-1.5 w-20 text-center">QCĐG</th>
                                    <th class="py-2 px-1.5 w-20 text-center">SL thành phẩm</th>
                                    <th class="py-2 px-1.5 w-28 text-center">Yêu cầu PPCB</th>
                                    <th class="py-2 px-1.5 w-24 text-center">Mã PPCB</th>
                                    <th class="py-2 px-1.5 w-24 text-center">QA: Lô / NCC</th>
                                    <th class="py-2 px-1.5 w-32 text-center">Ghi chú</th>
                                    <th class="py-2 px-1.5 text-center w-10">Xóa</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs font-medium">
                                <tr>
                                    <td class="py-2 px-1.5 text-center font-bold text-slate-700 stt">1</td>
                                    <td class="py-2 px-1.5">
                                        <select name="items[0][item_id]" required class="w-full text-xs product-select">
                                            <option value="">-- Chọn vị thuốc --</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center font-mono product-sku"></td>
                                    <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center product-unit"></td>
                                    <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center product-classification" name="items[0][classification]"></td>
                                    <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center product-origin"></td>
                                    <td class="py-2 px-1.5"><input type="number" step="any" name="items[0][quantity]" placeholder="0" required class="w-full text-xs font-bold border-slate-300 rounded px-1 py-1 text-center font-mono qty-input focus:border-blue-500 focus:ring-blue-500"></td>
                                    <td class="py-2 px-1.5"><input type="text" name="items[0][packaging_spec]" placeholder="VD: 0.5 hoặc 1.5" class="w-full text-xs font-medium border-slate-300 rounded px-1 py-1 text-center font-mono qcdg-input focus:border-blue-500 focus:ring-blue-500"></td>
                                    <td class="py-2 px-1.5 text-center"><input type="number" step="any" name="items[0][finished_quantity]" readonly class="w-full text-xs font-bold border-slate-200 bg-slate-50 text-slate-700 rounded px-1 py-1 text-center font-mono finished-qty" value="0"></td>
                                    <td class="py-2 px-1.5">
                                        <select name="items[0][ppcb_id]" class="w-full text-xs ppcb-select">
                                            <option value="">-- Chọn PPCB --</option>
                                        </select>
                                    </td>
                                    <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center font-mono ppcb-code"></td>
                                    <td class="py-2 px-1.5 text-center text-[11px] text-slate-500 italic font-semibold">(QA chỉ định)</td>
                                    <td class="py-2 px-1.5"><input type="text" name="items[0][notes]" placeholder="Ghi chú..." class="w-full text-xs font-medium border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500"></td>
                                    <td class="py-2 px-1.5 text-center">
                                        <button type="button" class="text-slate-400 hover:text-rose-600 remove-row p-1 rounded hover:bg-rose-50 transition">
                                            <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- NÚT LƯU -->
                <div class="flex justify-end gap-2 pt-1">
                    <a href="{{ route('orders.index') }}" class="px-4 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 font-semibold text-xs rounded shadow-sm transition">
                        Hủy bỏ
                    </a>
                    <button type="submit" class="px-5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded shadow-sm transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Lưu Đơn Hàng Mới
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {$('#customer_select').autocompleteSelect({
                theme: 'bootstrap-5',
                placeholder: '-- Chọn hoặc tìm khách hàng --',
                allowClear: true,
                ajax: {
                    url: '{{ route("api.customers.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term }),
                    processResults: function (data) {
                        let resultsArray = Array.isArray(data) ? data : [data];
                        return {
                            results: resultsArray.map(item => ({
                                id: item.id,
                                text: item.text,
                                code: item.code,
                                address: item.address
                            }))
                        };
                    },
                    cache: true
                }
            }).on('autocomplete:select', function(e) {
                let data = e.params.data;
                $('#customer_code').val(data.code ? data.code : '');
                $('#customer_address').val(data.address ? data.address : '');
            }).on('autocomplete:clear', function() {
                $('#customer_code').val('');
                $('#customer_address').val('');
            });

            // Hàm tính toán số lượng thành phẩm tự động
            function calculateFinishedQuantity(row) {
                let qty = parseFloat($(row).find('.qty-input').val()) || 0;
                let qcdgRaw = $(row).find('.qcdg-input').val();
                
                // Trích xuất số thập phân từ chuỗi QCĐG (hỗ trợ cả trường hợp người dùng nhập số có chứa chữ như "1.5 kg" hoặc chỉ nhập số thuần túy "0.5")
                let qcdgMatch = qcdgRaw ? qcdgRaw.match(/[\d.,]+/) : null;
                let qcdg = 0;
                if (qcdgMatch) {
                    // Thay thế dấu phẩy thành dấu chấm để chuẩn hóa dạng số thập phân JS
                    qcdg = parseFloat(qcdgMatch[0].replace(',', '.')) || 0;
                }

                let finishedQty = 0;
                if (qcdg > 0) {
                    finishedQty = qty / qcdg;
                }

                // Hiển thị kết quả (giữ tối đa 2 chữ số thập phân nếu có số lẻ, hoặc làm tròn tùy ý)
                $(row).find('.finished-qty').val(finishedQty % 1 !== 0 ? finishedQty.toFixed(2) : finishedQty);
            }

            // Lắng nghe sự kiện thay đổi trên ô số lượng hoặc QCĐG
            $(document).on('input', '.qty-input, .qcdg-input', function() {
                let row = $(this).closest('tr');
                calculateFinishedQuantity(row);
            });

            function initRowAutocomplete(tr) {
                $(tr).find('.product-select').autocompleteSelect({
                    theme: 'bootstrap-5',
                    placeholder: '-- Chọn vị thuốc --',
                    allowClear: true,
                    ajax: {
                        url: () => ($('select[name=order_type]').val() === 'NL' ? '{{ route("api.raw-materials.search") }}' : '{{ route("api.products.search") }}'),
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: data => ({
                            results: data.map(item => ({
                                id: item.id,
                                text: item.sku + ' | ' + (item.name || ' ') + '' + ' | ' + (item.classification || ' ') + ' ',
                                sku: item.sku || item.code || '',
                                unit: item.unit || '',
                                classification: item.classification || item.type || '',
                                origin: item.origin || ''
                            }))
                        }),
                        cache: true
                    }
                }).on('autocomplete:select', function(e) {
                    let data = e.params.data;
                    let row = $(this).closest('tr');
                    row.find('.product-sku').val(data.sku);
                    row.find('.product-unit').val(data.unit);
                    row.find('.product-classification').val(data.classification);
                    row.find('.product-origin').val(data.origin);
                }).on('autocomplete:clear', function() {
                    let row = $(this).closest('tr');
                    row.find('.product-sku, .product-unit, .product-classification, .product-origin').val('');
                });

                $(tr).find('.ppcb-select').autocompleteSelect({
                    theme: 'bootstrap-5',
                    placeholder: '-- Chọn PPCB --',
                    allowClear: true,
                    ajax: {
                        url: '{{ route("api.ppcb.search") }}',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: data => ({
                            results: data.map(item => ({
                                id: item.id,
                                text: (item.ten_ppcb || item.name) + (item.ma || item.code ? ' (' + (item.ma || item.code) + ')' : ''),
                                code: item.ma || item.code || ''
                            }))
                        }),
                        cache: true
                    }
                }).on('autocomplete:select', function(e) {
                    let data = e.params.data;
                    let row = $(this).closest('tr');
                    row.find('.ppcb-code').val(data.code);
                }).on('autocomplete:clear', function() {
                    let row = $(this).closest('tr');
                    row.find('.ppcb-code').val('');
                });
            }

            initRowAutocomplete($('#itemsTable tbody tr')[0]);

            $('select[name=order_type]').on('change', function () {
                $('#itemsTable tbody .ajax-autocomplete-clear').trigger('click');
            });

            function updateSTT() {
                $('#itemsTable tbody tr').each(function(index, tr) {
                    $(tr).find('.stt').text(index + 1);$(tr).find('input, select, textarea').each(function() {
                        let name = $(this).attr('name');
                        if (name) {
                            $(this).attr('name', name.replace(/\[\d+\]/, `[${index}]`));
                        }
                    });
                });
            }

            $('#addRowBtn').click(function() {
                let tbody = $('#itemsTable tbody');
                let rowIndex = tbody.find('tr').length;
                let trHtml = `
                    <tr>
                        <td class="py-2 px-1.5 text-center font-bold text-slate-700 stt"></td>
                        <td class="py-2 px-1.5">
                            <select name="items[${rowIndex}][item_id]" required class="w-full text-xs product-select">
                                <option value="">-- Chọn vị thuốc --</option>
                            </select>
                        </td>
                        <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center font-mono product-sku"></td>
                        <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center product-unit"></td>
                        <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center product-classification" name="items[${rowIndex}][classification]"></td>
                        <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center product-origin"></td>
                        <td class="py-2 px-1.5"><input type="number" step="any" name="items[${rowIndex}][quantity]" placeholder="0" required class="w-full text-xs font-bold border-slate-300 rounded px-1 py-1 text-center font-mono qty-input focus:border-blue-500 focus:ring-blue-500"></td>
                        <td class="py-2 px-1.5"><input type="text" name="items[${rowIndex}][packaging_spec]" placeholder="VD: 0.5 hoặc 1.5" class="w-full text-xs font-medium border-slate-300 rounded px-1 py-1 text-center font-mono qcdg-input focus:border-blue-500 focus:ring-blue-500"></td>
                        <td class="py-2 px-1.5 text-center"><input type="number" step="any" name="items[${rowIndex}][finished_quantity]" readonly class="w-full text-xs font-bold border-slate-200 bg-slate-50 text-slate-700 rounded px-1 py-1 text-center font-mono finished-qty" value="0"></td>
                        <td class="py-2 px-1.5">
                            <select name="items[${rowIndex}][ppcb_id]" class="w-full text-xs ppcb-select">
                                <option value="">-- Chọn PPCB --</option>
                            </select>
                        </td>
                        <td class="py-2 px-1.5 text-center"><input type="text" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded px-1 py-1 text-center font-mono ppcb-code"></td>
                        <td class="py-2 px-1.5 text-center text-[11px] text-slate-500 italic font-semibold">(QA chỉ định)</td>
                        <td class="py-2 px-1.5"><input type="text" name="items[${rowIndex}][notes]" placeholder="Ghi chú..." class="w-full text-xs font-medium border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500"></td>
                        <td class="py-2 px-1.5 text-center">
                            <button type="button" class="text-slate-400 hover:text-rose-600 remove-row p-1 rounded hover:bg-rose-50 transition">
                                <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </td>
                    </tr>
                `;
                let newRow = $(trHtml);
                tbody.append(newRow);
                initRowAutocomplete(newRow[0]);
                updateSTT();
            });

            $(document).on('click', '.remove-row', function() {
                let row = $(this).closest('tr');
                if($('#itemsTable tbody tr').length > 1) {
                    row.remove();
                    updateSTT();
                } else {
                    alert('Đơn hàng phải có ít nhất một vị thuốc dược liệu!');
                }
            });
        });
    </script>
    @endpush
</x-app-layout>