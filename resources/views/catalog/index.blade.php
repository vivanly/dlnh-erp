<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">{{ $labels['title'] }}</h2>
            @if($canManage)
                <div class="flex items-center gap-2">
                <a href="{{ route($prefix . '.import.form') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition">Import Excel</a>
                <a href="{{ route($prefix . '.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-700 border border-slate-800 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-slate-800 transition">Export Excel</a>
                <a href="{{ route($prefix . '.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition">
                    + {{ $labels['add'] }}
                </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">{{ session('error') }}</div>
            @endif

            <div class="bg-white border border-slate-300">
                <form method="GET" action="{{ route($prefix . '.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="w-full sm:w-96">
                        <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên hoặc mã hàng..." aria-label="Tìm kiếm" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 py-1.5">
                    </div>
                    <select name="classification" aria-label="Lọc theo phân loại" class="w-full sm:w-56 text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0" onchange="this.form.submit()">
                        <option value="">Tất cả phân loại</option>
                        @foreach($classifications as $classification)
                            <option value="{{ $classification }}" @selected(request('classification') === $classification)>{{ $classification }}</option>
                        @endforeach
                    </select>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs uppercase font-bold hover:bg-slate-700 transition">Tìm kiếm</button>
                        @if(request()->hasAny(['search', 'classification']))
                            <a href="{{ route($prefix . '.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold hover:bg-slate-300 transition">Xóa lọc</a>
                        @endif
                    </div>
                </form>

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
                                <th class="py-2.5 px-3 border-r border-slate-300">Tên khoa học</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Ghi chú</th>
                                @if($canManage)
                                    <th class="py-2.5 px-3 text-center w-24">Hành động</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800">
                            @forelse($items as $index => $item)
                                <tr id="item-{{ $item->id }}" class="hover:bg-blue-50/40 transition">
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono text-slate-600">{{ $items->firstItem() + $index }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200">{{ $item->classification ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono">{{ $item->sku }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900">{{ $item->name }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-semibold text-blue-700">{{ $item->unit ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200">{{ $item->origin ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200">{{ $item->part_used ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 italic">{{ $item->scientific_name ?? '---' }}</td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600 truncate max-w-[150px]" title="{{ $item->note }}">{{ $item->note ?? '---' }}</td>
                                    @if($canManage)
                                        <td class="py-2 px-3 text-center space-x-2 whitespace-nowrap">
                                            <a href="{{ route($prefix . '.edit', $item->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">Sửa</a>
                                            <form action="{{ route($prefix . '.destroy', $item->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mục này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold cursor-pointer">Xóa</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManage ? 10 : 9 }}" class="py-6 text-center text-slate-500 text-xs italic bg-slate-50">
                                        {{ request('search') ? 'Không tìm thấy kết quả phù hợp.' : 'Chưa có dữ liệu trong danh mục này.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-t border-slate-300 bg-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select :default="100" />
                    @if($items->hasPages())
                        {{ $items->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
