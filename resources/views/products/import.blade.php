<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Danh mục Dược liệu từ Excel') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Chọn file Excel (.xlsx, .xls, .csv):</label>
                        <input type="file" name="file" class="border rounded p-2 w-full" required>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-medium">
                            📥 Tiến hành Import
                        </button>
                        <a href="{{ route('products.index') }}" class="text-gray-600 hover:underline">Quay lại danh sách</a>
                    </div>
                </form>

                <div class="mt-8 border-t pt-4 text-sm text-gray-600">
                    <p class="font-semibold text-gray-700">Lưu ý cấu trúc tên cột ở dòng đầu tiên trong file Excel:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li><code>ten_hang_hoa</code> (Bắt buộc)</li>
                        <li><code>ma_hang_sku</code></li>
                        <li><code>gtin</code></li>
                        <li><code>bo_phan_dung</code></li>
                        <li><code>nguon_goc</code></li>
                        <li><code>dvt</code></li>
                        <li><code>phan_loai</code></li>
                        <li><code>ten_khoa_hoc</code></li>
                        <li><code>tai_lieu_tham_khao</code></li>
                        <li><code>ghi_chu</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>