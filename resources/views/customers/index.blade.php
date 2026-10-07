<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Quản lý Danh mục Khách hàng') }}
            </h2>
            <div class="flex items-center gap-2">
                <!-- NÚT THÊM KHÁCH HÀNG MỚI -->
                @if($canManageCustomers)
                <a href="{{ route('customers.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 border border-blue-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 transition shadow-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                    Thêm khách hàng mới
                </a>
                @endif
            </div>
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

            <!-- KHUNG CHỨA BẢNG -->
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                
                <!-- FORM TÌM KIẾM & LỌC ĐÃ TỐI ƯU -->
                <form method="GET" action="{{ route('customers.index') }}" class="p-3 border-b border-slate-300 bg-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="w-full sm:w-[420px] relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo Mã KH, Tên khách hàng, SĐT, Địa chỉ..." class="w-full text-xs pl-9 pr-8 border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5">
                        
                        @if(request('search'))
                            <a href="{{ route('customers.index') }}" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-rose-600" title="Xóa tìm kiếm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        @endif
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end flex-wrap">
                        <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs uppercase font-bold rounded-none hover:bg-slate-700 transition">
                            Tìm kiếm
                        </button>

                        @if(request('search'))
                            <a href="{{ route('customers.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition" title="Xóa từ khóa tìm kiếm">
                                Xóa lọc
                            </a>
                        @endif
                    </div>
                </form>

                <!-- BẢNG DỮ LIỆU KHÁCH HÀNG -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-200/80 border-b border-slate-300 text-[11px] font-bold text-slate-700 uppercase tracking-wider">
                                <th class="py-2.5 px-3 border-r border-slate-300 w-12 text-center">STT</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Mã KH</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Tên khách hàng</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-28">Phân loại</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Giấy phép kinh doanh</th>
                                <th class="py-2.5 px-3 border-r border-slate-300 w-32">Số điện thoại</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Địa chỉ</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Email</th>
                                <th class="py-2.5 px-3 border-r border-slate-300">Ghi chú</th>
                                <th class="py-2.5 px-3 text-center w-24">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs text-slate-800 font-normal">
                            @forelse ($customers as $index => $customer)
                                <tr class="hover:bg-blue-50/40 transition">
                                    <td class="py-2 px-3 border-r border-slate-200 text-center font-mono text-slate-600">
                                        {{ $customers->firstItem() + $index }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono font-bold text-slate-900">
                                        {{ $customer->code }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-bold text-slate-900">
                                        {{ $customer->name }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-700">
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 font-semibold rounded-none text-[10px]">
                                            {{ $customer->type }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono text-slate-700">
                                        {{ $customer->license_number ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 font-mono text-slate-700">
                                        {{ $customer->phone ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600 truncate max-w-[180px]" title="{{ $customer->address }}">
                                        {{ $customer->address ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600">
                                        {{ $customer->email ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 border-r border-slate-200 text-slate-600 truncate max-w-[150px]" title="{{ $customer->note }}">
                                        {{ $customer->note ?? '---' }}
                                    </td>
                                    <td class="py-2 px-3 text-center space-x-2 whitespace-nowrap">
                                        @if($canManageCustomers)
                                        <a href="{{ route('customers.edit', $customer->id) }}" class="text-blue-600 hover:text-blue-900 font-bold">Sửa</a>
                                        <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách hàng này không?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold cursor-pointer">Xóa</button>
                                        </form>
                                        @else
                                            <span class="text-slate-400">Chỉ xem</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="py-6 text-center text-slate-500 text-xs italic bg-slate-50">
                                        @if(request('search'))
                                            Không tìm thấy khách hàng nào phù hợp với từ khóa "<span class="font-bold text-slate-700">{{ request('search') }}</span>".
                                        @else
                                            Chưa có dữ liệu khách hàng trong hệ thống.
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
                    @if($customers->hasPages())
                        {{ $customers->links() }}
                    @endif
                </div>

            </div>

        </div>
    </div>
</x-app-layout>