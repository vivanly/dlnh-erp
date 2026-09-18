<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Quản lý Danh mục Dược liệu / Hàng hóa') }}
            </h2>
			<a href="{{ route('products.import.form') }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-medium">
                📥 Import Excel
            </a>
            <a href="{{ route('products.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium">
                + Thêm sản phẩm mới
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-full mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-700 uppercase font-bold">
                            <tr>
                                <th class="px-4 py-3 text-left">STT</th>
                                <th class="px-4 py-3 text-left">Tên hàng hóa</th>
                                <th class="px-4 py-3 text-left">Mã hàng (SKU)</th>
                                <th class="px-4 py-3 text-left">Mã GTIN</th>
                                <th class="px-4 py-3 text-left">Bộ phận dùng</th>
                                <th class="px-4 py-3 text-left">Nguồn gốc</th>
                                <th class="px-4 py-3 text-left">Đơn vị tính</th>
                                <th class="px-4 py-3 text-left">Phân loại</th>
                                <th class="px-4 py-3 text-left">Tên khoa học</th>
                                <th class="px-4 py-3 text-left">Tài liệu tham khảo</th>
                                <th class="px-4 py-3 text-left">Ghi chú</th>
                                <th class="px-4 py-3 text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($products as $index => $product)
                                <tr>
                                    <td class="px-4 py-3">{{ $products->firstItem() + $index }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $product->name }}</td>
                                    <td class="px-4 py-3">{{ $product->sku }}</td>
                                    <td class="px-4 py-3">{{ $product->gtin }}</td>
                                    <td class="px-4 py-3">{{ $product->part_used }}</td>
                                    <td class="px-4 py-3">{{ $product->origin }}</td>
                                    <td class="px-4 py-3">{{ $product->unit }}</td>
                                    <td class="px-4 py-3">{{ $product->classification }}</td>
                                    <td class="px-4 py-3 italic">{{ $product->scientific_name }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $product->scientific_name_reference }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600">{{ $product->note }}</td>
                                    <td class="px-4 py-3 text-center space-x-2">
                                        <a href="{{ route('products.edit', $product->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Sửa</a>
                                        <form action="{{ route('products.destroy', $product->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này không?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-medium">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-4 text-center text-gray-500">Chưa có dữ liệu sản phẩm nào.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>