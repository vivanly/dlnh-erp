<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Thêm mới Phương pháp chế biến (PPCB)') }}
            </h2>
            <a href="{{ route('ppcb.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 border border-slate-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-slate-700 transition">
                Quay lại danh sách
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-none px-2 space-y-2">
            
            <div class="bg-white border border-slate-300 rounded-none shadow-none p-4">
                <form action="{{ route('ppcb.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Mã PPCB -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mã PPCB <span class="text-rose-600">*</span></label>
                            <input type="text" name="ma" value="{{ old('ma') }}" required class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 font-mono" placeholder="Nhập mã PPCB...">
                            @error('ma') 
                                <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p> 
                            @enderror
                        </div>

                        <!-- Tên PPCB -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tên PPCB <span class="text-rose-600">*</span></label>
                            <input type="text" name="ten_ppcb" value="{{ old('ten_ppcb') }}" required class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="Nhập tên phương pháp chế biến...">
                            @error('ten_ppcb') 
                                <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p> 
                            @enderror
                        </div>
                    </div>

                    <!-- Chi tiết PPCB -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Chi tiết PPCB</label>
                        <textarea name="chi_tiet_ppcb" rows="3" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="Nhập chi tiết PPCB...">{{ old('chi_tiet_ppcb') }}</textarea>
                        @error('chi_tiet_ppcb') 
                            <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p> 
                        @enderror
                    </div>

                    <!-- Ghi chú -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ghi chú</label>
                        <textarea name="ghi_chu" rows="2" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="Nhập ghi chú (nếu có)...">{{ old('ghi_chu') }}</textarea>
                        @error('ghi_chu') 
                            <p class="text-rose-600 text-[11px] mt-1">{{ $message }}</p> 
                        @enderror
                    </div>

                    <!-- Nút hành động -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                        <a href="{{ route('ppcb.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition">
                            Hủy
                        </a>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white text-xs uppercase font-bold rounded-none hover:bg-blue-700 transition">
                            Lưu dữ liệu
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>