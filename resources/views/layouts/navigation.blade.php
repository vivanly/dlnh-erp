<nav class="bg-white border-b border-slate-200 shadow-sm sticky top-0 z-50">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center space-x-8">
                <!-- Logo & Tên hệ thống ERP -->
                <div class="shrink-0 flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <img src="{{ asset('images/logo.png') }}" alt="ERP Logo" class="block h-10 w-auto object-contain">
                        <span class="font-bold text-lg tracking-tight text-slate-800 group-hover:text-blue-600 transition">Hệ Thống ERP Nhà Máy</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:flex h-full items-center">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('dashboard') ? 'border-blue-600 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        {{ __('Tổng quan') }}
                    </x-nav-link>

                    <x-nav-link :href="route('departments.index')" :active="request()->routeIs('departments*')" class="inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('departments*') ? 'border-blue-600 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        {{ __('Phòng ban') }}
                    </x-nav-link>

                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users*')" class="inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('users*') ? 'border-blue-600 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        {{ __('Nhân sự') }}
                    </x-nav-link>

                    <!-- Thêm mục Quản lý Sản phẩm Dược liệu -->
                    <x-nav-link :href="route('products.index')" :active="request()->routeIs('products*')" class="inline-flex items-center px-3 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ request()->routeIs('products*') ? 'border-blue-600 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300' }}">
                        {{ __('Sản phẩm') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <details class="relative">
                    <summary class="list-none cursor-pointer inline-flex items-center px-3 py-2 border border-slate-200 text-sm leading-5 font-medium rounded-lg text-slate-700 bg-slate-50 hover:bg-slate-100 hover:text-slate-900 focus:outline-none transition">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            <span class="font-semibold">{{ Auth::user()->name }}</span>
                        </div>
                        <div class="ms-2">
                            <svg class="fill-current h-4 w-4 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </summary>

                    <!-- Menu nội dung thả xuống -->
                    <div class="absolute right-0 z-50 mt-2 w-52 rounded-xl shadow-xl py-1 bg-white text-slate-700 border border-slate-100 ring-1 ring-black ring-opacity-5">
                        <div class="px-4 py-2 border-b border-slate-100 text-xs text-slate-400 font-semibold uppercase">Tài khoản hệ thống</div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600">Thông tin cá nhân</a>
                        <a href="{{ route('password.edit') }}" class="block px-4 py-2 text-sm hover:bg-blue-50 hover:text-blue-600">Đổi mật khẩu</a>

                        <div class="border-t border-slate-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </div>
</nav>