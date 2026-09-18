<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Thông tin nhân viên') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Bảng hiển thị thông tin chi tiết nhân viên -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-3xl">
                    <section>
                        <header class="flex justify-between items-center border-b pb-4 mb-6">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">
                                    Chi tiết hồ sơ nhân sự
                                </h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Thông tin định danh, phòng ban và chức vụ công tác tại nhà máy.
                                </p>
                            </div>
                        </header>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <span class="block text-sm font-medium text-gray-500">Họ và tên:</span>
                                <p class="mt-1 text-base font-semibold text-gray-900">{{ $user->name }}</p>
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-gray-500">Mã nhân viên:</span>
                                <p class="mt-1 text-base font-semibold text-gray-900">{{ $user->employee_code ?? 'Chưa cập nhật' }}</p>
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-gray-500">Địa chỉ Email:</span>
                                <p class="mt-1 text-base text-gray-900">{{ $user->email }}</p>
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-gray-500">Số điện thoại:</span>
                                <p class="mt-1 text-base text-gray-900">{{ $user->phone ?? 'Chưa cập nhật' }}</p>
                            </div>

                            <!-- Khối nổi bật cho Chức vụ và Phòng ban -->
                            <div class="md:col-span-2 bg-gray-50 p-4 rounded-lg border border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-500">Phòng ban công tác:</span>
                                    <p class="mt-1 text-base font-semibold text-gray-900">
                                        {{ $user->department ? $user->department->name : ($user->department_id ? 'Phòng Ban #' . $user->department_id : 'Chưa gán phòng ban') }}
                                    </p>
                                </div>

                                <div>
                                    <span class="block text-xs font-bold uppercase tracking-wider text-gray-500">Chức vụ hiện tại:</span>
                                    <p class="mt-1 text-base font-bold text-indigo-600">
                                        {{ $user->position ?? 'Chưa cập nhật' }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-gray-500">Trạng thái làm việc:</span>
                                <p class="mt-1 text-base">
                                    @if($user->status == 'working' || !$user->status)
                                        <span class="px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">Đang làm việc</span>
                                    @else
                                        <span class="px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 rounded-full">Nghỉ việc</span>
                                    @endif
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <span class="block text-sm font-medium text-gray-500">Địa chỉ liên hệ:</span>
                                <p class="mt-1 text-base text-gray-900">{{ $user->address ?? 'Chưa cập nhật' }}</p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Liên kết sang trang Đổi mật khẩu -->
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Bảo mật tài khoản</h3>
                    <p class="text-sm text-gray-600">Quản lý mật khẩu đăng nhập hệ thống ERP của bạn.</p>
                </div>
                <a href="{{ route('password.edit') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Đổi mật khẩu
                </a>
            </div>

        </div>
    </div>
</x-app-layout>