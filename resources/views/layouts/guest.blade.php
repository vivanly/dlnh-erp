<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dược Liệu Ninh Hiệp ERP') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite('resources/css/app.css')
    </head>
    <body class="erp-guest-shell min-h-screen bg-[#f7f8f5] font-sans text-slate-900 antialiased">
        <main class="relative min-h-screen lg:grid lg:grid-cols-[1.05fr_0.95fr]">
            <section class="relative hidden min-h-screen overflow-hidden bg-[radial-gradient(ellipse_at_12%_8%,rgba(206,220,185,0.55),transparent_38%),radial-gradient(ellipse_at_88%_86%,rgba(232,218,190,0.55),transparent_34%),linear-gradient(145deg,#f6f7ef_0%,#eef2e7_54%,#f5f1e7_100%)] text-[#244333] lg:flex lg:flex-col lg:justify-between lg:px-12 lg:py-10 xl:px-16 xl:py-11" aria-label="{{ __('Giới thiệu hệ thống') }}">
                <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                    <div class="absolute inset-0 opacity-[0.09]" style="background-image: radial-gradient(rgba(76,105,67,.45) .65px, transparent .65px); background-size: 20px 20px;"></div>
                    <div class="absolute -right-40 top-[18%] h-[34rem] w-[34rem] rounded-full border border-[#78916b]/[0.13]"></div>
                    <div class="absolute -right-24 top-[24%] h-[27rem] w-[27rem] rounded-full border border-[#78916b]/[0.11]"></div>
                    <div class="absolute -right-8 top-[30%] h-[20rem] w-[20rem] rounded-full border border-[#78916b]/[0.09]"></div>
                    <svg class="absolute -right-3 top-[19%] h-[62%] w-[51%] text-[#66805a]/[0.2]" viewBox="0 0 360 520" fill="none">
                        <path d="M184 498c-9-105 5-209 53-319" stroke="currentColor" stroke-width="1.2"/>
                        <path d="M215 346c-77 9-127-23-151-98 76-4 127 28 151 98ZM242 265c-55-28-77-73-65-137 52 28 73 74 65 137ZM185 426c-78-7-127 24-149 94 74 8 125-24 149-94ZM264 202c-31-43-32-88-4-136 32 43 33 88 4 136Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
                        <path d="M220 339c-41-37-87-65-137-83m154 2c-28-38-48-80-60-127m12 292c-45-6-90 0-135 19m202-245c-3-36 2-71 17-103" stroke="currentColor" stroke-width="1" stroke-linecap="round"/>
                    </svg>
                    <div class="absolute -bottom-44 -left-36 h-[30rem] w-[30rem] rounded-full bg-[#b8c9a8]/30 blur-3xl"></div>
                </div>

                <a href="/" class="relative z-10 inline-flex w-fit items-center gap-3.5">
                    <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl bg-white p-1.5 shadow-md shadow-[#324d35]/10 ring-1 ring-[#dfe5d8]">
                        <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo Dược Liệu Ninh Hiệp') }}" class="h-full w-full object-contain">
                    </span>
                    <span class="flex flex-col">
                        <span class="text-[13px] font-bold tracking-wide text-[#244333]">{{ __('Dược Liệu Ninh Hiệp') }}</span>
                        <span class="mt-1 text-[9px] font-semibold uppercase tracking-[0.18em] text-[#71816e]">{{ __('Nền tảng quản trị doanh nghiệp') }}</span>
                    </span>
                </a>

                <div class="relative z-10 max-w-lg pb-7">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="h-px w-8 bg-[#9f8152]"></span>
                        <span class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[#75866b]">{{ __('Uy tín tạo niềm tin') }}</span>
                    </div>
                    <h1 class="max-w-lg text-3xl font-semibold leading-[1.22] tracking-tight text-[#244333] xl:text-[2.5rem]">
                        {{ __('Vận hành tinh gọn,') }}<br>
                        <span class="font-normal text-[#987b53]">{{ __('phát triển bền vững.') }}</span>
                    </h1>
                    <p class="mt-5 max-w-sm text-[13px] leading-6 text-[#647467]">
                        {{ __('Kết nối con người, quy trình và dữ liệu trên một nền tảng quản trị thống nhất.') }}
                    </p>
                    <div class="mt-7 flex items-center gap-3 text-[11px] text-[#718071]">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border border-[#d8dfd0] bg-white/70 text-[#66805a]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 4c-8.5 0-14 4.1-14 10a6 6 0 0 0 6 6c5.9 0 10-5.5 8-16Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 21c2-5 5.5-8.5 11-11" />
                            </svg>
                        </span>
                        <span>{{ __('Đồng hành cùng đội ngũ Dược Liệu Ninh Hiệp') }}</span>
                    </div>
                </div>

                <div class="relative z-10 flex items-center gap-4">
                    <span class="h-px w-9 bg-[#a9b59e]"></span>
                    <p class="text-[11px] tracking-wide text-[#7c897c]">© {{ date('Y') }} {{ __('Dược Liệu Ninh Hiệp') }} · ERP</p>
                </div>
            </section>

            <section class="relative flex min-h-screen flex-col overflow-hidden bg-gradient-to-br from-[#fbfcfa] via-[#f7f8f5] to-[#f0f3ed] px-5 py-5 sm:px-8 lg:px-10 xl:px-14" aria-label="{{ __('Đăng nhập') }}">
                <div class="pointer-events-none absolute -right-28 top-1/4 h-72 w-72 rounded-full bg-[#dfe8dc]/50 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -bottom-36 left-1/4 h-64 w-64 rounded-full bg-[#efe6d7]/40 blur-3xl" aria-hidden="true"></div>

                <nav class="relative z-10 flex justify-end" aria-label="{{ __('Ngôn ngữ') }}">
                    <div class="inline-flex items-center gap-1 rounded-full border border-slate-200/80 bg-white/80 p-1 text-xs shadow-sm shadow-slate-900/[0.03]">
                        <a href="{{ request()->fullUrlWithQuery(['lang' => 'vi']) }}" lang="vi" hreflang="vi" @class(['rounded-full px-3 py-1.5 font-semibold transition' => true, 'bg-[#173d32] text-white shadow-sm' => app()->getLocale() === 'vi', 'text-slate-500 hover:text-slate-900' => app()->getLocale() !== 'vi']) aria-current="{{ app()->getLocale() === 'vi' ? 'true' : 'false' }}">VI</a>
                        <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" lang="en" hreflang="en" @class(['rounded-full px-3 py-1.5 font-semibold transition' => true, 'bg-[#173d32] text-white shadow-sm' => app()->getLocale() === 'en', 'text-slate-500 hover:text-slate-900' => app()->getLocale() !== 'en']) aria-current="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">EN</a>
                    </div>
                </nav>

                <div class="relative z-10 flex flex-1 items-center justify-center py-8">
                    <div class="w-full max-w-[420px]">
                        <div class="mb-7 flex items-center gap-3 lg:hidden">
                            <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
                                <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo Dược Liệu Ninh Hiệp') }}" class="h-full w-full object-contain">
                            </span>
                            <span class="flex flex-col">
                                <span class="text-[13px] font-bold text-slate-900">{{ __('Dược Liệu Ninh Hiệp') }}</span>
                                <span class="mt-1 text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-500">ERP · {{ __('Uy tín tạo niềm tin') }}</span>
                            </span>
                        </div>

                        <div class="rounded-2xl border border-white/90 bg-white/90 p-6 shadow-[0_20px_60px_-28px_rgba(23,61,50,0.22)] ring-1 ring-[#e6ebe3] backdrop-blur-sm sm:p-8">
                            {{ $slot }}
                        </div>
                    </div>
                </div>

                <footer class="relative z-10 pb-1 text-center text-[11px] text-slate-400">
                    {{ __('Hệ thống nội bộ dành cho nhân sự được cấp quyền.') }}
                </footer>
            </section>
        </main>
    </body>
</html>
