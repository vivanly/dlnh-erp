<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Quản lý Phòng ban Nhà máy') }}
            </h2>
            
            @if(auth()->check() && auth()->user()->isITDepartment())
                <div class="flex items-center gap-2">
                    <a href="{{ route('departments.import.form') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Excel
                    </a>
                    <a href="{{ route('departments.export') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-700 border border-slate-800 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-slate-800 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('departments.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Thêm Phòng Ban
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            
            <!-- Thông báo thành công -->
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs rounded-none flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- KHUNG CHỨA BẢNG -->
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                
                <!-- BẢNG DỮ LIỆU PHÒNG BAN -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-36">Mã phòng ban</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-64">Tên phòng ban</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Mô tả</th>
                                @if(auth()->check() && auth()->user()->isITDepartment())
                                    <th class="py-2.5 px-3 text-center w-28">Hành động</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse($departments as $dept)
                                <tr class="hover:bg-blue-50/40 transition">
                                    <!-- Cột STT tăng dần -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                        {{ $loop->iteration }}
                                    </td>
                                    <!-- Mã phòng ban -->
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono font-bold text-blue-700">
                                        {{ $dept->code }}
                                    </td>
                                    <!-- Tên phòng ban -->
                                    <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900">
                                        {{ $dept->name }}
                                    </td>
                                    <!-- Mô tả -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600">
                                        {{ $dept->description ?? '---' }}
                                    </td>
                                    <!-- Hành động (Chỉ hiển thị cho IT) -->
                                    @if(auth()->check() && auth()->user()->isITDepartment())
                                        <td class="py-2 px-3 text-center space-x-2">
                                            <a href="{{ route('departments.edit', $dept->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">Sửa</a>
                                            <form action="{{ route('departments.destroy', $dept->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phòng ban này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold cursor-pointer">Xóa</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ (auth()->check() && auth()->user()->isITDepartment()) ? 5 : 4 }}" class="py-6 text-center text-slate-500 text-xs italic bg-slate-50">
                                        Chưa có phòng ban nào được tạo.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>