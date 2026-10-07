<nav class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-xs sticky top-0 z-50">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center space-x-8">
                <!-- Logo & Tên hệ thống ERP -->
                <div class="shrink-0 flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="p-2 bg-blue-50 rounded-xl group-hover:bg-blue-100 transition">
                            <img src="{{ asset('images/logo.png') }}" alt="ERP Logo" class="block h-7 w-auto object-contain">
                        </div>
                        <div class="flex flex-col">
                            <span class="font-extrabold text-sm tracking-wide text-slate-900 group-hover:text-blue-600 transition">HỆ THỐNG ERP</span>
                            <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Quản lý Nhà máy</span>
                        </div>
                    </a>
                </div>

                <!-- TỰ ĐỘNG GỌI FILE CHỨA MENU VÀO ĐÂY -->
                @include('layouts.partials.sidebar-links')

            </div>

            <!-- Settings Dropdown & Quick Actions -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 space-x-3">
                <!-- Nút thông báo nhanh -->
                <button class="p-2 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 transition relative">
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full"></span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                </button>

                <div class="h-6 w-px bg-slate-200"></div>

                <!-- Dropdown tài khoản -->
                <details class="relative">
                    <summary class="list-none cursor-pointer inline-flex items-center px-3 py-1.5 border border-slate-200/80 text-sm leading-5 font-medium rounded-xl text-slate-700 bg-slate-50/50 hover:bg-slate-100 hover:text-slate-900 focus:outline-none transition shadow-2xs">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center text-xs">
                                {{ mb_substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <span class="font-semibold text-slate-800">{{ Auth::user()->name }}</span>
                        </div>
                        <div class="ms-2">
                            <svg class="fill-current h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </summary>

                    <!-- Menu nội dung tài khoản thả xuống -->
                    <div class="absolute right-0 z-50 mt-2 w-56 rounded-2xl shadow-xl py-1.5 bg-white text-slate-700 border border-slate-100 ring-1 ring-black ring-opacity-5">
                        <div class="px-4 py-2.5 border-b border-slate-100">
                            <p class="text-xs text-slate-400 font-semibold uppercase">Đang đăng nhập với</p>
                            <p class="text-sm font-bold text-slate-800 truncate">{{ Auth::user()->email ?? 'Tài khoản hệ thống' }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-blue-50 hover:text-blue-600 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Thông tin cá nhân
                        </a>
                        <a href="{{ route('password.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-blue-50 hover:text-blue-600 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                            Đổi mật khẩu
                        </a>

                        <div class="border-t border-slate-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-2 text-left px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 font-medium transition">
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 0H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </div>
</nav>