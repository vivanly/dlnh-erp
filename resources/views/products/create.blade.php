<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Thêm mới Dược liệu / Hàng hóa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>- {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('products.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Tên hàng hóa -->
                        <div class="col-span-2">
                            <label class="block font-medium text-sm text-gray-700">Tên hàng hóa (Tên chính / tên khác theo dược điển) <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Mã hàng SKU -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Mã hàng (SKU) <span class="text-red-500">*</span></label>
                            <input type="text" name="sku" value="{{ old('sku') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Mã GTIN -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Mã GTIN (Mã vạch)</label>
                            <input type="text" name="gtin" value="{{ old('gtin') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Bộ phận dùng -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Bộ phận dùng</label>
                            <input type="text" name="part_used" value="{{ old('part_used') }}" placeholder="Ví dụ: Hoa, Lá, Rễ..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Nguồn gốc -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Nguồn gốc</label>
                            <input type="text" name="origin" value="{{ old('origin') }}" placeholder="Ví dụ: VN" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Đơn vị tính -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Đơn vị tính (ĐVT)</label>
                            <input type="text" name="unit" value="{{ old('unit') }}" placeholder="Ví dụ: Kg" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Phân loại -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Phân loại</label>
                            <input type="text" name="classification" value="{{ old('classification') }}" placeholder="Ví dụ: DL / Sơ chế..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Tên khoa học -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Tên khoa học</label>
                            <input type="text" name="scientific_name" value="{{ old('scientific_name') }}" placeholder="Ví dụ: Cynarae Scolymi Flos" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Tài liệu tham khảo tên khoa học -->
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Tài liệu tham khảo tên khoa học</label>
                            <input type="text" name="scientific_name_reference" value="{{ old('scientific_name_reference') }}" placeholder="Ví dụ: Theo ĐĐVN 6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <!-- Ghi chú -->
                        <div class="col-span-2">
                            <label class="block font-medium text-sm text-gray-700">Ghi chú</label>
                            <textarea name="note" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('note') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end space-x-3">
                        <a href="{{ route('products.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-sm font-medium">Hủy</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium">Lưu sản phẩm</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>