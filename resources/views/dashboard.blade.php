<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Dược Liệu Ninh Hiệp · ERP</p>
            <h1 class="mt-0.5 text-lg font-bold tracking-tight text-slate-900">Tổng quan</h1>
        </div>
    </x-slot>

    <div class="erp-page space-y-5">
        <section class="erp-panel flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                @if(auth()->user()->avatar_path)
                    <img src="{{ route('profile.avatar') }}" alt="Ảnh đại diện của {{ auth()->user()->name }}" class="h-12 w-12 shrink-0 rounded-full border border-slate-200 object-cover sm:h-14 sm:w-14">
                @else
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-700 text-lg font-bold text-white shadow-sm sm:h-14 sm:w-14">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">{{ now()->hour < 12 ? 'Chào buổi sáng' : (now()->hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối') }}</p>
                    <h2 class="mt-0.5 truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ auth()->user()->name }}</h2>
                    <p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->position ?? 'Nhân viên' }}{{ auth()->user()->department ? ' · '.auth()->user()->department->name : '' }}</p>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-md border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Hồ sơ cá nhân
            </a>
        </section>

        <section aria-labelledby="company-overview">
            <div class="mb-3">
                <h2 id="company-overview" class="text-sm font-bold text-slate-900">Tổng quan doanh nghiệp</h2>
                <p class="mt-0.5 text-xs text-slate-500">Thông tin tổ chức và nhân sự</p>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <article class="erp-kpi" style="--erp-kpi-color:#2563eb">
                    <p class="text-xs font-semibold text-slate-500">Phòng ban</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($totalDepartments) }}</p>
                </article>
                <article class="erp-kpi" style="--erp-kpi-color:#7c3aed">
                    <p class="text-xs font-semibold text-slate-500">Tổng nhân sự</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($totalUsers) }}</p>
                </article>
                <article class="erp-kpi" style="--erp-kpi-color:#059669">
                    <p class="text-xs font-semibold text-slate-500">Nhân sự đang làm việc</p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($workingUsers) }}</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="dashboard-shortcuts">
            <div class="mb-3">
                <h2 id="dashboard-shortcuts" class="text-sm font-bold text-slate-900">Lối tắt phân hệ</h2>
                <p class="mt-0.5 text-xs text-slate-500">Truy cập nhanh các công việc bạn được phân quyền</p>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @if($isIT || $isSales)
                    <a href="{{ route('orders.index') }}" class="erp-panel group flex items-center gap-3 p-4 transition hover:border-blue-200 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700">ĐB</span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-blue-700">Đơn bán hàng</span>
                            <span class="mt-1 block text-xs text-slate-500">Khách hàng và tiến độ đơn</span>
                        </span>
                    </a>
                @endif
                @if($isIT || $isWarehouse)
                    <a href="{{ route('warehouse.stock') }}" class="erp-panel group flex items-center gap-3 p-4 transition hover:border-emerald-200 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-sm font-bold text-emerald-700">K</span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-emerald-700">Quản lý tồn kho</span>
                            <span class="mt-1 block text-xs text-slate-500">Tồn khả dụng và hạn dùng</span>
                        </span>
                    </a>
                @endif
                @if($isIT)
                    <a href="{{ route('boms.plan') }}" class="erp-panel group flex items-center gap-3 p-4 transition hover:border-violet-200 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-sm font-bold text-violet-700">KH</span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-violet-700">Kế hoạch nguyên liệu</span>
                            <span class="mt-1 block text-xs text-slate-500">Định mức BOM và nhu cầu</span>
                        </span>
                    </a>
                @endif
                @if($isIT || $isProduction || $isWarehouse)
                    <a href="{{ route('production-orders.index') }}" class="erp-panel group flex items-center gap-3 p-4 transition hover:border-amber-200 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-sm font-bold text-amber-700">SX</span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-amber-700">Lệnh sản xuất</span>
                            <span class="mt-1 block text-xs text-slate-500">Theo dõi tiến độ sản xuất</span>
                        </span>
                    </a>
                @endif
                @if($isIT || $isQA)
                    <a href="{{ route('qa.order-batches') }}" class="erp-panel group flex items-center gap-3 p-4 transition hover:border-rose-200 hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-sm font-bold text-rose-700">QA</span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800 group-hover:text-rose-700">Hàng đợi QA</span>
                            <span class="mt-1 block text-xs text-slate-500">Lô hàng và hồ sơ chất lượng</span>
                        </span>
                    </a>
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
