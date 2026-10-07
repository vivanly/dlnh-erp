<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ $canManageQaLots ? 'QA Chốt Số Lô' : 'Chỉnh Sửa Đơn Hàng' }}: <span class="font-mono text-blue-600">{{ $order->order_code }}</span>
            </h2>
            <a href="{{ route('orders.show', $order->id) }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition">
                Quay lại chi tiết
            </a>
        </div>
    </x-slot>

    @php
        $user = auth()->user();

        // Kiểm tra quyền IT
        $isItOrAdmin = $user && method_exists($user, 'isITDepartment') && $user->isITDepartment();

        // Kiểm tra quyền QA
        $isQA = $user && (method_exists($user, 'isQADepartment') && $user->isQADepartment());

        // Kiểm tra quyền Sales
        $isSales = $user && method_exists($user, 'isSalesDepartment' ) && $user->isSalesDepartment();

        // Cấp quyền sửa thông tin chung và danh sách vị thuốc (IT, Admin, Sales được sửa; QA bị khóa)
        $canEditGeneralInfo = ($isItOrAdmin || $isSales) && !$canManageQaLots;
    @endphp

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if($canManageQaLots)
                <div class="flex flex-wrap items-center justify-between gap-2 border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                    <span>QA có thể phân bổ lượng dự kiến của lô nội bộ vừa tạo độc lập với lệnh sản xuất. Đơn chỉ chuyển đóng gói sau khi Kho nhập đủ lượng đã giữ.</span>
                </div>
            @endif
            <form action="{{ route('orders.update', $order->id) }}" method="POST" class="space-y-3">
                @csrf
                @method('PUT')

                <!-- Trạng thái ẩn -->
                <input type="hidden" name="status" value="{{ $order->status }}">

                <!-- 1. THÔNG TIN CHUNG ĐƠN HÀNG -->
                <div class="bg-white border border-slate-200 rounded-lg p-3 shadow-sm">
                    <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider mb-3 pb-2 border-b border-slate-200">
                        1. Cập nhật thông tin chung đơn hàng
                    </h3>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Mã Đơn Hàng <span class="text-rose-600">*</span></label>
                            <input type="text" name="order_code" value="{{ old('order_code', $order->order_code) }}" required {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 font-mono font-bold {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Khách Hàng <span class="text-rose-600">*</span></label>
                            <select name="customer_id" id="customer_select" required {{ !$canEditGeneralInfo ? 'disabled' : '' }} class="w-full text-xs">
                                @if($order->customer)
                                    <option value="{{ $order->customer->id }}" selected>{{ $order->customer->name ?? $order->customer->text }}</option>
                                @endif
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Mã Khách Hàng</label>
                            <input type="text" id="customer_code" value="{{ $order->customer->code ?? '' }}" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-semibold rounded-md py-1.5 px-2.5 font-mono">
                        </div>


                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Địa Chỉ Khách Hàng</label>
                            <input type="text" id="customer_address" value="{{ $order->customer->address ?? '' }}" readonly class="w-full text-xs border-slate-200 bg-slate-50 text-slate-700 font-medium rounded-md py-1.5 px-2.5">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Loại Đơn Hàng <span class="text-rose-600">*</span></label>
                            <select name="order_type" required {{ !$canEditGeneralInfo ? 'disabled' : '' }} class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 bg-white {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">
                                <option value="">-- Chọn loại đơn hàng --</option>
                                <option value="DL" {{ old('order_type', $order->order_type) == 'DL' ? 'selected' : '' }}>Dược Liệu</option>
                                <option value="VT" {{ old('order_type', $order->order_type) == 'VT' ? 'selected' : '' }}>Vị Thuốc</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Ngày Nhận Đơn <span class="text-rose-600">*</span></label>
                            <input type="date" name="order_date" value="{{ old('order_date', $order->order_date) }}" required {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 font-mono {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Dự Kiến Giao Hàng <span class="text-rose-600">*</span></label>
                            <input type="date" name="delivery_date" value="{{ old('delivery_date', $order->delivery_date) }}" required {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 font-mono {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Tỉnh / Thành Phố <span class="text-rose-600">*</span></label>
                            <input type="text" name="province_city" value="{{ old('province_city', $order->province_city) }}" required {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Người Thông Tin / Liên Hệ</label>
                            <input type="text" name="contact_person" value="{{ old('contact_person', $order->contact_person) }}" {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-slate-800 mb-1">Ghi Chú Đơn Hàng</label>
                            <textarea name="notes" rows="1" {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="w-full text-xs border-slate-300 rounded-md focus:border-blue-500 focus:ring-blue-500 py-1.5 px-2.5 {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">{{ old('notes', $order->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- 2. CHI TIẾT VỊ THUỐC / DƯỢC LIỆU -->
                <div class="bg-white border border-slate-300 rounded-none shadow-none">
                    <div class="p-3 bg-slate-100 border-b border-slate-300 flex items-center justify-between">
                        <h3 class="font-bold text-xs text-slate-700 uppercase tracking-wider">
                            2. Danh mục vị thuốc dược liệu yêu cầu
                        </h3>
                        @if($canEditGeneralInfo)
                            <button type="button" id="add-item-row" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] uppercase tracking-wider rounded-none transition">
                                + Thêm vị thuốc
                            </button>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse whitespace-nowrap text-xs" id="items-table">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-300 text-[11px] font-bold text-slate-700">
                                    <th class="py-2.5 px-2 border-r border-slate-300 w-10 text-center">STT</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 min-w-[200px]">Dược liệu / Vị Thuốc<span class="text-rose-600">*</span></th>
                                    <th class="py-2.5 px-3 border-r border-slate-300">Mã Hàng</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 text-center w-20">Đvt</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300">Phân Loại</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300">Nguồn Gốc</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 text-center w-24">Số Lượng <span class="text-rose-600">*</span></th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 text-center w-24">QCĐG <span class="text-rose-600">*</span></th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 text-center">Thành Phẩm</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300 min-w-[160px]">Yêu Cầu Bào Chế</th>
                                    <th class="py-2.5 px-3 border-r border-slate-300">Mã PPCB</th>
                                    
                                    @if($canManageQaLots)
                                        <th class="py-2.5 px-3 border-r border-slate-300 min-w-[340px]">QA chọn lô · hệ thống tự phân bổ lượng</th>
                                    @endif

                                    <th class="py-2.5 px-3 border-r border-slate-300">Ghi chú</th>
                                    @if($canEditGeneralInfo)
                                        <th class="py-2.5 px-2 text-center w-12">Xóa</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200" id="items-tbody">
                                @forelse($order->items ?? [] as $index => $item)
                                <tr class="item-row hover:bg-blue-50/20 transition">
                                    <td class="py-2 px-2 border-r border-slate-200 text-center font-mono row-index">
                                        {{ $index + 1 }}
                                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200">
                                        <select name="items[{{ $index }}][product_id]" class="product-select w-full text-xs" required {{ !$canEditGeneralInfo ? 'disabled' : '' }}>
                                            @if($item->product)
                                                <option value="{{ $item->product->id }}" selected>{{ $item->product->name }}</option>
                                            @endif
                                        </select>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono text-slate-600 item-sku">
                                        {{ optional($item->product)->sku ?? $item->sku ?? '-' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center item-unit">
                                        {{ $item->unit ?? optional($item->product)->unit ?? '-' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 item-classification">
                                        {{ $item->classification ?? optional($item->product)->type ?? optional($item->product)->classification ?? '-' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 item-origin">
                                        {{ $item->origin ?? optional($item->product)->origin ?? '-' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                                        <input type="number" step="any" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="item-quantity w-full text-xs text-center border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500 font-mono font-bold {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}" required>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                                        <input type="number" step="any" name="items[{{ $index }}][packaging_spec]" value="{{ $item->packaging_spec ?? 0 }}" {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="item-spec w-full text-xs text-center border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500 font-mono {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}" required>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                                        <input type="number" step="any" name="items[{{ $index }}][finished_quantity]" value="{{ $item->finished_quantity ?? 0 }}" class="item-finished w-full text-xs text-center border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500 font-mono font-bold text-blue-600 bg-slate-50" readonly>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200">
                                        <select name="items[{{ $index }}][ppcb_id]" {{ !$canEditGeneralInfo ? 'disabled' : '' }}                                         class="ppcb-select w-full text-xs">
                                                                                    @if($item->ppcb)
                                                                                        <option value="{{ $item->ppcb->id }}" selected>{{ $item->ppcb->ten_ppcb }}</option>
                                                                                    @endif
                                                                                </select>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono item-ppcb-ma">
                                        {{ optional($item->ppcb)->ma ?? $item->ppcb_ma ?? '-' }}
                                    </td>


                                    @if($canManageQaLots)
                                        <td class="py-2 px-3 border-r border-slate-200 min-w-[340px]">
                                            <div class="max-h-56 overflow-y-auto divide-y divide-slate-200">
                                                @forelse(($qaLotsByProduct[$item->id] ?? collect()) as $lotIndex => $lotOption)
                                                    <div class="grid grid-cols-[1.25rem_1fr] items-center gap-2 py-1.5">
                                                        <input id="lot-{{ $item->id }}-{{ $lotIndex }}" type="checkbox" name="items[{{ $index }}][allocations][{{ $lotIndex }}][lot]" value="{{ $lotOption['lot'] }}" @checked($lotOption['reserved_quantity'] > 0) @disabled($lotOption['available_quantity'] <= 0 && $lotOption['reserved_quantity'] <= 0) class="border-slate-300 text-blue-600 focus:ring-blue-500">
                                                        <label for="lot-{{ $item->id }}-{{ $lotIndex }}" class="min-w-0">
                                                            <span class="block truncate font-mono font-semibold">{{ $lotOption['code'] }}</span>
                                                            <span class="block text-[10px] text-slate-500">Tồn {{ number_format($lotOption['current_quantity'], 4) }} · Chờ nhập {{ number_format($lotOption['pending_quantity'], 4) }} · Khả dụng {{ number_format($lotOption['available_quantity'], 4) }} · Đơn này {{ number_format($lotOption['reserved_quantity'], 4) }}</span>
                                                        </label>
                                                    </div>
                                                @empty
                                                    <p class="py-2 text-xs text-rose-600">Không có lô khả dụng cho sản phẩm này.</p>
                                                @endforelse
                                            </div>
                                            <p class="mt-1 text-[10px] text-slate-500">Chỉ chọn lô; hệ thống tự phân bổ theo lượng khả dụng đến tối đa {{ number_format((float) $item->quantity, 4) }} {{ $item->product->unit ?? '' }}. Phần thiếu sẽ được Kế hoạch lập lệnh sản xuất.</p>
                                        </td>
                                    @endif

                                    <td class="py-2 px-3 border-r border-slate-200">
                                        <input type="text" name="items[{{ $index }}][notes]" value="{{ $item->notes ?? '' }}" {{ !$canEditGeneralInfo ? 'readonly' : '' }} class="w-full text-xs border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500 {{ !$canEditGeneralInfo ? 'bg-slate-50 text-slate-600' : '' }}">
                                    </td>
                                    @if($canEditGeneralInfo)
                                        <td class="py-2 px-2 text-center">
                                            <button type="button" class="remove-row text-rose-600 hover:text-rose-800 font-bold px-1.5 py-0.5 text-sm">&times;</button>
                                        </td>
                                    @endif
                                </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider rounded-none transition shadow-none">
                        {{ $canManageQaLots ? 'Lưu phân bổ lô và chuyển Kế hoạch' : 'Cập Nhật Đơn Hàng' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        const canEditGeneralInfo = @json($canEditGeneralInfo);

        $(document).ready(function() {
            // Khởi tạo autocomplete cho khách hàng
            if (canEditGeneralInfo) {
                $('#customer_select').autocompleteSelect({
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
                                    text: item.name || item.text,
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
            } else {
                $('#customer_select').autocompleteSelect({ theme: 'bootstrap-5', disabled: true });
            }

            // Khởi tạo autocomplete cho các dòng sản phẩm
            initProductSelect($('.product-select'));
            initPpcbSelect($('.ppcb-select'));
            // Tính toán thành phẩm ban đầu
            $('.item-row').each(function() {
                calculateFinished($(this));
            });

            // Lắng nghe sự kiện thay đổi số lượng hoặc quy cách đóng gói để tính thành phẩm
            $(document).on('input', '.item-quantity, .item-spec', function() {
                let row = $(this).closest('tr');
                calculateFinished(row);
            });

            function calculateFinished(row) {
                let qty = parseFloat(row.find('.item-quantity').val()) || 0;
                let spec = parseFloat(row.find('.item-spec').val()) || 0;
                let finished = (spec > 0) ? (qty / spec) : 0;
                row.find('.item-finished').val(isNaN(finished) ? 0 : finished.toFixed(2));
            }

            // Hàm khởi tạo autocomplete sản phẩm
            function initProductSelect(element) {
                element.autocompleteSelect({
                    theme: 'bootstrap-5',
                    placeholder: '-- Chọn vị thuốc --',
                    allowClear: true,
                    disabled: !canEditGeneralInfo,
                    ajax: {
                        url: '{{ route("api.products.search") ?? "#" }}',
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: function (data) {
                            let resultsArray = Array.isArray(data) ? data : [data];
                            return {
                                results: resultsArray.map(item => ({
                                    id: item.id,
                                    text: item.name,
                                    sku: item.sku,
                                    unit: item.unit,
                                    classification: item.classification || item.type,
                                    origin: item.origin
                                }))
                            };
                        },
                        cache: true
                    }
                }).on('autocomplete:select', function(e) {
                    let data = e.params.data;
                    let row = $(this).closest('tr');
                    row.find('.item-sku').text(data.sku || '-');
                    row.find('.item-unit').text(data.unit || '-');
                    row.find('.item-classification').text(data.classification || '-');
                    row.find('.item-origin').text(data.origin || '-');
                });
            }

            function initPpcbSelect(element) {
                element.autocompleteSelect({
                    theme: 'bootstrap-5',
                    placeholder: '-- Chọn YCBC / PPCB --',
                    allowClear: true,
                    disabled: !canEditGeneralInfo,
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
                    $(this).closest('tr').find('.item-ppcb-ma').text(e.params.data.code || '-');
                }).on('autocomplete:clear', function() {
                    $(this).closest('tr').find('.item-ppcb-ma').text('-');
                });
            }

            // Thêm dòng mới
            // Thêm dòng mới khi bấm nút "+ Thêm vị thuốc"
            $('#add-item-row').click(function() {
                let index = $('#items-tbody tr').length;
                let deleteBtnHtml = canEditGeneralInfo ? `
                    <td class="py-2 px-2 text-center">
                        <button type="button" class="remove-row text-rose-600 hover:text-rose-800 font-bold px-1.5 py-0.5 text-sm">&times;</button>
                    </td>
                ` : '';

                let newRow = `
                    <tr class="item-row hover:bg-blue-50/20 transition">
                        <td class="py-2 px-2 border-r border-slate-200 text-center font-mono row-index">
                            ${index + 1}
                        </td>
                        <td class="py-2 px-3 border-r border-slate-200">
                            <select name="items[${index}][product_id]" class="product-select w-full text-xs" required></select>
                        </td>
                        <td class="py-2 px-3 border-r border-slate-200 font-mono text-slate-600 item-sku">-</td>
                        <td class="py-2 px-3 border-r border-slate-200 text-center item-unit">-</td>
                        <td class="py-2 px-3 border-r border-slate-200 item-classification">-</td>
                        <td class="py-2 px-3 border-r border-slate-200 item-origin">-</td>
                        <td class="py-2 px-3 border-r border-slate-200 text-center">
                            <input type="number" step="any" name="items[${index}][quantity]" value="1" class="item-quantity w-full text-xs text-center border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500 font-mono font-bold" required>
                        </td>
                        <td class="py-2 px-3 border-r border-slate-200 text-center">
                            <input type="number" step="any" name="items[${index}][packaging_spec]" value="0" class="item-spec w-full text-xs text-center border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500 font-mono" required>
                        </td>
                        <td class="py-2 px-3 border-r border-slate-200 text-center">
                            <input type="number" step="any" name="items[${index}][finished_quantity]" value="0" class="item-finished w-full text-xs text-center border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500 font-mono font-bold text-blue-600 bg-slate-50" readonly>
                        </td>
                        <td class="py-2 px-3 border-r border-slate-200">
                            <select name="items[${index}][ppcb_id]" class="ppcb-select w-full text-xs"></select>
                        </td>
                        <td class="py-2 px-3 border-r border-slate-200 font-mono item-ppcb-ma">-</td>
                        <td class="py-2 px-3 border-r border-slate-200">
                            <input type="text" name="items[${index}][notes]" class="w-full text-xs border-slate-300 rounded px-1 py-1 focus:border-blue-500 focus:ring-blue-500">
                        </td>
                        ${deleteBtnHtml}
                    </tr>
                `;
                $('#items-tbody').append(newRow);
                let newRowElement = $('#items-tbody tr:last-child');
                initProductSelect(newRowElement.find('.product-select'));
                initPpcbSelect(newRowElement.find('.ppcb-select'));
                updateRowIndexes();
            });

            // Xóa dòng vị thuốc
            $(document).on('click', '.remove-row', function() {$(this).closest('tr').remove();
                updateRowIndexes();
            });

            // Cập nhật lại số thứ tự STT sau khi xóa/thêm
            function updateRowIndexes() {
                $('#items-tbody tr').each(function(idx, tr) {
                    $(tr).find('.row-index').text(idx + 1);$(tr).find('input, select').each(function() {
                        let name = $(this).attr('name');
                        if (name) {
                            let newName = name.replace(/items\[\d+\]/, `items[${idx}]`);
                            $(this).attr('name', newName);
                        }
                    });
                });
            }
        });
    </script>
    @endpush
</x-app-layout>