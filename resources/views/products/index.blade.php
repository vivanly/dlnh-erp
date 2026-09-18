<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Quản lý Danh mục Dược liệu / Hàng hóa') }}
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('products.import.form') }}" class="inline-flex items-center gap-1.5 bg-emerald-600 text-white px-3.5 py-2 rounded-lg hover:bg-emerald-700 text-sm font-medium shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Import Excel
                </a>
                <a href="{{ route('products.create') }}" class="inline-flex items-center gap-1.5 bg-blue-600 text-white px-3.5 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Thêm sản phẩm mới
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            <!-- Thông báo thành công -->
            @if (session('success'))
                <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl shadow-sm" role="alert">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Khung chứa Bảng & Thanh tìm kiếm chuẩn ERP -->
            <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
                
                <!-- Thanh công cụ tìm kiếm và thông tin phân trang -->
                <div class="p-4 border-b border-gray-200 bg-gray-50/75 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Form tìm kiếm (Gắn vào route hiện tại của bạn, ví dụ: route('products.index')) -->
                    <form action="{{ route('products.index') }}" method="GET" class="flex items-center gap-2 w-full sm:w-80">
                        <div class="relative w-full">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </span>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm tên, SKU, GTIN..." class="w-full pl-9 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors shrink-0">
                            Tìm kiếm
                        </button>
                        @if(request('search'))
                            <a href="{{ route('products.index') }}" class="text-gray-500 hover:text-gray-700 text-sm whitespace-nowrap underline">Xóa lọc</a>
                        @endif
                    </form>

                    <div class="text-xs text-gray-500">
                        Hiển thị từ <span class="font-semibold text-gray-700">{{ $products->firstItem() ?? 0 }}</span> đến <span class="font-semibold text-gray-700">{{ $products->lastItem() ?? 0 }}</span> trong tổng số <span class="font-semibold text-gray-700">{{ $products->total() }}</span> bản ghi
                    </div>
                </div>

                <!-- Bảng dữ liệu 12 cột đầy đủ -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm text-left border-collapse">
                        <thead class="bg-gray-100 text-gray-700 uppercase tracking-wider text-xs font-bold">
                            <tr>
                                <th class="px-3.5 py-3 w-12 text-center border-b border-gray-200">STT</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Tên hàng hóa</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Mã hàng (SKU)</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Mã GTIN</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Bộ phận dùng</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Nguồn gốc</th>
                                <th class="px-3.5 py-3 text-center border-b border-gray-200">Đơn vị tính</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Phân loại</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Tên khoa học</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Tài liệu tham khảo</th>
                                <th class="px-3.5 py-3 border-b border-gray-200">Ghi chú</th>
                                <th class="px-3.5 py-3 text-center w-28 border-b border-gray-200">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white text-xs">
                            @forelse ($products as $index => $product)
                                <tr class="hover:bg-blue-50/40 transition-colors">
                                    <td class="px-3.5 py-3 text-center text-gray-500 font-medium">
                                        {{ $products->firstItem() + $index }}
                                    </td>
                                    <td class="px-3.5 py-3 font-semibold text-gray-900 text-sm whitespace-nowrap">
                                        {{ $product->name }}
                                    </td>
                                    <td class="px-3.5 py-3 font-mono text-gray-700 whitespace-nowrap">
                                        {{ $product->sku ?? '---' }}
                                    </td>
                                    <td class="px-3.5 py-3 font-mono text-gray-600 whitespace-nowrap">
                                        {{ $product->gtin ?? '---' }}
                                    </td>
                                    <td class="px-3.5 py-3 text-gray-700 whitespace-nowrap">
                                        {{ $product->part_used ?? '---' }}
                                    </td>
                                    <td class="px-3.5 py-3 text-gray-700 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-800">
                                            {{ $product->origin ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                        <span class="inline-block px-2 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold">
                                            {{ $product->unit }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                            {{ $product->classification ?? 'Khác' }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3 italic text-gray-800 whitespace-nowrap">
                                        {{ $product->scientific_name ?? '---' }}
                                    </td>
                                    <td class="px-3.5 py-3 text-gray-600 max-w-[150px] truncate" title="{{ $product->scientific_name_reference }}">
                                        {{ $product->scientific_name_reference ?? '---' }}
                                    </td>
                                    <td class="px-3.5 py-3 text-gray-600 max-w-[150px] truncate" title="{{ $product->note }}">
                                        {{ $product->note ?? '---' }}
                                    </td>
                                    <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('products.edit', $product->id) }}" class="p-1.5 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded-lg transition-colors font-medium inline-flex items-center gap-1" title="Sửa">
                                                <span>Sửa</span>
                                            </a>
                                            <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 text-red-600 hover:text-red-900 hover:bg-red-50 rounded-lg transition-colors font-medium">
                                                    <span>Xóa</span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                            <p class="text-sm font-medium text-gray-500">Không tìm thấy dữ liệu phù hợp.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Phân trang (Giữ lại query search khi chuyển trang) -->
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    {{ $products->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>