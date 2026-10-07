<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3"><h2 class="font-bold text-sm uppercase tracking-wide text-slate-800">Phương pháp bảo quản</h2>@if($canManage)<a href="{{ route('product-storage-methods.create') }}" class="bg-blue-700 px-3 py-1.5 text-xs font-bold uppercase text-white hover:bg-blue-800">Thêm phương pháp</a>@endif</div>
    </x-slot>
    <div class="py-2"><div class="max-w-none px-2 space-y-2">
        @if(session('success'))<div class="border-l-4 border-emerald-600 bg-emerald-50 p-3 text-xs text-emerald-900">{{ session('success') }}</div>@endif
        <div class="border border-slate-300 bg-white">
            <form method="GET" class="flex flex-col gap-2 border-b border-slate-300 bg-slate-100 p-3 sm:flex-row">
                <select name="product_id" aria-label="Lọc theo sản phẩm" class="text-xs border-slate-300"><option value="">Tất cả sản phẩm</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}</option>@endforeach</select>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm sản phẩm hoặc phương pháp bảo quản..." class="min-w-0 flex-1 text-xs border-slate-300"><button class="bg-slate-800 px-3 py-1.5 text-xs font-bold uppercase text-white">Tìm kiếm</button>
            </form>
            <div class="overflow-x-auto"><table class="w-full border-collapse text-left text-xs">
                <thead class="bg-slate-200 text-[11px] font-bold uppercase text-slate-700"><tr><th class="p-2.5">Sản phẩm</th><th class="p-2.5">Phương pháp bảo quản</th><th class="p-2.5">Ghi chú</th>@if($canManage)<th class="p-2.5 text-center">Thao tác</th>@endif</tr></thead>
                <tbody class="divide-y divide-slate-200 text-slate-800">
                    @forelse($storageMethods as $method)<tr class="align-top hover:bg-blue-50/30"><td class="p-2.5 font-semibold">{{ $method->product->name }}<span class="block font-mono text-[10px] font-normal text-slate-500">{{ $method->product->sku }}</span></td><td class="whitespace-pre-line p-2.5">{{ $method->storage_method }}</td><td class="whitespace-pre-line p-2.5">{{ $method->note ?: '—' }}</td>@if($canManage)<td class="whitespace-nowrap p-2.5 text-center"><a href="{{ route('product-storage-methods.edit', $method) }}" class="font-bold text-blue-700">Sửa</a><form method="POST" action="{{ route('product-storage-methods.destroy', $method) }}" class="ml-2 inline" onsubmit="return confirm('Xóa phương pháp bảo quản này?')">@csrf @method('DELETE')<button class="font-bold text-rose-700">Xóa</button></form></td>@endif</tr>
                    @empty<tr><td colspan="{{ $canManage ? 4 : 3 }}" class="p-6 text-center italic text-slate-500">Chưa có phương pháp bảo quản.</td></tr>@endforelse
                </tbody>
            </table></div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-300 bg-slate-100 p-3"><x-per-page-select />@if($storageMethods->hasPages()){{ $storageMethods->links() }}@endif</div>
        </div>
    </div></div>
</x-app-layout>