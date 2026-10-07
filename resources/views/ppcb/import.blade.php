<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Import Phương pháp chế biến từ Excel') }}
            </h2>
            <a href="{{ route('ppcb.index') }}" class="text-xs font-bold uppercase text-slate-600 hover:text-slate-900">
                Quay lại danh sách
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-4xl mx-auto px-2 space-y-3">
            @if($errors->any())
                <div class="p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-3 bg-amber-50 border-l-4 border-amber-600 text-amber-900 text-xs">
                    <p>{{ session('warning') }}</p>
                    @if(session('import_errors'))
                        <ul class="list-disc pl-5 mt-2 space-y-1">
                            @foreach(session('import_errors') as $failure)
                                <li>Dòng {{ $failure['row'] }}: {{ implode('; ', $failure['messages']) }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <div class="bg-white border border-slate-300 p-4">
                <form action="{{ route('ppcb.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label for="file" class="block text-xs font-bold text-slate-700 mb-2">
                            Chọn file Excel (.xlsx, .xls, .csv), tối đa 2MB
                        </label>
                        <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required
                            class="w-full text-xs border border-slate-300 p-2">
                    </div>

                    <button type="submit" class="px-3 py-2 bg-emerald-600 text-white text-xs font-bold uppercase hover:bg-emerald-700">
                        Import phương pháp chế biến
                    </button>
                </form>

                <div class="mt-5 border-t border-slate-200 pt-4 text-xs text-slate-600 space-y-2">
                    <p class="font-bold text-slate-800">Dùng file Export PPCB hoặc file có hàng tiêu đề:</p>
                    <p><code>ma</code>, <code>ten_ppcb</code>, <code>chi_tiet_ppcb</code>, <code>ghi_chu</code></p>
                    <p>Bắt buộc: <code>ma</code> và <code>ten_ppcb</code>. Mã PPCB đã tồn tại sẽ bị bỏ qua và báo lỗi.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
