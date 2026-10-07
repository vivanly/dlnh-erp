<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Quản lý mã truy xuất nguồn gốc</h2>
                <p class="mt-1 text-xs text-slate-500">Cấu hình đường dẫn QR và chuỗi truy xuất cho từng lô hàng.</p>
            </div>
            <a href="{{ route('traceability.index') }}" class="border border-slate-300 px-3 py-1.5 text-xs font-bold uppercase text-slate-700 hover:bg-slate-50">Tra cứu nguồn gốc</a>
        </div>
    </x-slot>

    <div class="py-3">
        <div class="max-w-none space-y-3 px-2">
            @if(session('status'))
                <div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-800">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-800">
                    <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="border border-slate-300 bg-white p-3">
                <h3 class="text-xs font-bold uppercase text-slate-700">Đường dẫn truy xuất cố định</h3>
                <p class="mt-1 text-[11px] text-slate-500">Mã QR của từng lô được tạo bằng cách nối đường dẫn này với chuỗi truy xuất của lô.</p>
                @if($canManageTraceability)
                    <form method="POST" action="{{ route('traceability.management.base-url.update') }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                        @csrf
                        @method('PUT')
                        <input type="url" name="base_url" value="{{ old('base_url', $setting->base_url) }}" maxlength="500" placeholder="http://localhost/code=" class="min-w-0 flex-1 border-slate-300 text-xs">
                        <button type="submit" class="bg-slate-800 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-slate-700">Lưu đường dẫn</button>
                        <button type="submit" form="delete-traceability-base" class="border border-rose-300 px-4 py-2 text-xs font-bold uppercase text-rose-700 hover:bg-rose-50">Xóa</button>
                    </form>
                    <form id="delete-traceability-base" method="POST" action="{{ route('traceability.management.base-url.delete') }}" class="hidden">@csrf @method('DELETE')</form>
                @else
                    <p class="mt-3 break-all text-xs text-slate-700">{{ $setting->base_url ?: 'Chưa cấu hình đường dẫn truy xuất.' }}</p>
                @endif
                @if($setting->base_url)
                    <p class="mt-2 break-all text-[11px] text-slate-600">Ví dụ khi nối mã lô: <span class="font-mono font-semibold">{{ $setting->base_url }}NCC-123</span></p>
                @endif
            </section>

            <section class="overflow-x-auto border border-slate-300 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 p-3">
                    <div>
                        <h3 class="text-xs font-bold uppercase text-slate-700">Danh sách lô hàng và chuỗi truy xuất</h3>
                        <p class="mt-1 text-[11px] text-slate-500">Chuỗi mặc định được tạo tự động; có thể sửa hoặc xóa riêng cho từng lô.</p>
                    </div>
                    <form method="GET" action="{{ route('traceability.management.index') }}" class="flex flex-wrap items-center gap-2">
                        <label for="traceability-lot-filter" class="text-[11px] font-semibold text-slate-600">Lọc loại lô</label>
                        <select id="traceability-lot-filter" name="type" class="border-slate-300 py-1.5 text-xs" onchange="this.form.submit()">
                            <option value="all" @selected($filter === 'all')>Tất cả lô</option>
                            <option value="supplier" @selected($filter === 'supplier')>Lô nhà cung cấp</option>
                            <option value="finished" @selected($filter === 'finished')>Lô nội bộ</option>
                        </select>
                        <label for="traceability-per-page" class="text-[11px] font-semibold text-slate-600">Số dòng</label>
                        <select id="traceability-per-page" name="per_page" class="border-slate-300 py-1.5 text-xs" onchange="this.form.submit()">
                            @foreach([100, 500, 1000, 5000, 10000, 50000] as $option)
                                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit" class="border border-slate-300 px-2 py-1.5 text-xs">Lọc</button></noscript>
                        <span class="text-[11px] text-slate-500">Tổng lô: {{ number_format($lots->total()) }}</span>
                    </form>
                </div>
                <table class="w-full min-w-[950px] border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-300 bg-slate-100 text-[10px] uppercase text-slate-600">
                            <th class="p-2.5">Loại lô</th>
                            <th class="p-2.5">Số lô</th>
                            <th class="p-2.5">Sản phẩm</th>
                            <th class="p-2.5 text-right">Tồn hiện tại</th>
                            <th class="p-2.5">Trạng thái</th>
                            <th class="p-2.5">Chuỗi truy xuất</th>
                            <th class="p-2.5">Liên kết QR</th>
                            <th class="p-2.5">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($lots as $lot)
                            @php
                                $type = $lot->lot_type;
                                $code = $codes->get($type . '-' . $lot->lot_id);
                                $traceCode = $code?->trace_code ?: ($type === 'supplier' ? 'NCC-' : 'TP-') . $lot->lot_id;
                            @endphp
                            <tr>
                                <td class="p-2.5">{{ $type === 'supplier' ? 'Lô nhà cung cấp' : 'Lô thành phẩm' }}</td>
                                <td class="p-2.5 font-mono font-semibold">{{ $lot->batch_number }}</td>
                                <td class="p-2.5">{{ $lot->product_name }}</td>
                                <td class="p-2.5 text-right font-mono">{{ number_format((float) $lot->current_quantity, 4) }} {{ $lot->unit }}</td>
                                <td class="p-2.5">{{ $lot->status }}</td>
                                <td class="p-2.5">
                                    @if($canManageTraceability)
                                        <form method="POST" action="{{ route('traceability.management.lot-code.update', [$type, $lot->lot_id]) }}" class="flex gap-1.5">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="trace_code" value="{{ $code?->trace_code }}" maxlength="255" placeholder="Chuỗi truy xuất" class="min-w-48 flex-1 border-slate-300 text-xs">
                                            <button type="submit" class="border border-slate-300 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-700 hover:bg-slate-50">Lưu</button>
                                        </form>
                                    @else
                                        <span class="font-mono">{{ $traceCode }}</span>
                                    @endif
                                </td>
                                <td class="max-w-48 break-all p-2.5 font-mono text-[10px] text-slate-600">
                                    {{ $setting->base_url ? $setting->base_url . $traceCode : '---' }}
                                </td>
                                <td class="p-2.5">
                                    @if($canManageTraceability)
                                        <form method="POST" action="{{ route('traceability.management.lot-code.delete', [$type, $lot->lot_id]) }}" onsubmit="return confirm('Xóa chuỗi truy xuất của lô này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-[10px] font-bold uppercase text-rose-700 hover:underline">Xóa chuỗi</button>
                                        </form>
                                    @else
                                        <span class="text-slate-400">Chỉ xem</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-8 text-center text-slate-500">Chưa có lô hàng nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="border-t border-slate-200 p-3">{{ $lots->appends(['type' => $filter])->links() }}</div>
            </section>
        </div>
    </div>
</x-app-layout>
