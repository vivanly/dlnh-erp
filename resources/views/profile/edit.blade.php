<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Tài khoản</p>
                <h1 class="mt-0.5 text-lg font-bold tracking-tight text-slate-900">Hồ sơ cá nhân</h1>
            </div>
            <a href="{{ route('password.edit') }}" class="hidden items-center gap-2 rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 sm:inline-flex">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 10V7a5 5 0 0110 0v3m-12 0h14v11H5V10zm7 4v3"></path>
                </svg>
                Bảo mật tài khoản
            </a>
        </div>
    </x-slot>

    <div class="erp-page space-y-5">
        <section class="erp-panel overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-slate-900 via-blue-950 to-blue-800 sm:h-28"></div>

            <div class="px-5 pb-5 sm:px-7 sm:pb-7">
                <div class="-mt-10 flex flex-col gap-4 sm:-mt-12 sm:flex-row sm:items-end sm:justify-between">
                    <div class="flex min-w-0 items-end gap-4">
                        <div class="shrink-0 rounded-full bg-white p-1.5 shadow-md">
                            @if($user->avatar_path)
                                <img id="profile-avatar-preview" src="{{ route('profile.avatar') }}" alt="Ảnh đại diện của {{ $user->name }}" class="h-20 w-20 rounded-full object-cover sm:h-24 sm:w-24">
                            @else
                                <span id="profile-avatar-fallback" class="flex h-20 w-20 items-center justify-center rounded-full bg-blue-700 text-2xl font-bold text-white sm:h-24 sm:w-24">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </span>
                                <img id="profile-avatar-preview" src="" alt="Xem trước ảnh đại diện" class="hidden h-20 w-20 rounded-full object-cover sm:h-24 sm:w-24">
                            @endif
                        </div>
                        <div class="min-w-0 pb-1">
                            <p class="text-xs font-medium text-slate-500">Hồ sơ nhân sự</p>
                            <h2 class="mt-1 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $user->name }}</h2>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $user->position ?? 'Nhân viên' }}{{ $user->department ? ' · '.$user->department->name : '' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pb-1">
                        @if($user->status === 'working' || !$user->status)
                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Đang làm việc
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                Nghỉ việc
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-6 grid gap-5 border-t border-slate-100 pt-5 lg:grid-cols-[minmax(0,1fr)_minmax(19rem,0.72fr)] lg:gap-8 lg:pt-6">
                    <section aria-labelledby="profile-details-heading">
                        <div class="mb-4">
                            <h3 id="profile-details-heading" class="text-sm font-bold text-slate-900">Thông tin nhân sự</h3>
                            <p class="mt-1 text-xs text-slate-500">Thông tin định danh và công tác của bạn trong hệ thống.</p>
                        </div>

                        <dl class="grid gap-x-8 sm:grid-cols-2">
                            <div class="border-b border-slate-100 py-3">
                                <dt class="text-xs font-medium text-slate-500">Mã nhân viên</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $user->employee_code ?? 'Chưa cập nhật' }}</dd>
                            </div>
                            <div class="border-b border-slate-100 py-3">
                                <dt class="text-xs font-medium text-slate-500">Phòng ban</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $user->department?->name ?? ($user->department_id ? 'Phòng ban #'.$user->department_id : 'Chưa gán phòng ban') }}</dd>
                            </div>
                            <div class="border-b border-slate-100 py-3">
                                <dt class="text-xs font-medium text-slate-500">Địa chỉ email</dt>
                                <dd class="mt-1.5 break-all text-sm font-medium text-slate-800">{{ $user->email }}</dd>
                            </div>
                            <div class="border-b border-slate-100 py-3">
                                <dt class="text-xs font-medium text-slate-500">Chức vụ</dt>
                                <dd class="mt-1.5 text-sm font-semibold text-slate-800">{{ $user->position ?? 'Chưa cập nhật' }}</dd>
                            </div>
                            <div class="border-b border-slate-100 py-3">
                                <dt class="text-xs font-medium text-slate-500">Số điện thoại</dt>
                                <dd class="mt-1.5 text-sm font-medium text-slate-800">{{ $user->phone ?: 'Chưa cập nhật' }}</dd>
                            </div>
                            <div class="border-b border-slate-100 py-3 sm:col-span-2">
                                <dt class="text-xs font-medium text-slate-500">Địa chỉ liên hệ</dt>
                                <dd class="mt-1.5 text-sm font-medium text-slate-800">{{ $user->address ?: 'Chưa cập nhật' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section aria-labelledby="avatar-heading" class="rounded-lg border border-slate-200 bg-slate-50/80 p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h3l1.5-2h7L17 7h3v12H4V7zm8 3a3 3 0 100 6 3 3 0 000-6z"></path>
                                </svg>
                            </span>
                            <div>
                                <h3 id="avatar-heading" class="text-sm font-bold text-slate-900">Ảnh đại diện</h3>
                                <p id="avatar-help" class="mt-1 text-xs leading-5 text-slate-500">Ảnh này hiển thị cạnh tên bạn trong hệ thống. JPG, PNG hoặc WebP, tối đa 2 MB.</p>
                            </div>
                        </div>

                        @if(session('status') === 'avatar-updated')
                            <div role="status" class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800">Đã cập nhật ảnh đại diện.</div>
                        @elseif(session('status') === 'avatar-deleted')
                            <div role="status" class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-800">Đã xóa ảnh đại diện.</div>
                        @endif

                        <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="mt-4">
                            @csrf
                            <input id="avatar" type="file" name="avatar" accept="image/jpeg,image/png,image/webp" aria-describedby="avatar-help" required class="sr-only">
                            <label for="avatar" class="flex cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white px-4 py-5 text-center transition hover:border-blue-400 hover:bg-blue-50/40 focus-within:ring-2 focus-within:ring-blue-500 focus-within:ring-offset-2">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4 4 4M4 16v4h16v-4"></path>
                                    </svg>
                                </span>
                                <span class="mt-2 text-xs font-semibold text-slate-700">Chọn ảnh từ thiết bị</span>
                                <span id="avatar-file-name" class="mt-1 max-w-full truncate text-[11px] text-slate-500">Chưa chọn tệp</span>
                            </label>

                            @error('avatar')
                                <p id="avatar-error" role="alert" class="mt-2 text-xs font-medium text-rose-700">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-blue-700 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Lưu ảnh đại diện
                                </button>
                                @if($user->avatar_path)
                                    <button type="submit" form="delete-avatar-form" class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2">
                                        Xóa ảnh
                                    </button>
                                @endif
                            </div>
                        </form>

                        @if($user->avatar_path)
                            <form id="delete-avatar-form" method="POST" action="{{ route('profile.avatar.destroy') }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endif
                    </section>
                </div>
            </div>
        </section>

        <section class="erp-panel flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-100">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 10V7a5 5 0 0110 0v3m-12 0h14v11H5V10zm7 4v3"></path>
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Bảo mật tài khoản</h3>
                    <p class="mt-1 text-xs text-slate-500">Cập nhật mật khẩu để bảo vệ tài khoản ERP của bạn.</p>
                </div>
            </div>
            <a href="{{ route('password.edit') }}" class="inline-flex items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Đổi mật khẩu
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"></path>
                </svg>
            </a>
        </section>
    </div>

    @push('scripts')
        <script>
            document.getElementById('avatar')?.addEventListener('change', function () {
                const file = this.files?.[0];
                const fileName = document.getElementById('avatar-file-name');
                const preview = document.getElementById('profile-avatar-preview');
                const fallback = document.getElementById('profile-avatar-fallback');

                if (!file) {
                    return;
                }

                fileName.textContent = file.name;
                preview.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
                fallback?.classList.add('hidden');
            });
        </script>
    @endpush
</x-app-layout>
