<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Quản lý hồ sơ sản phẩm</h2>
            <a href="{{ route('product-regulatory-documents.create', ['product_id' => $selectedProductId]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition">Thêm hồ sơ</a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs">{{ session('success') }}</div>
            @endif

            <div class="bg-white border border-slate-300">
                <form method="GET" action="{{ route('product-regulatory-documents.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row gap-2">
                    <select name="product_id" aria-label="Lọc theo sản phẩm" class="w-full sm:w-72 text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
                        <option value="">Tất cả sản phẩm</option>
                        @foreach($productOptions as $product)
                            <option value="{{ $product->id }}" @selected((string) $selectedProductId === (string) $product->id)>{{ $product->name }} - {{ $product->sku }} ({{ $product->classification ?: 'Chưa phân loại' }})</option>
                        @endforeach
                    </select>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên sản phẩm, SKU hoặc số hồ sơ" aria-label="Tìm hồ sơ" class="w-full sm:w-96 text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
                    <select name="document_type" class="text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0">
                        <option value="">Tất cả loại hồ sơ</option>
                        <option value="cong_bo" @selected(request('document_type') === 'cong_bo')>Công bố</option>
                        <option value="dang_ky" @selected(request('document_type') === 'dang_ky')>Đăng ký</option>
                        <option value="gpnk" @selected(request('document_type') === 'gpnk')>Giấy phép nhập khẩu (GPNK)</option>
                    </select>
                    <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs uppercase font-bold">Tìm kiếm</button>
                    @if(request()->hasAny(['product_id', 'search', 'document_type']))
                        <a href="{{ route('product-regulatory-documents.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold">Xóa lọc</a>
                    @endif
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3">Sản phẩm</th>
                                <th class="py-2.5 px-3">SKU</th>
                                <th class="py-2.5 px-3">Loại hồ sơ</th>
                                <th class="py-2.5 px-3">Số hồ sơ</th>
                                <th class="py-2.5 px-3">Ngày</th>
                                <th class="py-2.5 px-3">Loại hình</th>
                                <th class="py-2.5 px-3 text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800">
                            @forelse($documents as $document)
                                <tr class="hover:bg-blue-50/40">
                                    <td class="py-2 px-3 font-bold">{{ $document->product->name }}</td>
                                    <td class="py-2 px-3 font-mono">{{ $document->product->sku }}</td>
                                    <td class="py-2 px-3">
                                        {{ ['cong_bo' => 'Công bố', 'dang_ky' => 'Đăng ký', 'gpnk' => 'Giấy phép nhập khẩu (GPNK)'][$document->document_type] ?? $document->document_type }}
                                    </td>
                                    <td class="py-2 px-3 font-mono font-semibold">{{ $document->document_number }}</td>
                                    <td class="py-2 px-3">{{ $document->document_date->format('d/m/Y') }}</td>
                                    <td class="py-2 px-3">{{ $document->document_form }}</td>
                                    <td class="py-2 px-3 text-center whitespace-nowrap space-x-2">
                                        <a href="{{ route('product-regulatory-documents.edit', $document) }}" class="text-blue-600 font-bold">Sửa</a>
                                        <form action="{{ route('product-regulatory-documents.destroy', $document) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hồ sơ này không?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 font-bold">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-slate-500 italic">Chưa có hồ sơ của sản phẩm.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-t border-slate-300 bg-slate-100 flex flex-wrap items-center justify-between gap-2">
                    <x-per-page-select />
                    @if($documents->hasPages())
                        {{ $documents->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
