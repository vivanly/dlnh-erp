<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm text-slate-800 uppercase tracking-wide">
                {{ __('Thêm Khách Hàng Mới') }}
            </h2>
            <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 border border-slate-700 rounded-none font-bold text-xs text-white uppercase tracking-wider hover:bg-slate-700 transition shadow-none">
                Quay lại
            </a>
        </div>
    </x-slot>

    <div class="py-2">
        <div class="max-w-3xl mx-auto px-2">
            
            @if ($errors->any())
                <div class="mb-3 p-3 bg-rose-50 border-l-4 border-rose-600 text-rose-900 text-xs rounded-none">
                    <p class="font-bold mb-1">Vui lòng kiểm tra lại các lỗi sau:</p>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-slate-300 rounded-none shadow-none p-4">
                <form action="{{ route('customers.store') }}" method="POST" class="space-y-4">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Mã Khách Hàng -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Mã Khách Hàng <span class="text-rose-600">*</span></label>
                            <input type="text" name="code" value="{{ old('code') }}" required class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="VD: KH001">
                        </div>

                        <!-- Tên Khách Hàng -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tên Khách Hàng / Đơn Vị <span class="text-rose-600">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="VD: Nhà thuốc GPP An Tâm">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Phân Loại (Đã đổi thành input text) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Phân Loại <span class="text-rose-600">*</span></label>
                            <input type="text" name="type" value="{{ old('type') }}" required class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="VD: Lẻ, Thầu, Cửa Hàng, Bệnh Viện...">
                        </div>

                        <!-- Giấy Phép Kinh Doanh -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Giấy Phép Kinh Doanh</label>
                            <input type="text" name="license_number" value="{{ old('license_number') }}" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="Số GPKD / GPP">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Số Điện Thoại -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Số Điện Thoại</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="Số điện thoại liên hệ">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="email@domain.com">
                        </div>
                    </div>

                    <!-- Địa Chỉ -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Địa Chỉ</label>
                        <textarea name="address" rows="2" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="Địa chỉ cơ sở / nhà thuốc">{{ old('address') }}</textarea>
                    </div>

                    <!-- Ghi Chú -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Ghi Chú</label>
                        <textarea name="note" rows="2" class="w-full text-xs border-slate-300 rounded-none focus:border-blue-600 focus:ring-0 shadow-none py-1.5" placeholder="Ghi chú thêm...">{{ old('note') }}</textarea>
                    </div>

                    <!-- Nút hành động -->
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                        <a href="{{ route('customers.index') }}" class="px-3 py-1.5 bg-slate-200 text-slate-700 text-xs uppercase font-bold rounded-none hover:bg-slate-300 transition">
                            Hủy bỏ
                        </a>
                        <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white text-xs uppercase font-bold rounded-none hover:bg-blue-700 transition">
                            Lưu khách hàng
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>