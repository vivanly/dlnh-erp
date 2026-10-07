<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Ban Giám Đốc · Duyệt kế hoạch sản xuất tháng</h2>
            <p class="mt-1 text-xs text-slate-500">Duyệt sẽ tạo lệnh sản xuất độc lập; chưa cấp số lô thành phẩm. QA cấp lô sau khi Sản xuất hoàn tất.</p>
        </div>
    </x-slot>
    <div class="py-2">
        <div class="max-w-none space-y-3 px-2">
            @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="border-l-4 border-rose-600 bg-rose-50 p-3 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @forelse($plans as $plan)
                <section class="border border-slate-300 bg-white">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 p-3">
                        <h3 class="text-sm font-bold text-slate-800">Kế hoạch {{ $plan->plan_month->format('m/Y') }}</h3>
                        <span class="text-xs text-slate-500">Người lập: {{ $plan->creator->name ?? '—' }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-[10px] uppercase text-slate-600"><tr><th class="p-2.5">Sản phẩm</th><th class="p-2.5">PPCB</th><th class="p-2.5 text-right">Sản lượng</th><th class="p-2.5">Ghi chú</th></tr></thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($plan->lines as $line)
                                    <tr><td class="p-2.5">{{ $line->product->name }}</td><td class="p-2.5">{{ optional($line->ppcb)->ma ?? optional($line->ppcb)->ten_ppcb ?? '---' }}</td><td class="p-2.5 text-right font-mono">{{ number_format((float) $line->planned_quantity, 4) }} {{ $line->unit }}</td><td class="p-2.5">{{ $line->notes }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($canApprovePlans)
                        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 p-3">
                            <form method="POST" action="{{ route('production-monthly-plans.reject', $plan) }}" class="flex flex-1 gap-2 sm:flex-none">@csrf<textarea name="reason" required maxlength="1000" placeholder="Lý do trả lại (bắt buộc)" class="min-w-64 border-slate-300 text-xs"></textarea><button class="border border-rose-300 px-3 py-2 text-xs font-bold text-rose-700 hover:bg-rose-50">Trả lại</button></form>
                            <form method="POST" action="{{ route('production-monthly-plans.approve', $plan) }}" onsubmit="return confirm('Duyệt kế hoạch và tạo các lệnh sản xuất?');">@csrf<button class="bg-emerald-700 px-4 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-800">Duyệt kế hoạch</button></form>
                        </div>
                    @endif
                </section>
            @empty
                <div class="border border-slate-300 bg-white p-8 text-center text-xs text-slate-500">Không có kế hoạch tháng chờ duyệt.</div>
            @endforelse
            {{ $plans->links() }}
        </div>
    </div>
</x-app-layout>
