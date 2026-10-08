<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Lô nội bộ chờ QC cập nhật PKN</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $batches->total() }} lô cần hoàn thiện hồ sơ.</p>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">
            @if(session('success'))<div class="p-3 bg-emerald-50 border-l-4 border-emerald-600 text-xs text-emerald-900">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-xs text-rose-900"><ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <form method="GET" class="bg-white border border-slate-300 p-3 flex gap-2">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mã lô hoặc sản phẩm" class="w-full sm:max-w-md text-xs border-slate-300 rounded-none py-1.5">
                <button class="px-3 py-1.5 bg-slate-800 text-xs font-bold uppercase text-white">Tìm</button>
            </form>

            @forelse($batches as $batch)
                @if($canManageQuality)
                <form method="POST" enctype="multipart/form-data" action="{{ route('qa.production-batches.approve', $batch) }}" class="bg-white border border-slate-300 p-3">
                    @csrf
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6 items-end">
                        <div class="xl:col-span-2">
                            <span class="block text-[10px] uppercase text-slate-500">Sản phẩm</span>
                            <strong class="text-xs">{{ $batch->product->name }}</strong>
                            <span class="block font-mono text-[10px] text-slate-500">{{ $batch->product->sku ?: '---' }} · {{ $batch->product->classification ?: '---' }} · {{ $batch->unit }}</span>
                            <span class="block text-[10px] text-slate-500">Nguồn gốc: {{ $batch->origin ?: '---' }} · PPCB: {{ $batch->ppcb ? $batch->ppcb->ma . ' · ' . $batch->ppcb->ten_ppcb : '---' }}</span>
                            <span class="block text-[10px] text-slate-500">@if($batch->orderAllocations->isNotEmpty()) @foreach($batch->orderAllocations as $allocation){{ $allocation->productionOrder?->production_code }} · {{ number_format((float) $allocation->quantity, 4) }} {{ $batch->unit }} @endforeach @else {{ $batch->productionOrder ? $batch->productionOrder->production_code . ' · ' . ($batch->productionOrder->order->order_code ?? '---') : 'Lô độc lập' }} @endif</span>
                        </div>
                        <label class="text-xs">Mã dự trù
                            <input value="{{ $batch->provisional_batch_number ?: $batch->batch_number }}" readonly class="mt-1 w-full text-xs bg-slate-100 border-slate-300 rounded-none">
                        </label>
                        <div class="text-xs"><span class="block text-[10px] text-slate-500">Mã lô QA đã cấp</span><strong class="mt-1 block font-mono">{{ $batch->batch_number }}</strong></div>
                        <label class="text-xs">Số PKN
                            <input name="qc_test_report" value="{{ old('qc_test_report', $batch->qc_test_report) }}" required maxlength="255" class="mt-1 w-full text-xs border-slate-300 rounded-none">
                        </label>
                        <label class="text-xs">Ngày ra phiếu
                            <input type="date" name="qc_date" value="{{ old('qc_date', optional($batch->qc_date)->format('Y-m-d')) }}" required class="mt-1 w-full text-xs border-slate-300 rounded-none">
                        </label>
                        <label class="text-xs">File PKN (PDF/JPG/PNG)
                            <input type="file" name="qc_test_report_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required class="mt-1 w-full text-xs">
                        </label>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 border-t border-slate-200 pt-3 text-[10px] text-slate-600 sm:grid-cols-4">
                        <div>Số lô: <strong class="font-mono">{{ $batch->batch_number }}</strong></div>
                        <div>NSX: <strong>{{ optional($batch->mfg_date)->format('d/m/Y') ?: '---' }}</strong></div>
                        <div>HSD: <strong>{{ optional($batch->exp_date)->format('d/m/Y') ?: '---' }}</strong></div>
                        <div>Giấy phép: <strong>{{ $batch->license_number ?: '---' }}</strong></div>
                        <div>Tồn kho: <strong>{{ number_format((float) $batch->current_quantity, 4) }} {{ $batch->unit }}</strong></div>
                        <div>Chờ nhập: <strong>{{ number_format((float) $batch->pending_warehouse_quantity, 4) }} {{ $batch->unit }}</strong></div>
                        <div>PKN hiện tại: <strong>{{ $batch->qc_result === 'passed' ? 'Đạt' : ($batch->qc_result === 'failed' ? 'Không đạt' : 'Chưa có kết quả') }}</strong></div>
                        @if($batch->qc_test_report_file)<div><a href="{{ route('private-documents.production-qc-report', $batch) }}" target="_blank" rel="noopener" class="font-semibold text-blue-700 underline">Xem file PKN hiện tại</a></div>@endif
                    </div>
                    <div class="mt-3 flex flex-wrap justify-end gap-2">
                        <button name="qc_result" value="failed" class="border border-rose-300 px-3 py-2 text-xs font-bold uppercase text-rose-700 hover:bg-rose-50">Ghi nhận PKN không đạt</button>
                        <button name="qc_result" value="passed" class="bg-emerald-700 px-3 py-2 text-xs font-bold uppercase text-white hover:bg-emerald-800">Ghi nhận PKN đạt</button>
                    </div>
                </form>
                @else
                    <section class="bg-white border border-slate-300 p-3 text-xs">
                        <strong>{{ $batch->product->name }}</strong>
                        <p class="mt-1 font-mono text-slate-600">{{ $batch->batch_number }} · {{ $batch->unit }}</p>
                        <p class="mt-1 text-slate-600">PKN: {{ $batch->qc_test_report ?: 'Chưa có' }} · Kết quả: {{ $batch->qc_result ?: 'Chưa có kết quả' }}</p>
                        @if($batch->qc_test_report_file)
                            <a href="{{ route('private-documents.production-qc-report', $batch) }}" target="_blank" rel="noopener" class="mt-1 inline-block text-blue-700 underline">Xem file PKN</a>
                        @endif
                    </section>
                @endif
            @empty
                <div class="border border-slate-300 bg-white p-8 text-center text-xs text-slate-500">Không có lô thành phẩm chờ QC.</div>
            @endforelse

            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-per-page-select />
                {{ $batches->links() }}
            </div>

        </div>
    </div>
</x-app-layout>