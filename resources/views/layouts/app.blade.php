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
            .erp-bell { position: relative; }
            .erp-bell > summary { list-style: none; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border: 1px solid #e2e8f0; border-radius: 9999px; background: #fff; color: #475569; }
            .erp-bell > summary::-webkit-details-marker { display: none; }
            .erp-bell > summary:hover { background: #f1f5f9; }
            .erp-bell-badge { position: absolute; top: -.35rem; right: -.35rem; min-width: 1.1rem; height: 1.1rem; padding: 0 .25rem; border-radius: 9999px; background: #dc2626; color: #fff; font-size: .65rem; font-weight: 700; line-height: 1.1rem; text-align: center; }
            .erp-bell-badge[hidden] { display: none; }
            .erp-bell-panel { position: absolute; right: 0; top: 2.5rem; z-index: 60; width: 20rem; max-height: 24rem; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; box-shadow: 0 8px 24px rgb(15 23 42 / 15%); }
            .erp-bell-title { padding: .5rem .75rem; border-bottom: 1px solid #e2e8f0; font-size: .75rem; font-weight: 700; color: #0f172a; }
            .erp-bell-item { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .6rem .75rem; font-size: .75rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
            .erp-bell-item:hover { background: #eff6ff; color: #1d4ed8; }
            .erp-bell-count { flex-shrink: 0; min-width: 1.4rem; padding: .05rem .4rem; border-radius: 9999px; background: #fee2e2; color: #b91c1c; font-weight: 700; text-align: center; }
            .erp-bell-empty { padding: 1rem .75rem; font-size: .75rem; color: #64748b; text-align: center; }
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
                request()->routeIs(['users.*', 'departments.*', 'products.*', 'raw-materials.*', 'accessories.*']) => __('Quản trị dữ liệu'),
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
                        <details class="erp-bell" id="erp-bell" data-url="{{ route('notifications.summary') }}">
                            <summary aria-label="{{ __('Thông báo') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"></path>
                                </svg>
                                <span class="erp-bell-badge" id="erp-bell-badge" hidden>0</span>
                            </summary>
                            <div class="erp-bell-panel">
                                <div class="erp-bell-title">{{ __('Việc cần xử lý') }}</div>
                                <div id="erp-bell-list"><div class="erp-bell-empty">{{ __('Đang tải...') }}</div></div>
                            </div>
                        </details>
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
        <script>
            (function () {
                var bell = document.getElementById('erp-bell');
                if (!bell) return;
                var badge = document.getElementById('erp-bell-badge');
                var list = document.getElementById('erp-bell-list');
                var emptyText = @json(__('Không có việc nào cần xử lý.'));
                var lastTotal = null;

                function render(data) {
                    badge.textContent = data.total > 99 ? '99+' : data.total;
                    badge.hidden = data.total === 0;
                    list.innerHTML = '';
                    if (!data.items.length) {
                        var empty = document.createElement('div');
                        empty.className = 'erp-bell-empty';
                        empty.textContent = emptyText;
                        list.appendChild(empty);
                        return;
                    }
                    data.items.forEach(function (item) {
                        var link = document.createElement('a');
                        link.className = 'erp-bell-item';
                        link.href = item.url;
                        var label = document.createElement('span');
                        label.textContent = item.label;
                        var count = document.createElement('span');
                        count.className = 'erp-bell-count';
                        count.textContent = item.count;
                        link.appendChild(label);
                        link.appendChild(count);
                        list.appendChild(link);
                    });
                    if (lastTotal !== null && data.total > lastTotal) {
                        bell.classList.add('has-new');
                    }
                    lastTotal = data.total;
                }

                function load() {
                    if (document.hidden) return;
                    fetch(bell.dataset.url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                        .then(function (r) { return r.ok ? r.json() : null; })
                        .then(function (data) { if (data) render(data); })
                        .catch(function () {});
                }

                load();
                setInterval(load, 15000);
                document.addEventListener('visibilitychange', load);
                document.addEventListener('click', function (e) { if (!bell.contains(e.target)) bell.removeAttribute('open'); });
            })();
        </script>
        @stack('scripts')
    </body>
</html>