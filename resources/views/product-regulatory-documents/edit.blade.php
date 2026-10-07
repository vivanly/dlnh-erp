<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">Sửa hồ sơ sản phẩm</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4">
            <div class="bg-white border border-slate-300 p-6">
                @include('product-regulatory-documents.form', [
                    'action' => route('product-regulatory-documents.update', $productRegulatoryDocument),
                    'method' => 'PUT',
                    'document' => $productRegulatoryDocument,
                ])
            </div>
        </div>
    </div>
</x-app-layout>
