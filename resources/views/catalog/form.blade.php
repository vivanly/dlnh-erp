@php
    $field = fn (string $name) => old($name, $item->{$name} ?? '');
    $input = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $item ? 'Sửa ' . $labels['singular'] : $labels['add'] }}
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

                <form action="{{ $item ? route($prefix . '.update', $item->id) : route($prefix . '.store') }}" method="POST">
                    @csrf
                    @if($item) @method('PUT') @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Phân loại</label>
                            <input type="text" name="classification" value="{{ $field('classification') }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Mã hàng (SKU) <span class="text-red-500">*</span></label>
                            <input type="text" name="sku" value="{{ $field('sku') }}" required class="{{ $input }}">
                        </div>
                        <div class="col-span-2">
                            <label class="block font-medium text-sm text-gray-700">Tên hàng <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ $field('name') }}" required class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Đơn vị tính (ĐVT)</label>
                            <input type="text" name="unit" value="{{ $field('unit') }}" placeholder="Ví dụ: Kg" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Nguồn gốc</label>
                            <input type="text" name="origin" value="{{ $field('origin') }}" placeholder="Ví dụ: VN" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Bộ phận dùng</label>
                            <input type="text" name="part_used" value="{{ $field('part_used') }}" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block font-medium text-sm text-gray-700">Tên khoa học</label>
                            <input type="text" name="scientific_name" value="{{ $field('scientific_name') }}" class="{{ $input }}">
                        </div>
                        <div class="col-span-2">
                            <label class="block font-medium text-sm text-gray-700">Tài liệu tham khảo tên khoa học</label>
                            <input type="text" name="scientific_name_reference" value="{{ $field('scientific_name_reference') }}" class="{{ $input }}">
                        </div>
                        <div class="col-span-2">
                            <label class="block font-medium text-sm text-gray-700">Ghi chú</label>
                            <textarea name="note" rows="3" class="{{ $input }}">{{ $field('note') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end space-x-3">
                        <a href="{{ route($prefix . '.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 text-sm font-medium">Hủy</a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-medium">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
