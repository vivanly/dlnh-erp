<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('logo.ico') }}">
        <title>{{ config('app.name', 'Dược Liệu Ninh Hiệp ERP') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite('resources/css/app.css')

        <style>
            .ajax-autocomplete { position: relative; width: 100%; }
            .ajax-autocomplete-field { position: relative; }
            .ajax-autocomplete-input { width: 100%; padding-right: 2rem; }
            .ajax-autocomplete-clear { position: absolute; top: 50%; right: .5rem; transform: translateY(-50%); color: #64748b; font-size: 1.1rem; line-height: 1; }
            .ajax-autocomplete-dropdown { position: fixed; z-index: 50; max-height: 16rem; overflow-y: auto; border: 1px solid #cbd5e1; background: #fff; box-shadow: 0 4px 8px rgb(15 23 42 / 12%); }
            .ajax-autocomplete-option, .ajax-autocomplete-message { display: block; width: 100%; padding: .5rem .75rem; text-align: left; font-size: .75rem; color: #1e293b; }
            .ajax-autocomplete-option:hover, .ajax-autocomplete-option.is-active { background: #eff6ff; }
            .ajax-autocomplete-description { display: block; margin-top: .125rem; color: #64748b; }
            .ajax-autocomplete-message { color: #64748b; }
        </style>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('js/ajax-autocomplete.js') }}"></script>

        <!-- Điểm đón các file CSS từ trang con -->
        @stack('styles')
    </head>
    <body class="erp-shell font-sans antialiased bg-slate-100 text-slate-800 text-sm m-0 p-0">
        @php
            $workspaceLabel = match (true) {
                request()->routeIs('dashboard') => __('Tổng quan'),
                request()->routeIs('profile.*', 'password.*') => __('Tài khoản'),
                request()->routeIs(['orders.*', 'sales-order-approvals.*', 'purchase-orders.*', 'customers.*', 'suppliers.*']) => __('Kinh doanh'),
                request()->routeIs('warehouse.*') => __('Kho vận'),
                request()->routeIs(['production*', 'boms.*']) => __('Kế hoạch & sản xuất'),
                request()->routeIs(['qa.*', 'product-regulatory-documents.*', 'ppcb.*']) => __('Chất lượng'),
                request()->routeIs('traceability.*') => __('Truy xuất nguồn gốc'),
                request()->routeIs(['users.*', 'departments.*', 'products.*']) => __('Quản trị dữ liệu'),
                default => __('Không gian làm việc'),
            };
        @endphp
        <div class="erp-app-frame flex m-0 p-0">
            <input id="erp-mobile-nav" class="erp-mobile-nav-state" type="checkbox" aria-label="Mở menu điều hướng">
            <label for="erp-mobile-nav" class="erp-mobile-nav-overlay" aria-label="Đóng menu điều hướng"></label>

            <aside class="erp-sidebar w-64 bg-white flex flex-col sticky top-0 h-screen z-30 select-none shrink-0" aria-label="Điều hướng chính">
                <div class="erp-sidebar-content">
                    <div class="erp-sidebar-brand flex items-center px-5 border-b border-slate-200">
                        <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5 group">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white shadow-sm transition group-hover:border-blue-300">
                                <img src="{{ asset('images/logo.png') }}" alt="ERP Logo" class="block h-6 w-auto object-contain">
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold text-[13px] tracking-tight text-slate-900 group-hover:text-blue-700">Dược Liệu Ninh Hiệp</span>
                                <span class="mt-0.5 whitespace-nowrap text-[8px] font-semibold uppercase tracking-[0.06em] text-slate-400">Uy Tín Tạo Niềm Tin</span>
                            </div>
                        </a>
                    </div>

                    @include('layouts.partials.sidebar-links')
                </div>
            </aside>

            <div class="erp-main-column flex min-w-0 flex-1 flex-col m-0 p-0">
                <header class="erp-topbar">
                    <div class="erp-topbar-main">
                        <label for="erp-mobile-nav" class="erp-mobile-nav-button" aria-label="Mở menu điều hướng">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </label>
                        <nav class="erp-breadcrumb" aria-label="Breadcrumb">
                            <a href="{{ route('dashboard') }}" class="erp-breadcrumb-home">ERP</a>
                            <svg class="h-3.5 w-3.5 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"></path>
                            </svg>
                            <span>{{ $workspaceLabel }}</span>
                        </nav>
                        <div class="erp-topbar-spacer"></div>
                        <nav class="flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px]" aria-label="{{ __('Ngôn ngữ') }}">
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'vi']) }}" lang="vi" hreflang="vi" @class(['font-bold text-blue-700' => app()->getLocale() === 'vi', 'text-slate-500 hover:text-slate-900' => app()->getLocale() !== 'vi']) aria-current="{{ app()->getLocale() === 'vi' ? 'true' : 'false' }}">VI</a>
                            <span class="text-slate-300" aria-hidden="true">|</span>
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" lang="en" hreflang="en" @class(['font-bold text-blue-700' => app()->getLocale() === 'en', 'text-slate-500 hover:text-slate-900' => app()->getLocale() !== 'en']) aria-current="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">EN</a>
                        </nav>
                        <span class="erp-topbar-date">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14H3V6a2 2 0 012-2z"></path>
                            </svg>
                            {{ app()->getLocale() === 'en' ? now()->format('m/d/Y') : now()->format('d/m/Y') }}
                        </span>
                        <details class="erp-user-menu relative">
                            <summary class="erp-user-trigger list-none cursor-pointer">
                                @if(Auth::user()->avatar_path)
                                    <img src="{{ route('profile.avatar') }}" alt="" class="h-8 w-8 shrink-0 rounded-full border border-slate-200 object-cover">
                                @else
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-700 text-xs font-bold text-white">
                                        {{ mb_substr(Auth::user()->name, 0, 1) }}
                                    </span>
                                @endif
                                <span class="erp-user-name">{{ Auth::user()->name }}</span>
                                <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"></path>
                                </svg>
                            </summary>
                            <div class="erp-user-dropdown">
                                <a href="{{ route('profile.edit') }}">{{ __('Thông tin cá nhân') }}</a>
                                <a href="{{ route('password.edit') }}">{{ __('Đổi mật khẩu') }}</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit">{{ __('Đăng xuất') }}</button>
                                </form>
                            </div>
                        </details>
                    </div>
                    @isset($header)
                        <div class="erp-page-toolbar">
                            {{ $header }}
                        </div>
                    @endisset
                </header>
                <main class="erp-main-content flex-1 m-0 p-0">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- SCRIPTS ĐẶT Ở DƯỚI CÙNG ĐỂ ĐẢM BẢO DOM ĐÃ RENDER XONG -->
        <!-- Điểm đón các đoạn script AJAX từ trang con -->
        @stack('scripts')
    </body>
</html>