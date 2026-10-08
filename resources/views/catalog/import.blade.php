<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Import {{ $labels['title'] }} từ Excel
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session('warning'))
                    <div class="mb-4 bg-amber-100 border border-amber-400 text-amber-800 px-4 py-3 rounded">
                        <p>{{ session('warning') }}</p>
                        @if(session('import_errors'))
                            <ul class="list-disc pl-5 mt-2">
                                @foreach(session('import_errors') as $failure)
                                    <li>Dòng {{ $failure['row'] }}: {{ implode('; ', $failure['messages']) }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <form action="{{ route($prefix . '.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Chọn file Excel (.xlsx, .xls, .csv):</label>
                        <input type="file" name="file" class="border rounded p-2 w-full" required>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 text-sm font-medium">
                            📥 Tiến hành Import
                        </button>
                        <a href="{{ route($prefix . '.index') }}" class="text-gray-600 hover:underline">Quay lại danh sách</a>
                    </div>
                </form>

                <div class="mt-8 border-t pt-4 text-sm text-gray-600">
                    <p class="font-semibold text-gray-700">Lưu ý cấu trúc tên cột ở dòng đầu tiên trong file Excel:</p>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <li><code>ma_hang_sku</code> (Bắt buộc)</li>
                        <li><code>ten_hang_hoa</code> (Bắt buộc)</li>
                        <li><code>phan_loai</code>, <code>dvt</code>, <code>nguon_goc</code>, <code>bo_phan_dung</code>, <code>ten_khoa_hoc</code>, <code>ghi_chu</code>, <code>tai_lieu_tham_khao</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>