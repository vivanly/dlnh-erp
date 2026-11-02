<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Quản lý Danh mục Dược liệu / Hàng hóa') }}
            </h2>
            <div class="flex items-center gap-2">
                <!-- CHỈ QA HOẶC IT/ADMIN MỚI THẤY NÚT IMPORT, EXPORT VÀ THÊM -->
                @if(auth()->user()->isQADepartment() || auth()->user()->isITDepartment())
                    <a href="{{ route('products.import.form') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Excel
                    </a>
                    <a href="{{ route('products.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-700 border border-slate-800 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-slate-800 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Excel
                    </a>
                    <button type="button" onclick="document.getElementById('product-create-modal').showModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Thêm sản phẩm mới
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs rounded-none flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs rounded-none flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="p-3 bg-amber-50 border-l-4 border-amber-600 text-amber-900 text-xs rounded-none">
                    {{ session('warning') }}
                </div>
            @endif

            <!-- KHUNG CHỨA BẢNG -->
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                
                <!-- FORM TÌM KIẾM & LỌC ĐÃ TỐI ƯU -->
                <form method="GET" action="{{ route('products.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="w-full sm:w-96">
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên sản phẩm..." aria-label="Tìm theo tên sản phẩm" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                    </div>
                    <select name="classification" aria-label="Lọc theo phân loại" class="w-full sm:w-56 text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0" onchange="this.form.submit()">
                        <option value="">Tất cả phân loại</option>
                        @foreach($classifications as $classification)
                            <option value="{{ $classification }}" @selected(request('classification') === $classification)>{{ $classification }}</option>
                        @endforeach
                    </select>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs uppercase font-bold rounded-none hover:bg-slate-700 transition">
                            Tìm kiếm
                        </button>

                        @if(request()->hasAny(['search', 'classification']))
                            <a href="{{ route('products.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition" title="Xóa từ khóa tìm kiếm">
                                Xóa lọc
                            </a>
                        @endif
                    </div>
                </form>

                <!-- BẢNG DỮ LIỆU SẢN PHẨM -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Phân loại</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Mã hàng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Tên hàng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 text-center w-20">ĐVT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-24">Nguồn gốc</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Bộ phận dùng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">PPCB</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Tên khoa học</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Ghi chú</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Mã GTIN</th>
                                
                                <!-- CHỈ QA HOẶC IT/ADMIN MỚI THẤY CỘT HÀNH ĐỘNG -->
                                @if(auth()->user()->isQADepartment() || auth()->user()->isITDepartment())
                                    <th class="py-2.5 px-3 text-center w-24">Hành động</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse ($products as $index => $product)
                                <tr id="product-{{ $product->id }}" class="hover:bg-blue-50/40 transition">
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                        {{ $products->firstItem() + $index }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">{{ $product->classification ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono text-slate-700">{{ $product->sku ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900">{{ $product->name }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-semibold text-blue-700">{{ $product->unit ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">
                                        {{ $product->origin ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">{{ $product->part_used ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">{{ $product->ppcb ? $product->ppcb->ten_ppcb . ' (' . $product->ppcb->ma . ')' : '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 italic text-slate-800">
                                        {{ $product->scientific_name ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600 truncate max-w-[150px]" title="{{ $product->note }}">
                                        {{ $product->note ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono text-slate-600">
                                        {{ $product->gtin ?? '---' }}
                                    </td>

                                    <!-- CHỈ QA HOẶC IT/ADMIN MỚI THẤY NÚT SỬA/XÓA -->
                                    @if(auth()->user()->isQADepartment() || auth()->user()->isITDepartment())
                                        <td class="py-2 px-3 text-center space-x-2 whitespace-nowrap">
                                            <a href="{{ route('product-regulatory-documents.index', ['product_id' => $product->id]) }}" class="text-emerald-600 hover:text-emerald-900 font-bold">Hồ sơ</a>
                                            <button type="button" onclick="document.getElementById('product-edit-modal-{{ $product->id }}').showModal()" class="erp-action-text text-blue-600 hover:text-blue-900 font-bold">Sửa</button>
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="erp-action-text text-rose-600 hover:text-rose-900 font-bold cursor-pointer">Xóa</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    @php
                                        $colSpan = (auth()->user()->isQADepartment() || auth()->user()->isITDepartment()) ? 11 : 10;
                                    @endphp
                                    <td colspan="{{ $colSpan }}" class="py-6 text-center text-slate-500 text-xs italic bg-slate-50">
                                        @if(request('search'))
                                            Không tìm thấy kết quả nào phù hợp với từ khóa "<span class="font-bold text-slate-700">{{ request('search') }}</span>".
                                        @else
                                            Chưa có dữ liệu sản phẩm trong hệ thống.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canManageProducts)
                    <dialog id="product-create-modal" class="erp-form-modal w-[min(64rem,calc(100%-2rem))] max-h-[90vh] p-0">
                        <div class="flex items-center justify-between border-b border-blue-200 bg-blue-50 px-5 py-3"><div><p class="text-[11px] uppercase tracking-wider text-blue-700">Danh mục hàng hóa</p><h3 class="text-base font-bold">Thêm sản phẩm</h3></div><button type="button" aria-label="Đóng" onclick="this.closest('dialog').close()" class="border bg-white px-3 py-1 text-lg">×</button></div>
                        <form action="{{ route('products.store') }}" method="POST" class="space-y-4 p-5">@csrf<input type="hidden" name="_product_modal" value="create">@include('products.partials.fields', ['product' => null])
                            <div class="flex justify-end gap-2 border-t pt-3"><button type="button" onclick="this.closest('dialog').close()" class="border px-4 py-2 text-xs">Hủy</button><button class="border border-blue-700 bg-blue-600 px-4 py-2 text-xs font-bold text-white">Lưu sản phẩm</button></div>
                        </form>
                    </dialog>
                    @foreach($products as $product)
                        <dialog id="product-edit-modal-{{ $product->id }}" class="erp-form-modal w-[min(64rem,calc(100%-2rem))] max-h-[90vh] p-0">
                            <div class="flex items-center justify-between border-b border-blue-200 bg-blue-50 px-5 py-3"><div><p class="text-[11px] uppercase tracking-wider text-blue-700">Danh mục hàng hóa</p><h3 class="text-base font-bold">Sửa sản phẩm · {{ $product->name }}</h3></div><button type="button" aria-label="Đóng" onclick="this.closest('dialog').close()" class="border bg-white px-3 py-1 text-lg">×</button></div>
                            <form action="{{ route('products.update', $product) }}" method="POST" class="space-y-4 p-5">@csrf @method('PUT')<input type="hidden" name="_product_modal" value="edit"><input type="hidden" name="_product_id" value="{{ $product->id }}">@include('products.partials.fields', ['product' => $product])
                                <div class="flex justify-end gap-2 border-t pt-3"><button type="button" onclick="this.closest('dialog').close()" class="border px-4 py-2 text-xs">Hủy</button><button class="border border-blue-700 bg-blue-600 px-4 py-2 text-xs font-bold text-white">Lưu thay đổi</button></div>
                            </form>
                        </dialog>
                    @endforeach
                    @if(old('_product_modal'))<script>document.addEventListener('DOMContentLoaded',()=>{const id=@json(old('_product_modal'))==='create'?'product-create-modal':`product-edit-modal-${@json(old('_product_id'))}`;document.getElementById(id)?.showModal();});</script>@endif
                @endif
                <!-- Footer phân trang -->
                <div class="p-3 border-t border-slate-300 bg-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select :default="100" />
                    @if($products->hasPages())
                        {{ $products->links() }}
                    @endif
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
