<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                    Quản lý Nhà Cung Cấp
                </h2>
                <span class="px-2.5 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-none text-xs font-semibold">
                    Tổng: {{ $suppliers->total() ?? 0 }}
                </span>
            </div>

            {{-- KIỂM TRA QUYỀN ADMIN, SALES HOẶC IT CHO NÚT THÊM MỚI --}}
            @php
                $user = auth()->user();
                
                $isSales = $user && (
                    (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
                    (isset($user->department) && strtolower($user->department) === 'sales')
                );

                $isIT = $user && (
                    (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                    (isset($user->department) && strtolower($user->department) === 'it') ||
                    (isset($user->role) && strtolower($user->role) === 'it')
                );

                $canManage = $isSales || $isIT;
            @endphp

            @if($canManage)
                <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition shadow-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                    Thêm mới Nhà Cung Cấp
                </a>
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

            <!-- KHUNG CHỨA BẢNG -->
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                
                <!-- FORM TÌM KIẾM & LỌC ĐÃ TỐI ƯU -->
                <form method="GET" action="{{ route('suppliers.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="w-full sm:w-[420px] relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo tên, mã, sđt, email, người liên hệ..." class="w-full text-xs pl-9 pr-8 border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                        
                        @if(request('search'))
                            <a href="{{ route('suppliers.index') }}" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-rose-600" title="Xóa tìm kiếm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        @endif
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs uppercase font-bold rounded-none hover:bg-slate-700 transition">
                            Tìm kiếm
                        </button>

                        @if(request('search'))
                            <a href="{{ route('suppliers.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition" title="Xóa từ khóa tìm kiếm">
                                Xóa lọc
                            </a>
                        @endif
                    </div>
                </form>

                <!-- BẢNG DỮ LIỆU NHÀ CUNG CẤP -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Mã NCC</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Tên NCC</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-32">Điện Thoại</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Email</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Địa Chỉ</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Người Liên Hệ</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Ghi Chú</th>
                                <th class="py-2.5 px-3 text-center w-24">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse ($suppliers as $index => $supplier)
                                <tr class="hover:bg-blue-50/40 transition">
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                        {{ $suppliers->firstItem() + $index }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono font-bold text-blue-600">
                                        {{ $supplier->code }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900">
                                        {{ $supplier->name }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700 font-mono">
                                        {{ $supplier->phone ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">
                                        {{ $supplier->email ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700 max-w-xs truncate" title="{{ $supplier->address }}">
                                        {{ $supplier->address ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">
                                        {{ $supplier->contact_person ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600 max-w-xs truncate" title="{{ $supplier->notes }}">
                                        {{ $supplier->notes ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 text-center space-x-2 whitespace-nowrap">
                                        {{-- HIỂN THỊ NÚT SỬA VÀ XÓA NẾU CÓ QUYỀN QUẢN LÝ --}}
                                        @if($canManage)
                                            <a href="{{ route('suppliers.edit', $supplier->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">Sửa</a>
                                            <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhà cung cấp này không?');">
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
                                    <td colspan="9" class="py-6 text-center text-slate-500 text-xs italic bg-slate-50">
                                        @if(request('search'))
                                            Không tìm thấy nhà cung cấp nào phù hợp với từ khóa "<span class="font-bold text-slate-700">{{ request('search') }}</span>".
                                        @else
                                            Chưa có dữ liệu nhà cung cấp trong hệ thống.
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
                    @if(method_exists($suppliers, 'hasPages') && $suppliers->hasPages())
                        {{ $suppliers->links() }}
                    @endif
                </div>

            </div>

        </div>
    </div>
</x-app-layout>