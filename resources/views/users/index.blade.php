<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Quản lý Nhân sự Nhà máy') }}
            </h2>
            
            {{-- Chỉ IT được thêm, import hoặc export nhân viên --}}
            @if(auth()->check() && auth()->user()->isITDepartment())
                <div class="flex items-center gap-2">
                    <a href="{{ route('users.import.form') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 border border-emerald-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-emerald-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import Excel
                    </a>
                    <a href="{{ route('users.export', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-700 border border-slate-800 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-slate-800 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('users.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition shadow-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Thêm Nhân Viên
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            
            {{-- Thông báo thành công --}}
            @if(session('success'))
                <div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-emerald-900 text-xs rounded-none flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
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
                <form method="GET" action="{{ route('users.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="w-full sm:w-80">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm mã NV, tên, email..." class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
                        <!-- Lọc theo phòng ban -->
                        <select name="department_id" onchange="this.form.submit()" class="text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                            <option value="">Tất cả phòng ban</option>
                            @if(isset($departments))
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>

                        <!-- Lọc theo trạng thái -->
                        <select name="status" onchange="this.form.submit()" class="text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                            <option value="">Tất cả trạng thái</option>
                            <option value="working" {{ request('status') == 'working' ? 'selected' : '' }}>Đang làm việc</option>
                            <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>Nghỉ việc</option>
                        </select>

                        <!-- Nút tìm kiếm văn bản -->
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs uppercase font-bold rounded-none hover:bg-slate-700 transition">
                            Lọc
                        </button>

                        <!-- Nút reset bộ lọc nếu đang có điều kiện lọc -->
                        @if(request()->anyFilled(['search', 'department_id', 'status', 'per_page']))
                            <a href="{{ route('users.index') }}" class="px-2.5 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition" title="Xóa bộ lọc">
                                X
                            </a>
                        @endif
                    </div>
                </form>

                <!-- BẢNG DỮ LIỆU CHÍNH CÓ CỘT STT VÀ ĐẦY ĐỦ THÔNG TIN -->
                <div class="overflow-x-auto">
                    @php
                        // Khai báo biến kiểm tra quyền IT
                        $isIT = auth()->check() && auth()->user()->isITDepartment();
                    @endphp

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-24">Mã NV</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Họ tên & Email</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Chức vụ</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Phòng ban</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Điện thoại</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Địa chỉ</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 text-center w-28">Trạng thái</th>
                                
                                {{-- Chỉ hiện cột hành động nếu là IT --}}
                                @if($isIT)
                                    <th class="py-2.5 px-3 text-center w-24">Hành động</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse($users as $user)
                                <tr class="hover:bg-blue-50/40 transition">
                                    <!-- Cột STT tăng dần chuẩn theo phân trang -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                        {{ $users->firstItem() + $loop->index }}
                                    </td>
                                    <!-- Mã nhân viên -->
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono font-bold text-blue-700">
                                        {{ $user->employee_code ?? '---' }}
                                    </td>
                                    <!-- Họ tên & Email -->
                                    <td class="py-2 px-3 border-r border-slate-200">
                                        <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $user->email }}</div>
                                    </td>
                                    <!-- Chức vụ -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700 font-medium">
                                        {{ $user->position ?? '---' }}
                                    </td>
                                    <!-- Phòng ban -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">
                                        {{ $user->department->name ?? 'Chưa phân bổ' }}
                                    </td>
                                    <!-- Số điện thoại -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700 font-mono">
                                        {{ $user->phone ?? '---' }}
                                    </td>
                                    <!-- Địa chỉ -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600 truncate max-w-xs" title="{{ $user->address }}">
                                        {{ $user->address ?? '---' }}
                                    </td>
                                    <!-- Trạng thái -->
                                    <td class="py-2 px-3 border-r border-slate-200 text-center">
                                        @if($user->status == 'working' || !$user->status)
                                            <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-none">
                                                Đang làm việc
                                            </span>
                                        @else
                                            <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300 rounded-none">
                                                Nghỉ việc
                                            </span>
                                        @endif
                                    </td>
                                    
                                    {{-- Chỉ hiện nút Sửa/Xóa nếu là IT --}}
                                    @if($isIT)
                                        <td class="py-2 px-3 text-center space-x-2">
                                            <a href="{{ route('users.edit', $user->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">Sửa</a>
                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhân viên này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold cursor-pointer">Xóa</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    {{-- Colspan tự động căn chỉnh (9 cột nếu là IT, 8 cột nếu không phải) --}}
                                    <td colspan="{{ $isIT ? 9 : 8 }}" class="py-6 text-center text-slate-500 text-xs italic bg-slate-50">
                                        Không tìm thấy bản ghi nhân sự phù hợp.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Footer phân trang -->
                @if(method_exists($users, 'links'))
                    <div class="p-3 border-t border-slate-300 bg-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <x-per-page-select :default="100" />
                        @if($users->hasPages())
                            {{ $users->links() }}
                        @endif
                    </div>
                @endif

            </div>

        </div>
    </div>
</x-app-layout>