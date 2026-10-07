<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Quản lý Phương pháp chế biến (PPCB)') }}
            </h2>

            {{-- KIỂM TRA QUYỀN ADMIN, QA HOẶC IT CHO NÚT THÊM MỚI --}}
            @php
                $user = auth()->user();
                
                $isQA = $user && (
                    (method_exists($user, 'isQADepartment') && $user->isQADepartment()) ||
                    (method_exists($user, 'isQADepartmen') && $user->isQADepartmen()) ||
                    (isset($user->department) && strtolower($user->department) === 'qa')
                );

                $isIT = $user && (
                    (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                    (isset($user->department) && strtolower($user->department) === 'it') ||
                    (isset($user->role) && strtolower($user->role) === 'it')
                );

                $canManage = $isQA || $isIT;
            @endphp

            @if($canManage)
                <div class="flex items-center gap-2">
                    <a href="{{ route('ppcb.import.form') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Excel
                    </a>
                    <a href="{{ route('ppcb.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-700 border border-slate-800 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-slate-800 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('ppcb.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Thêm mới PPCB
                    </a>
                </div>
            @endif
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
                
                <!-- FORM TÌM KIẾM & LỌC -->
                <form method="GET" action="{{ route('ppcb.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="w-full sm:w-96">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo Mã, Tên PPCB, Chi tiết..." class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs uppercase font-bold rounded-none hover:bg-slate-700 transition">
                            Tìm kiếm
                        </button>

                        @if(request('search'))
                            <a href="{{ route('ppcb.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition" title="Xóa từ khóa tìm kiếm">
                                Xóa lọc
                            </a>
                        @endif
                    </div>
                </form>

                <!-- BẢNG DỮ LIỆU PPCB -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-32">Mã PPCB</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Tên PPCB</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Chi tiết PPCB</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Ghi chú</th>
                                <th class="py-2.5 px-3 text-center w-24">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse ($ppcbs as $index => $item)
                                <tr class="hover:bg-blue-50/40 transition">
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                        {{ $ppcbs->firstItem() + $index }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono font-bold text-slate-900">
                                        {{ $item->ma }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900">
                                        {{ $item->ten_ppcb }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">
                                        {{ $item->chi_tiet_ppcb ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600">
                                        {{ $item->ghi_chu ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 text-center space-x-2 whitespace-nowrap">
                                        {{-- KIỂM TRA QUYỀN ĐỂ HIỂN THỊ NÚT SỬA VÀ XÓA --}}
                                        @if($canManage)
                                            <a href="{{ route('ppcb.edit', $item->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">Sửa</a>
                                            <form action="{{ route('ppcb.destroy', $item->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bản ghi này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold cursor-pointer">Xóa</button>
                                            </form>
                                        @else
                                            <span class="text-slate-400 italic">Chỉ xem</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-slate-500 text-xs italic bg-slate-50">
                                        @if(request('search'))
                                            Không tìm thấy kết quả nào phù hợp với từ khóa "<span class="font-bold text-slate-700">{{ request('search') }}</span>".
                                        @else
                                            Chưa có dữ liệu phương pháp chế biến trong hệ thống.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer phân trang -->
                <div class="p-3 border-t border-slate-300 bg-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select />
                    @if(method_exists($ppcbs, 'hasPages') && $ppcbs->hasPages())
                        {{ $ppcbs->links() }}
                    @endif
                </div>

            </div>

        </div>
    </div>
</x-app-layout>