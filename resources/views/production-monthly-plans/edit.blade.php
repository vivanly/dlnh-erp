<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Chỉnh sửa kế hoạch sản xuất tháng</h2>
            <p class="mt-1 text-xs text-slate-500">Chỉ sửa được kế hoạch nháp/bị trả lại chưa tạo lệnh sản xuất.</p>
        </div>
    </x-slot>
    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @include('production-monthly-plans._form', ['action' => route('production-monthly-plans.update', $plan), 'method' => 'PUT'])
        </div>
    </div>
</x-app-layout>
