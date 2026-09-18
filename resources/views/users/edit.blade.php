<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Chỉnh sửa thông tin Nhân viên') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Hàng 1: Họ tên & Email -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Họ và tên:</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Email đăng nhập:</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Hàng 2: Mật khẩu & Mã nhân viên -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Mật khẩu mới (Bỏ trống nếu không đổi):</label>
                            <input type="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Mã nhân viên:</label>
                            <input type="text" name="employee_code" value="{{ old('employee_code', $user->employee_code) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            @error('employee_code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Hàng 3: Phòng ban & Chức vụ -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Phòng ban công tác:</label>
                            <select name="department_id" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Chọn phòng ban --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Chức vụ:</label>
                            <select name="position" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">-- Chọn chức vụ --</option>
                                <option value="Nhân viên sản xuất" {{ old('position', $user->position) == 'Nhân viên sản xuất' ? 'selected' : '' }}>Nhân viên sản xuất</option>
                                <option value="Nhân viên văn phòng" {{ old('position', $user->position) == 'Nhân viên văn phòng' ? 'selected' : '' }}>Nhân viên văn phòng</option>
                                <option value="Phó phòng" {{ old('position', $user->position) == 'Phó phòng' ? 'selected' : '' }}>Phó phòng</option>
                                <option value="Trưởng phòng" {{ old('position', $user->position) == 'Trưởng phòng' ? 'selected' : '' }}>Trưởng phòng</option>
                                <option value="Phó giám đốc" {{ old('position', $user->position) == 'Phó giám đốc' ? 'selected' : '' }}>Phó giám đốc</option>
                                <option value="Giám đốc" {{ old('position', $user->position) == 'Giám đốc' ? 'selected' : '' }}>Giám đốc</option>
                                <option value="Tổng giám đốc" {{ old('position', $user->position) == 'Tổng giám đốc' ? 'selected' : '' }}>Tổng giám đốc</option>
                            </select>
                            @error('position') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Hàng 4: Số điện thoại & Trạng thái làm việc -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Số điện thoại:</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Trạng thái làm việc:</label>
                            <select name="status" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="working" {{ old('status', $user->status) == 'working' ? 'selected' : '' }}>Đang làm việc</option>
                                <option value="resigned" {{ old('status', $user->status) == 'resigned' ? 'selected' : '' }}>Nghỉ việc</option>
                            </select>
                            @error('status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Địa chỉ liên hệ -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Địa chỉ liên hệ:</label>
                        <textarea name="address" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="2">{{ old('address', $user->address) }}</textarea>
                        @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- Nút thao tác -->
                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Cập nhật nhân viên
                        </button>
                        <a href="{{ route('users.index') }}" class="text-gray-600 hover:text-gray-800">Quay lại danh sách</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>