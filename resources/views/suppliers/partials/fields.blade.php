<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="mb-1 block text-xs font-bold uppercase text-slate-700">Mã NCC <span class="text-rose-600">*</span></label>
        <input type="text" name="code" value="{{ old('code', $supplier?->code) }}" required class="w-full border-slate-300 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-0">
        @error('code')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-xs font-bold uppercase text-slate-700">Tên NCC <span class="text-rose-600">*</span></label>
        <input type="text" name="name" value="{{ old('name', $supplier?->name) }}" required class="w-full border-slate-300 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-0">
        @error('name')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-xs font-bold uppercase text-slate-700">Điện thoại</label>
        <input type="text" name="phone" value="{{ old('phone', $supplier?->phone) }}" class="w-full border-slate-300 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-0">
        @error('phone')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-xs font-bold uppercase text-slate-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $supplier?->email) }}" class="w-full border-slate-300 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-0">
        @error('email')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label class="mb-1 block text-xs font-bold uppercase text-slate-700">Địa chỉ</label>
        <textarea name="address" rows="2" class="w-full border-slate-300 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-0">{{ old('address', $supplier?->address) }}</textarea>
        @error('address')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-xs font-bold uppercase text-slate-700">Người liên hệ</label>
        <input type="text" name="contact_person" value="{{ old('contact_person', $supplier?->contact_person) }}" class="w-full border-slate-300 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-0">
        @error('contact_person')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="mb-1 block text-xs font-bold uppercase text-slate-700">Ghi chú</label>
        <textarea name="notes" rows="2" class="w-full border-slate-300 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-0">{{ old('notes', $supplier?->notes) }}</textarea>
        @error('notes')<p class="mt-1 text-[11px] font-medium text-rose-600">{{ $message }}</p>@enderror
    </div>
</div>
