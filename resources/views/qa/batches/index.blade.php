<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                Quản lý Lô Hàng Nhà Cung Cấp (Lô Gốc)
            </h2>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-3">

            @if(session('success'))
                <div class="p-3 bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-medium">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 bg-red-50 border border-red-300 text-red-800 text-xs font-medium">
                    {{ session('error') }}
                </div>
            @endif

            <!-- THANH TÌM KIẾM & BỘ LỌC -->
            <div class="bg-white border border-slate-300 p-3">
                <form action="{{ route('qa.batches.index') }}" method="GET" class="flex flex-wrap gap-2 items-center justify-between">
                    <div class="flex items-center gap-2 flex-1">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm theo số lô hoặc tên dược liệu..." class="w-full sm:w-80 text-xs border-slate-300 py-1.5">
                        
                        <select name="status" class="text-xs border-slate-300 py-1.5">
                            <option value="">-- Tất cả trạng thái COA --</option>
                            <option value="missing" {{ request('status') == 'missing' ? 'selected' : '' }}>Chưa có COA</option>
                            <option value="uploaded" {{ request('status') == 'uploaded' ? 'selected' : '' }}>Đã có COA</option>
                        </select>

                        <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs uppercase tracking-wider transition">
                            Tìm kiếm
                        </button>
                    </div>
                </form>
            </div>

            <!-- BẢNG DANH SÁCH LÔ GỐC -->
            <div class="bg-white border border-slate-300 overflow-hidden">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-200/80 font-bold uppercase text-[11px] text-slate-700 border-b border-slate-300">
                            <th class="p-2.5 border-r w-12 text-center">STT</th>
                            <th class="p-2.5 border-r">Dược liệu / Sản phẩm</th>
                            <th class="p-2.5 border-r">Số lô NCC</th>
                            <th class="p-2.5 border-r">Nhà cung cấp / Phiếu nhập</th>
                            <th class="p-2.5 border-r text-center w-24">Tồn thực tế</th>
                            <th class="p-2.5 border-r text-center w-20">Đã giữ</th>
                            <th class="p-2.5 border-r text-center w-24">Khả dụng</th>
                            <th class="p-2.5 border-r text-center w-24">Trạng thái tồn</th>
                            <th class="p-2.5 border-r text-center w-28">Trạng thái COA</th>
                            <th class="p-2.5 text-center w-32">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse($batches as $index => $batch)
                        <tr>
                            <td class="p-2.5 border-r text-center font-mono text-slate-500">
                                {{ $batches->firstItem() + $index }}
                            </td>
                            <td class="p-2.5 border-r font-medium text-slate-800">
                                {{ $batch->product->name ?? 'N/A' }}
                                <div class="text-[10px] text-slate-500 font-normal">
                                    NSX: {{ $batch->mfg_date ? date('d/m/Y', strtotime($batch->mfg_date)) : '---' }} | 
                                    HSD: {{ $batch->exp_date ? date('d/m/Y', strtotime($batch->exp_date)) : '---' }}
                                </div>
                            </td>
                            <td class="p-2.5 border-r font-mono font-bold text-blue-600">
                                {{ $batch->batch_number }}
                            </td>
                            <td class="p-2.5 border-r">
                                <div class="font-medium">{{ $batch->goodsReceiptItem->goodsReceipt->purchaseOrder->supplier->name ?? 'N/A' }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">
                                    Phiếu: {{ $batch->goodsReceiptItem->goodsReceipt->receipt_code ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="p-2.5 border-r text-center font-mono">{{ number_format($batch->current_quantity, 2) }}</td>
                            <td class="p-2.5 border-r text-center font-mono">{{ number_format($batch->reserved_quantity, 2) }}</td>
                            <td class="p-2.5 border-r text-center font-mono font-bold text-emerald-700">{{ number_format($batch->available_quantity, 2) }}</td>
                            <td class="p-2.5 border-r text-center text-[10px]">
                                @php
                                    $availabilityClass = match($batch->availability_status) {
                                        'Sắp hết' => 'bg-amber-100 text-amber-800 border-amber-300',
                                        'Còn khả dụng' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                        'Đã giữ hết', 'Hết hạn' => 'bg-rose-100 text-rose-800 border-rose-300',
                                        default => 'bg-slate-100 text-slate-700 border-slate-300',
                                    };
                                @endphp
                                <span class="inline-block border px-2 py-0.5 font-bold {{ $availabilityClass }}">{{ $batch->availability_status }}</span>
                            </td>
                            <td class="p-2.5 border-r text-center">
                                @if($batch->coa_file)
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase border border-emerald-300">
                                        Đã có COA
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase border border-amber-300">
                                        Chưa có COA
                                    </span>
                                @endif
                            </td>
                            <td class="p-2.5 text-center">
                                @if($canManageBatches && (auth()->user()->isQCDepartment() || auth()->user()->isITDepartment()) && $batch->status === 'pending_qa' && $batch->current_quantity > 0)
                                    <form method="POST" action="{{ route('supplier-batches.approve', $batch) }}" class="mb-2">
                                        @csrf
                                        <button class="w-full px-2 py-1 bg-emerald-700 text-white text-[10px] font-bold uppercase">QC xác nhận đạt</button>
                                    </form>
                
                                @endif

                                @if($batch->status !== 'pending_qa')<span class="text-slate-400 italic text-[10px]">{{ $batch->status === 'active' ? 'Đã đạt QC' : $batch->status }}</span>@endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="p-4 text-center text-slate-400 italic">
                                Không tìm thấy lô nhà cung cấp nào.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                
                <div class="p-3 bg-slate-50 border-t border-slate-300 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <x-per-page-select />
                    @if($batches->hasPages())
                        {{ $batches->links() }}
                    @endif
                </div>
            </div>


        </div>
    </div>
</x-app-layout>
