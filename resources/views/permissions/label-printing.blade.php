<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700">Quản trị · Phân quyền</p>
            <h1 class="mt-0.5 text-base font-bold text-slate-900">Phân Quyền In Nhãn</h1>
        </div>
    </x-slot>

    <div class="erp-page space-y-3">
        @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs font-medium text-emerald-900">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ $errors->first() }}</div>@endif

        <section class="erp-panel overflow-hidden" aria-labelledby="label-permissions-title">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h2 id="label-permissions-title" class="text-sm font-bold text-slate-900">Danh sách tài khoản</h2>
                <p class="mt-1 text-xs text-slate-600">Chỉ xem. Quyền in nhãn dành cho người quản lý in nhãn.</p>
            </div>
            <form method="POST" action="{{ route('permissions.label-printing.update') }}">
                @csrf
                @method('PUT')
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] text-left text-xs">
                        <thead class="bg-slate-100 text-[10px] uppercase tracking-wider text-slate-600">
                            <tr><th class="px-4 py-2.5">Nhân viên</th><th class="px-4 py-2.5">Phòng ban</th><th class="px-4 py-2.5">Chức vụ</th><th class="px-4 py-2.5 text-center">Quyền in nhãn</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-blue-50/40">
                                    <td class="px-4 py-2.5"><span class="font-semibold text-slate-800">{{ $user->name }}</span><span class="mt-0.5 block text-[10px] text-slate-500">{{ $user->email }}</span></td>
                                    <td class="px-4 py-2.5 text-slate-600">{{ $user->department?->name ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-600">{{ $user->position ?: '—' }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if($user->isITDepartment())
                                            <input type="hidden" name="permissions[{{ $user->id }}]" value="1">
                                            <input type="checkbox" checked disabled aria-label="IT mặc định có quyền in nhãn" class="h-4 w-4 rounded border-slate-300 text-blue-700">
                                            <span class="ml-1 text-[10px] font-semibold text-blue-700">IT mặc định</span>
                                        @else
                                            <input type="checkbox" name="permissions[{{ $user->id }}]" value="1" @checked($user->can_print_labels) aria-label="Cấp quyền in nhãn cho {{ $user->name }}" class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-600">
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-[10px] text-slate-500">Tài khoản IT luôn có quyền in nhãn.</p>
                    <button type="submit" class="inline-flex items-center rounded border border-blue-700 bg-blue-700 px-4 py-2 text-xs font-bold text-white transition hover:bg-blue-800">Lưu phân quyền</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
