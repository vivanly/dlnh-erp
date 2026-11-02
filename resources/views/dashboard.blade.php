<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="dashboard-header-title mt-0.5 text-lg tracking-tight text-slate-950">Tổng quan</h1>
            </div>
            <span class="rounded border border-blue-200 bg-blue-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-wider text-blue-800">Điều hành doanh nghiệp</span>
        </div>
    </x-slot>

    <div class="erp-page space-y-4">
        <section class="dashboard-welcome-panel relative isolate overflow-hidden rounded-lg border border-blue-950 bg-gradient-to-r from-slate-950 via-blue-950 to-blue-800 px-4 py-4 text-white shadow-lg sm:px-6 sm:py-5" aria-labelledby="dashboard-welcome">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    @if(auth()->user()->avatar_path)
                        <img src="{{ route('profile.avatar') }}" alt="Ảnh đại diện của {{ auth()->user()->name }}" class="erp-user-avatar dashboard-welcome-avatar h-11 w-11 shrink-0 rounded-full border-2 border-white/40 object-cover sm:h-[52px] sm:w-[52px]">
                    @else
                        <span role="img" class="erp-user-avatar dashboard-welcome-avatar flex h-11 w-11 shrink-0 items-center justify-center rounded-full border-2 border-white/40 bg-white/10 text-lg font-bold sm:h-[52px] sm:w-[52px]">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    @endif
                    <div class="min-w-0">
                        <p class="dashboard-welcome-greeting text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-200">{{ now()->hour < 12 ? 'Chào buổi sáng' : (now()->hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối') }}</p>
                        <h2 id="dashboard-welcome" class="dashboard-welcome-name mt-0.5 truncate text-lg font-bold tracking-tight sm:text-xl">{{ auth()->user()->name }}</h2>
                        <p class="dashboard-welcome-role mt-0.5 truncate text-xs text-blue-100/85">{{ auth()->user()->position ?? 'Nhân viên' }}{{ auth()->user()->department ? ' · '.auth()->user()->department->name : '' }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <span class="dashboard-welcome-clock inline-flex items-center gap-2 rounded border border-white/20 bg-white/10 px-3 py-2 text-xs font-medium text-blue-50" data-live-clock data-timezone="{{ config('app.timezone') }}" aria-live="off">
                        <svg class="h-4 w-4 shrink-0 text-blue-200" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 012 2v14H3V6a2 2 0 012-2z" /></svg>
                        <span data-live-clock-text>{{ now()->format('d/m/Y H:i:s') }}</span>
                    </span>
                    <a href="{{ route('profile.edit') }}" class="dashboard-welcome-profile inline-flex items-center gap-2 rounded border border-white/30 bg-white px-3 py-2 text-xs font-bold text-blue-950 transition hover:bg-blue-50">Hồ sơ cá nhân</a>
                </div>
            </div>
        </section>

        <section aria-labelledby="company-overview">
            <div class="mb-2 flex flex-wrap items-center justify-between gap-x-3 gap-y-1 px-3">
                <div class="flex flex-wrap items-center gap-x-3">
                    <h2 id="company-overview" class="text-sm font-bold text-slate-900">Tổng quan nhân sự</h2>
                    <p class="dashboard-overview-caption text-[11px] text-slate-500">Quy mô tổ chức và tình hình làm việc</p>
                </div>
                <span class="dashboard-updated text-[9px] font-semibold uppercase tracking-wider text-slate-500">Cập nhật {{ now()->format('d/m/Y') }}</span>
            </div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <article class="erp-panel dashboard-stat-card flex items-center justify-between gap-3 border-l-[3px] border-l-blue-600 px-3 py-2">
                    <div class="flex min-w-0 items-baseline gap-3"><p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500">Phòng ban</p><p class="text-xl font-bold leading-none text-blue-800">{{ number_format($totalDepartments) }}</p></div>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9v.01M9 12v.01M9 15v.01M9 18v.01" /></svg></span>
                </article>
                <article class="erp-panel dashboard-stat-card flex items-center justify-between gap-3 border-l-[3px] border-l-indigo-600 px-3 py-2">
                    <div class="flex min-w-0 items-baseline gap-3"><p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500">Tổng nhân sự</p><p class="text-xl font-bold leading-none text-indigo-800">{{ number_format($totalUsers) }}</p></div>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-indigo-100 bg-indigo-50 text-indigo-700"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2m16 0v-2a4 4 0 00-3-3.87M10 11a4 4 0 100-8 4 4 0 000 8zm10-4a4 4 0 11-8 0 4 4 0 018 0z" /></svg></span>
                </article>
                <article class="erp-panel dashboard-stat-card flex items-center justify-between gap-3 border-l-[3px] border-l-emerald-600 px-3 py-2">
                    <div class="flex min-w-0 items-baseline gap-3"><p class="truncate text-[10px] font-bold uppercase tracking-wider text-slate-500">Đang làm việc</p><p class="text-xl font-bold leading-none text-emerald-800">{{ number_format($workingUsers) }}</p></div>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-emerald-100 bg-emerald-50 text-emerald-700"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5-1a8 8 0 11-16 0 8 8 0 0116 0z" /></svg></span>
                </article>
            </div>
        </section>

        @if($showDirectorAnalytics)
            <section class="erp-panel p-3 sm:p-4" aria-labelledby="director-order-mix">
                <div class="flex flex-col gap-3 border-b border-slate-200 pb-3 lg:flex-row lg:items-end lg:justify-between">
                    <div><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-blue-700">Ban Giám đốc</p><h2 id="director-order-mix" class="mt-1 text-sm font-bold text-slate-900">Số đơn mua và đơn bán theo thời gian</h2><p class="mt-1 text-xs text-slate-500">Số đơn tạo trong kỳ · {{ $analyticsStart->format('d/m/Y') }} – {{ $analyticsEnd->format('d/m/Y') }}</p></div>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-2">
                        <label class="text-xs font-semibold text-slate-600">Kỳ xem<select name="period" class="mt-1 block rounded border-slate-300 py-1.5 text-xs" onchange="this.form.submit()"><option value="month" @selected($analyticsPeriod === 'month')>Theo tháng</option><option value="quarter" @selected($analyticsPeriod === 'quarter')>Theo quý</option><option value="year" @selected($analyticsPeriod === 'year')>Theo năm</option></select></label>
                        <label class="text-xs font-semibold text-slate-600">Năm<select name="year" class="mt-1 block rounded border-slate-300 py-1.5 text-xs">@for($year = now()->year; $year >= now()->year - 5; $year--)<option value="{{ $year }}" @selected($analyticsYear === $year)>{{ $year }}</option>@endfor</select></label>
                        @if($analyticsPeriod === 'month')<label class="text-xs font-semibold text-slate-600">Tháng<select name="month" class="mt-1 block rounded border-slate-300 py-1.5 text-xs">@for($month = 1; $month <= 12; $month++)<option value="{{ $month }}" @selected($analyticsMonth === $month)>Tháng {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}</option>@endfor</select></label>@elseif($analyticsPeriod === 'quarter')<label class="text-xs font-semibold text-slate-600">Quý<select name="quarter" class="mt-1 block rounded border-slate-300 py-1.5 text-xs">@for($quarter = 1; $quarter <= 4; $quarter++)<option value="{{ $quarter }}" @selected($analyticsQuarter === $quarter)>Quý {{ $quarter }}</option>@endfor</select></label>@endif
                        <button type="submit" class="inline-flex h-[34px] items-center rounded border border-blue-700 bg-blue-700 px-3 text-xs font-bold text-white hover:bg-blue-800">Xem báo cáo</button>
                    </form>
                </div>
                <div class="grid gap-3 pt-3 md:grid-cols-2">
                    <section class="border border-blue-100 bg-white p-3" aria-labelledby="purchase-order-chart">
                        <div class="flex items-baseline justify-between gap-2"><h3 id="purchase-order-chart" class="text-sm font-bold text-slate-800">Đơn mua</h3><span class="text-lg font-bold text-blue-800">{{ number_format($purchaseOrderCount) }}</span></div>
                        <p class="mt-0.5 text-[11px] text-slate-500">Theo ngày dự kiến nhận hàng trong kỳ đã chọn</p>

                        <div class="mt-3 overflow-x-auto">
                            <div class="flex h-28 min-w-max items-end gap-1 border-b border-slate-200 px-1">
                                @foreach($purchaseOrderTrend as $point)
                                    <div class="flex h-full w-9 shrink-0 items-end justify-center" title="{{ $point['label'] }}: {{ $point['count'] }} đơn ({{ $purchaseOrderCount > 0 ? number_format($point['count'] * 100 / $purchaseOrderCount, 1) : 0 }}%)"><div class="w-5 bg-blue-600" style="height: {{ $purchaseOrderCount > 0 ? max(1, 112 * $point['count'] / $purchaseOrderCount) : 0 }}px"></div></div>
                                @endforeach
                            </div>
                            <div class="mt-1 flex min-w-max gap-1 px-1 text-center text-[9px] text-slate-500">
                                @foreach($purchaseOrderTrend as $point)<span class="w-9 shrink-0">{{ $point['label'] }}<small class="block">{{ $purchaseOrderCount > 0 ? number_format($point['count'] * 100 / $purchaseOrderCount, 1) : 0 }}%</small></span>@endforeach
                            </div>
                        </div>
                    </section>
                    <section class="border border-emerald-100 bg-white p-3" aria-labelledby="sales-order-chart">
                        <div class="flex items-baseline justify-between gap-2"><h3 id="sales-order-chart" class="text-sm font-bold text-slate-800">Đơn bán</h3><span class="text-lg font-bold text-emerald-800">{{ number_format($salesOrderCount) }}</span></div>
                        <p class="mt-0.5 text-[11px] text-slate-500">Theo ngày hẹn giao khách trong kỳ đã chọn</p>

                        <div class="mt-3 overflow-x-auto">
                            <div class="flex h-28 min-w-max items-end gap-1 border-b border-slate-200 px-1">
                                @foreach($salesOrderTrend as $point)
                                    <div class="flex h-full w-9 shrink-0 items-end justify-center" title="{{ $point['label'] }}: {{ $point['count'] }} đơn ({{ $salesOrderCount > 0 ? number_format($point['count'] * 100 / $salesOrderCount, 1) : 0 }}%)"><div class="w-5 bg-emerald-600" style="height: {{ $salesOrderCount > 0 ? max(1, 112 * $point['count'] / $salesOrderCount) : 0 }}px"></div></div>
                                @endforeach
                            </div>
                            <div class="mt-1 flex min-w-max gap-1 px-1 text-center text-[9px] text-slate-500">
                                @foreach($salesOrderTrend as $point)<span class="w-9 shrink-0">{{ $point['label'] }}<small class="block">{{ $salesOrderCount > 0 ? number_format($point['count'] * 100 / $salesOrderCount, 1) : 0 }}%</small></span>@endforeach
                            </div>
                        </div>
                    </section>
                </div>
            </section>
                            @endif

        <section aria-labelledby="dashboard-shortcuts">
            <div class="mb-2"><h2 id="dashboard-shortcuts" class="text-sm font-bold text-slate-900">Lối tắt phân hệ</h2><p class="mt-0.5 text-[11px] text-slate-500">Truy cập nhanh theo quyền của bạn</p></div>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @if($isIT || $isSales)
                    @foreach([['suppliers.index','Nhà cung cấp','Danh bạ đối tác cung ứng'],['purchase-orders.index','Đơn mua hàng','Theo dõi đơn đặt hàng'],['customers.index','Khách hàng','Danh bạ khách hàng'],['orders.index','Đơn bán hàng','Theo dõi đơn bán']] as [$route,$title,$description])
                        <a href="{{ route($route) }}" class="erp-panel group flex items-center gap-3 p-3 transition hover:border-blue-300 hover:shadow-sm"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v14H4zM8 3h8v4H8zm0 9h8m-8 4h5" /></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-slate-800 group-hover:text-blue-800">{{ $title }}</span><span class="mt-0.5 block text-[10px] text-slate-500">{{ $description }}</span></span><span class="text-slate-400 group-hover:text-blue-700">›</span></a>
                    @endforeach
                @endif

                @if($isIT || $isPlanning)
                    @foreach([['production-planning.index','Kế hoạch ngày','Lập kế hoạch theo đơn bán'],['production-monthly-plans.index','Kế hoạch tháng','Lập và theo dõi kế hoạch tháng'],['production-planning.history','Xem các kế hoạch đã ra','Tra cứu lịch sử kế hoạch']] as [$route,$title,$description])
                        <a href="{{ route($route) }}" class="erp-panel group flex items-center gap-3 p-3 transition hover:border-blue-300 hover:shadow-sm"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v15H4zM8 2v6m8-6v6M8 12h8m-8 4h5" /></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-slate-800 group-hover:text-blue-800">{{ $title }}</span><span class="mt-0.5 block text-[10px] text-slate-500">{{ $description }}</span></span><span class="text-slate-400 group-hover:text-blue-700">›</span></a>
                    @endforeach
                @endif

                @if($isIT || $isProduction || $isWarehouse)
                    @foreach([['production-orders.index','Lệnh sản xuất','Theo dõi tiến độ sản xuất'],['production.packaging.index','Xác nhận đóng gói','Xác nhận hoàn tất đóng gói']] as [$route,$title,$description])
                        <a href="{{ route($route) }}" class="erp-panel group flex items-center gap-3 p-3 transition hover:border-blue-300 hover:shadow-sm"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7l8-4 8 4-8 4-8-4zm0 5l8 4 8-4m-16 5l8 4 8-4" /></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-slate-800 group-hover:text-blue-800">{{ $title }}</span><span class="mt-0.5 block text-[10px] text-slate-500">{{ $description }}</span></span><span class="text-slate-400 group-hover:text-blue-700">›</span></a>
                    @endforeach
                @endif

                @if($isIT || $isQA || $isQC)
                    @foreach([['qa.internal-lots.index','Lô nội bộ','Quản lý số lô thành phẩm'],['qa.order-batches','Đơn hàng chờ cập nhật số lô','Cập nhật lô cho đơn hàng'],['products.index','Danh mục sản phẩm','Tra cứu sản phẩm'],['material-lots.index','Hồ sơ COA','Hồ sơ COA lô nguyên liệu']] as [$route,$title,$description])
                        <a href="{{ $route === 'material-lots.index' ? route($route, ['type' => 'raw_material']) : route($route) }}" class="erp-panel group flex items-center gap-3 p-3 transition hover:border-blue-300 hover:shadow-sm"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v15H4zM8 9h8m-8 4h8m-8 4h4" /></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-slate-800 group-hover:text-blue-800">{{ $title }}</span><span class="mt-0.5 block text-[10px] text-slate-500">{{ $description }}</span></span><span class="text-slate-400 group-hover:text-blue-700">›</span></a>
                    @endforeach
                    <a href="{{ route('products.index', ['classification' => 'Vị Thuốc Cổ Truyền']) }}" class="erp-panel group flex items-center gap-3 p-3 transition hover:border-blue-300 hover:shadow-sm"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-2.2 3-5 5.4-5 9a5 5 0 0010 0c0-3.6-2.8-6-5-9zm-2 10a2 2 0 002 2" /></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-slate-800 group-hover:text-blue-800">Vị thuốc cổ truyền</span><span class="mt-0.5 block text-[10px] text-slate-500">Danh mục sản phẩm vị thuốc cổ truyền</span></span><span class="text-slate-400 group-hover:text-blue-700">›</span></a>
                @endif

                @if($isIT || $isWarehouse || $isQC)
                    @foreach([['warehouse.purchase-orders','Nhận đơn mua hàng','Tiếp nhận hàng từ nhà cung cấp'],['warehouse.production-batches','Nhập thành phẩm','Nhận thành phẩm hoàn tất'],['supplier-returns.index','Trả hàng nhà cung cấp','Theo dõi hàng cần trả'],['warehouse.production-issues','Xuất nguyên liệu sản xuất','Cấp nguyên liệu theo lệnh'],['warehouse.material-issues','Xuất dược liệu theo đơn','Chuẩn bị hàng theo đơn bán'],['warehouse.sales-orders','Đóng gói và xuất hàng','Xử lý đơn chờ giao'],['warehouse.delivered-sales-orders','Đơn hàng đã giao','Tra cứu đơn đã xuất kho'],['warehouse.stock','Tồn kho thành phẩm','Theo dõi tồn kho thành phẩm'],['warehouse.material-stock','Tồn dược liệu và phụ liệu','Theo dõi số lượng và biến động tồn'],['warehouse.sales-order-stock-checks','Kiểm tra tồn đơn bán','Đối chiếu khả năng đáp ứng']] as [$route,$title,$description])
                        <a href="{{ route($route) }}" class="erp-panel group flex items-center gap-3 p-3 transition hover:border-blue-300 hover:shadow-sm"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7l9-4 9 4-9 4-9-4zm0 5l9 4 9-4m-18 5l9 4 9-4" /></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-slate-800 group-hover:text-blue-800">{{ $title }}</span><span class="mt-0.5 block text-[10px] text-slate-500">{{ $description }}</span></span><span class="text-slate-400 group-hover:text-blue-700">›</span></a>
                    @endforeach
                @endif

                @if(auth()->user()->canPrintLabels())
                    <a href="{{ route('labels.index') }}" class="erp-panel group flex items-center gap-3 p-3 transition hover:border-blue-300 hover:shadow-sm"><span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-blue-100 bg-blue-50 text-blue-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12-4h12v7H6v-7z" /></svg></span><span class="min-w-0 flex-1"><span class="block text-xs font-bold text-slate-800 group-hover:text-blue-800">In nhãn</span><span class="mt-0.5 block text-[10px] text-slate-500">In và theo dõi nhãn đơn hàng</span></span><span class="text-slate-400 group-hover:text-blue-700">›</span></a>
                @endif
            </div>
        </section>
    </div>

    <script>
        (() => {
            const clock = document.querySelector('[data-live-clock]');
            const clockText = clock?.querySelector('[data-live-clock-text]');
            if (!clock || !clockText) return;
            const formatter = new Intl.DateTimeFormat('en-GB', {timeZone: clock.dataset.timezone || 'Asia/Bangkok', day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23'});
            const update = () => {
                const parts = Object.fromEntries(formatter.formatToParts(new Date()).map(({type, value}) => [type, value]));
                clockText.textContent = `${parts.day}/${parts.month}/${parts.year} ${parts.hour}:${parts.minute}:${parts.second}`;
            };
            update();
            window.setInterval(update, 1000);
        })();
    </script>
</x-app-layout>
