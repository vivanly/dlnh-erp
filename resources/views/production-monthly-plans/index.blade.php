<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Kế hoạch sản xuất tháng</h2>
                <p class="mt-1 text-xs text-slate-500">Nhập sản lượng theo tháng, gửi Ban Giám Đốc duyệt và sinh lệnh sản xuất không gắn mã lô trước.</p>
            </div>
        </div>
    </x-slot>
    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($canManagePlans)
                @include('production-monthly-plans._form', ['action' => route('production-monthly-plans.store'), 'method' => 'POST'])
            @endif

            <div class="overflow-x-auto border border-slate-300 bg-white">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-[10px] uppercase text-slate-600"><tr><th class="p-2.5">Tháng</th><th class="p-2.5">Sản phẩm / sản lượng</th><th class="p-2.5">Trạng thái</th><th class="p-2.5">Lý do trả</th><th class="p-2.5 text-center">Thao tác</th></tr></thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($plans as $plan)
                            <tr>
                                <td class="p-2.5 font-mono font-semibold">{{ $plan->plan_month->format('m/Y') }}</td>
                                <td class="p-2.5">@foreach($plan->lines as $line)<div>{{ $line->product->name }} · <span class="font-mono">{{ number_format((float) $line->planned_quantity, 4) }} {{ $line->unit }}</span></div>@endforeach</td>
                                <td class="p-2.5">{{ ['draft' => 'Nháp', 'pending_approval' => 'Chờ Ban Giám Đốc duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Cần chỉnh sửa'][$plan->status] ?? $plan->status }}</td>
                                <td class="p-2.5 text-rose-700">{{ $plan->rejection_reason }}</td>
                                <td class="p-2.5 text-center whitespace-nowrap">
                                    @if($canManagePlans && in_array($plan->status, ['draft', 'rejected'], true))
                                        <a href="{{ route('production-monthly-plans.edit', $plan) }}" class="border border-slate-300 px-2.5 py-1.5 text-[10px] font-semibold text-slate-700 hover:bg-slate-50">Sửa</a>
                                        <form method="POST" action="{{ route('production-monthly-plans.submit', $plan) }}" class="inline" onsubmit="return confirm('Gửi kế hoạch tháng này lên Ban Giám Đốc duyệt?');">@csrf<button class="bg-blue-700 px-2.5 py-1.5 text-[10px] font-semibold text-white hover:bg-blue-800">Gửi duyệt</button></form>
                                    @elseif($plan->status === 'approved')
                                        <span class="text-slate-500">{{ $plan->lines->filter(fn ($line) => $line->productionOrder)->count() }} lệnh đã tạo</span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-8 text-center text-slate-500">Chưa có kế hoạch tháng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $plans->links() }}
        </div>
    </div>
</x-app-layout>
