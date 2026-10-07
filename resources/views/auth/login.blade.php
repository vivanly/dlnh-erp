<x-guest-layout>
<div>
    <div class="mb-7">
        <p class="mb-2.5 inline-flex items-center gap-2 rounded-full bg-[#edf3eb] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-[#45694b]">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 4c-8.5 0-14 4.1-14 10a6 6 0 0 0 6 6c5.9 0 10-5.5 8-16Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 21c2-5 5.5-8.5 11-11" />
            </svg>
            {{ __('Không gian làm việc') }}
        </p>
        <h2 class="text-[1.7rem] font-semibold tracking-tight text-slate-900 sm:text-[1.9rem]">{{ __('Chào mừng trở lại') }}</h2>
        <p class="mt-1.5 text-[13px] leading-5 text-slate-500">{{ __('Đăng nhập để tiếp tục công việc của bạn.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="employee_code" class="mb-1.5 block text-[13px] font-semibold text-slate-700">{{ __('Mã nhân viên') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 20H5a2 2 0 0 1-2-2v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1a2 2 0 0 1-1 1.732M15 4.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8ZM19 8h3m-1.5-1.5v3" />
                    </svg>
                </span>
                <input id="employee_code" class="block w-full rounded-lg border border-slate-200 bg-white py-3 pl-10 pr-4 text-[13px] text-slate-900 shadow-sm shadow-slate-900/[0.02] outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-[#527c4f] focus:ring-4 focus:ring-[#527c4f]/10" type="text" name="employee_code" value="{{ old('employee_code') }}" placeholder="{{ __('Nhập mã nhân viên') }}" required autofocus autocomplete="username">
            </div>
            <x-input-error :messages="$errors->get('employee_code')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-[13px] font-semibold text-slate-700">{{ __('Mật khẩu') }}</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400" aria-hidden="true">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="10" width="16" height="11" rx="2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 1 1 8 0v3m-4 4v3" />
                    </svg>
                </span>
                <input id="password" class="block w-full rounded-lg border border-slate-200 bg-white py-3 pl-10 pr-4 text-[13px] text-slate-900 shadow-sm shadow-slate-900/[0.02] outline-none transition placeholder:text-slate-400 hover:border-slate-300 focus:border-[#527c4f] focus:ring-4 focus:ring-[#527c4f]/10" type="password" name="password" placeholder="{{ __('Nhập mật khẩu') }}" required autocomplete="current-password">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-0.5">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2 text-[13px] text-slate-600">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#315c40] shadow-sm focus:ring-[#527c4f]/30" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="rounded text-[13px] font-semibold text-[#315c40] transition hover:text-[#173d32] hover:underline focus:outline-none focus:ring-2 focus:ring-[#527c4f]/40 focus:ring-offset-2" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="group mt-1 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#244b36] px-5 py-3 text-[13px] font-semibold text-white shadow-md shadow-[#244b36]/15 transition hover:-translate-y-0.5 hover:bg-[#173d32] hover:shadow-lg hover:shadow-[#244b36]/20 focus:outline-none focus:ring-4 focus:ring-[#527c4f]/25 active:translate-y-0">
            {{ __('Log in') }}
            <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
            </svg>
        </button>
    </form>

    <div class="mt-5 flex items-center justify-center gap-2 text-[11px] text-slate-400">
        <svg class="h-4 w-4 text-[#68816b]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 5 6v5c0 4.6 2.9 8.4 7 10 4.1-1.6 7-5.4 7-10V6l-7-3Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4" />
        </svg>
        <span>{{ __('Kết nối được bảo vệ và bảo mật.') }}</span>
    </div>
</div>
</x-guest-layout>
