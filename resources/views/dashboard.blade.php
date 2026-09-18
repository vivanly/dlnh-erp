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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border-l-4 border-blue-500 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Tổng số Phòng ban</div>
                            <div class="text-3xl font-extrabold text-gray-800 mt-2">{{ $totalDepartments ?? 0 }}</div>
                        </div>
                        <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                            <!-- Icon Tòa nhà / Phòng ban -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Thẻ Nhân sự -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border-l-4 border-green-500 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Tổng số Nhân sự</div>
                            <div class="text-3xl font-extrabold text-gray-800 mt-2">{{ $totalUsers ?? 0 }}</div>
                        </div>
                        <div class="p-3 bg-green-50 text-green-600 rounded-lg">
                            <!-- Icon Nhân sự -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Thẻ Đang làm việc -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl p-6 border-l-4 border-indigo-500 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-gray-400 text-xs font-semibold uppercase tracking-wider">Nhân sự Đang làm việc</div>
                            <div class="text-3xl font-extrabold text-gray-800 mt-2">{{ $workingUsers ?? 0 }}</div>
                            <div class="mt-1 text-xs text-emerald-600 flex items-center gap-1 font-medium">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Đã cập nhật trạng thái
                            </div>
                        </div>
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-lg">
                            <!-- Icon Hoạt động / Trạng thái -->
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>