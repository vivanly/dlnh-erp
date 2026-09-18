<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Tổng quan Hệ thống ERP Nhà máy') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Các thẻ thống kê (Widgets) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Thẻ Phòng ban -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-blue-500">
                    <div class="text-gray-500 text-sm font-medium uppercase">Tổng số Phòng ban</div>
                    <div class="text-3xl font-bold text-gray-800 mt-2">{{ $totalDepartments ?? 0 }}</div>
                </div>

                <!-- Thẻ Nhân sự -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-green-500">
                    <div class="text-gray-500 text-sm font-medium uppercase">Tổng số Nhân sự</div>
                    <div class="text-3xl font-bold text-gray-800 mt-2">{{ $totalUsers ?? 0 }}</div>
                </div>

                <!-- Thẻ Đang làm việc -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 border-l-4 border-indigo-500">
                    <div class="text-gray-500 text-sm font-medium uppercase">Nhân sự Đang làm việc</div>
                    <div class="text-3xl font-bold text-gray-800 mt-2">{{ $workingUsers ?? 0 }}</div>
                    <div class="mt-4 text-sm text-gray-500">Đã cập nhật trạng thái</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>