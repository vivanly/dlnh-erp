<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                Thêm mới Nhà Cung Cấp
            </h2>
            <a href="{{ route('suppliers.index') }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-300 rounded-none font-semibold text-xs text-slate-700 uppercase tracking-wider hover:bg-slate-50 transition shadow-none">
                Quay lại
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-3xl mx-auto px-2">
            <div class="bg-white border border-slate-300 rounded-none shadow-none">
                
                <!-- TIÊU ĐỀ FORM -->
                <div class="p-3 border-b border-slate-300 bg-slate-100">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Thông tin nhà cung cấp mới</h3>
                </div>

                <form action="{{ route('suppliers.store') }}" method="POST" class="p-4 space-y-3">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mã NCC <span class="text-rose-600">*</span></label>
                        <input type="text" name="code" value="{{ old('code') }}" required class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 px-2.5">
                        @error('code')<p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tên NCC <span class="text-rose-600">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 px-2.5">
                        @error('name')<p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Điện thoại</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 px-2.5">
                            @error('phone')<p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 px-2.5">
                            @error('email')<p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Địa chỉ</label>
                        <textarea name="address" rows="2" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 px-2.5">{{ old('address') }}</textarea>
                        @error('address')<p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Người liên hệ</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person') }}" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 px-2.5">
                        @error('contact_person')<p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ghi chú</label>
                        <textarea name="notes" rows="2" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5 px-2.5">{{ old('notes') }}</textarea>
                        @error('notes')<p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-300">
                        <a href="{{ route('suppliers.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition">
                            Hủy bỏ
                        </a>
                        <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white text-xs uppercase font-bold rounded-none hover:bg-blue-700 transition">
                            Lưu lại
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>