<!-- Danh sách các nút menu dọc -->
<div class="erp-sidebar-navigation px-3 py-4">
    <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Phân hệ</p>
    @php($currentUser = auth()->user())
    @php($canSeePurchaseApprovals = $currentUser && ((method_exists($currentUser, 'isSalesDirector') && $currentUser->isSalesDirector()) || (method_exists($currentUser, 'isGeneralDirector') && $currentUser->isGeneralDirector()) || (method_exists($currentUser, 'isITDepartment') && $currentUser->isITDepartment())))
    @php($canSeeSalesApprovals = $currentUser && ((method_exists($currentUser, 'isSalesDirector') && $currentUser->isSalesDirector()) || (method_exists($currentUser, 'isBiddingDirector') && $currentUser->isBiddingDirector()) || (method_exists($currentUser, 'isGeneralDirector') && $currentUser->isGeneralDirector()) || (method_exists($currentUser, 'isITDepartment') && $currentUser->isITDepartment())))
    @php($canSeePlanApprovals = $currentUser && ((method_exists($currentUser, 'isGeneralDirector') && $currentUser->isGeneralDirector()) || ($currentUser->position ?? '') === 'Giám Đốc' || in_array($currentUser->role ?? '', ['director', 'sales_director', 'general_director'], true)))
    @php($isITUser = $currentUser && method_exists($currentUser, 'isITDepartment') && $currentUser->isITDepartment())
    @php($canViewMonthlyPlans = $currentUser && ((method_exists($currentUser, 'isPlanningDepartment') && $currentUser->isPlanningDepartment()) || (method_exists($currentUser, 'isITDepartment') && $currentUser->isITDepartment())))
    @php($isPurchaseApprovalQueue = request()->routeIs('purchase-orders.index') && request('status') === 'pending')
    @php($isProductionApprovalQueue = request()->routeIs('production-planning.approvals'))
    @php($isMonthlyPlanApprovalQueue = request()->routeIs('production-monthly-plans.approvals'))
    @php($isBomApprovalQueue = request()->routeIs('boms.index'))
    @php($isPlanningSectionActive = ((request()->routeIs('production-planning*') && !$isProductionApprovalQueue) || request()->routeIs('production-monthly-plans*') || ($isITUser && request()->routeIs('boms*'))) && !($isBomApprovalQueue && $isITUser))

    <!-- Tổng quan -->
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-md text-xs font-medium transition border {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-semibold border-blue-200 shadow-2xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100 hover:text-slate-900' }}">
        <svg class="w-4 h-4 text-slate-500 {{ request()->routeIs('dashboard') ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
        Tổng quan
    </a>

    @if($currentUser)
        <details class="group/menu rounded-md border {{ request()->routeIs('sales-order-approvals*') || $isPurchaseApprovalQueue || $isProductionApprovalQueue || $isMonthlyPlanApprovalQueue || ($isBomApprovalQueue && $isITUser) ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs('sales-order-approvals*') || $isPurchaseApprovalQueue || $isProductionApprovalQueue || $isMonthlyPlanApprovalQueue || ($isBomApprovalQueue && $isITUser) ? 'open' : '' }}>
            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-500 {{ request()->routeIs('sales-order-approvals*') || $isPurchaseApprovalQueue || $isProductionApprovalQueue || ($isBomApprovalQueue && $isITUser) ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5-5v5c0 5-3.4 9.7-8 11-4.6-1.3-8-6-8-11V5l8-3 8 3z"></path></svg>
                    <span class="{{ request()->routeIs('sales-order-approvals*') || $isPurchaseApprovalQueue || $isProductionApprovalQueue || ($isBomApprovalQueue && $isITUser) ? 'text-blue-700 font-semibold' : '' }}">Ban Giám Đốc</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </summary>
            <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Đơn hàng</div>
                <a href="{{ route('purchase-orders.index', ['status' => 'pending']) }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ $isPurchaseApprovalQueue ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Duyệt đơn mua</a>
                <a href="{{ route('sales-order-approvals.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('sales-order-approvals*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Duyệt đơn bán</a>
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Kế hoạch</div>
                <a href="{{ route('production-planning.approvals') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('production-planning.approvals') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Duyệt kế hoạch</a>
                <a href="{{ route('production-monthly-plans.approvals') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ $isMonthlyPlanApprovalQueue ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Duyệt kế hoạch tháng</a>
                @if($isITUser)
                    <a href="{{ route('boms.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ $isBomApprovalQueue ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Duyệt định mức BOM</a>
                @endif
            </div>
        </details>
    @endif

    <details class="group/menu rounded-md border {{ request()->routeIs(['suppliers*', 'customers*', 'orders*']) || (request()->routeIs('purchase-orders*') && !$isPurchaseApprovalQueue) ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs(['suppliers*', 'customers*', 'orders*']) || (request()->routeIs('purchase-orders*') && !$isPurchaseApprovalQueue) ? 'open' : '' }}>
        <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-slate-500 {{ request()->routeIs(['suppliers*', 'customers*', 'orders*']) || (request()->routeIs('purchase-orders*') && !$isPurchaseApprovalQueue) ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8h18l-1.5 12h-15L3 8zm3 0 1.5-4h9L18 8M8 12v4m4-4v4m4-4v4"></path></svg>
                <span class="{{ request()->routeIs(['suppliers*', 'customers*', 'orders*']) || (request()->routeIs('purchase-orders*') && !$isPurchaseApprovalQueue) ? 'text-blue-700 font-semibold' : '' }}">Kinh doanh</span>
            </div>
            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
            <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Mua hàng</div>
            <a href="{{ route('suppliers.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('suppliers*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Nhà cung cấp</a>
            <a href="{{ route('purchase-orders.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('purchase-orders*') && !$isPurchaseApprovalQueue ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Đơn mua hàng</a>
            <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Bán hàng</div>
            <a href="{{ route('customers.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('customers*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Khách hàng</a>
            <a href="{{ route('orders.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('orders*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Đơn bán hàng</a>
        </div>
    </details>

    @if($currentUser)
    <!-- Menu QA -->
        <details class="group/menu rounded-md border {{ request()->routeIs(['products*', 'product-regulatory-documents*', 'product-storage-methods*', 'qa.order-batches*', 'qa.coas*', 'material-lots*', 'qa.internal-lots*', 'qa.production-output-lots*', 'ppcb*', 'raw-materials*', 'accessories*']) ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs(['products*', 'product-regulatory-documents*', 'product-storage-methods*', 'qa.order-batches*', 'qa.coas*', 'material-lots*', 'qa.internal-lots*', 'qa.production-output-lots*', 'ppcb*', 'raw-materials*', 'accessories*']) ? 'open' : '' }}>
        <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
            <div class="flex items-center gap-2.5">
                <svg class="w-4 h-4 text-slate-500 {{ request()->routeIs(['products*', 'qa.order-batches*', 'qa.coas*', 'material-lots*', 'qa.internal-lots*', 'traceability*', 'ppcb*']) ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 01-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a6 6 0 01.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                <span class="{{ request()->routeIs(['products*', 'product-regulatory-documents*', 'product-storage-methods*', 'qa.order-batches*', 'qa.coas*', 'material-lots*', 'qa.internal-lots*', 'ppcb*']) ? 'text-blue-700 font-semibold' : '' }}">QA</span>
            </div>
            <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
        </summary>
        <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
            <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Danh mục</div>
            <a href="{{ route('products.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('products*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Sản phẩm</a>
            <a href="{{ route('raw-materials.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('raw-materials*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Nguyên liệu thô</a>
            <a href="{{ route('accessories.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('accessories*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Phụ liệu</a>
            <a href="{{ route('ppcb.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('ppcb*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Phương pháp chế biến (PPCB)</a>
            <a href="{{ route('product-storage-methods.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('product-storage-methods*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Phương pháp bảo quản</a>
            <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Lô & sản lượng</div>
            <a href="{{ route('qa.internal-lots.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('qa.internal-lots*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Quản lý lô nội bộ</a>
            <a href="{{ route('qa.production-output-lots.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('qa.production-output-lots*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Sản lượng hoàn thành</a>
            <a href="{{ route('qa.order-batches') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('qa.order-batches*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Cập nhật lô cho đơn hàng</a>
            <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Hồ sơ</div>
            <a href="{{ route('product-regulatory-documents.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('product-regulatory-documents*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">SCB/SĐK sản phẩm</a>
            <a href="{{ route('material-lots.index', ['type' => 'raw_material']) }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('material-lots*') && request('type', 'raw_material') === 'raw_material' ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Hồ sơ COA lô NCC</a>
        </div>
    </details>
    @endif

    @if($currentUser)
        <details class="group/menu rounded-md border {{ $isPlanningSectionActive ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ $isPlanningSectionActive ? 'open' : '' }}>
            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-500 {{ (request()->routeIs('production-planning*') && !$isProductionApprovalQueue) || request()->routeIs('production-orders*') || ($isITUser && request()->routeIs('boms*')) ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h17M8 15l4-4 3 3 5-6"></path></svg>
                    <span class="{{ (request()->routeIs('production-planning*') && !$isProductionApprovalQueue) || ($isITUser && request()->routeIs('boms*')) ? 'text-blue-700 font-semibold' : '' }}">Kế hoạch</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </summary>
            <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
                @if($isITUser)
                    <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Định mức</div>
                    <a href="{{ route('boms.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('boms.index') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Định mức sản xuất (BOM)</a>
                    <a href="{{ route('boms.plan') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('boms.plan') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Nhu cầu nguyên liệu</a>
                @endif
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Kế hoạch</div>
                <a href="{{ route('production-monthly-plans.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('production-monthly-plans.index', 'production-monthly-plans.edit') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Kế hoạch sản xuất tháng</a>
                <a href="{{ route('production-planning.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('production-planning.index') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Đơn chờ kế hoạch</a>
                <a href="{{ route('production-planning.history') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('production-planning.history') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Lịch sử kế hoạch</a>
            </div>
        </details>
    @endif

    @if($currentUser)
        <details class="group/menu rounded-md border {{ request()->routeIs(['production.packaging*', 'production-orders*', 'labels.*']) ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs(['production.packaging*', 'production-orders*', 'labels.*']) ? 'open' : '' }}>
            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
                <div class="flex items-center gap-2.5">
                    <svg class="h-4 w-4 {{ request()->routeIs(['production.packaging*', 'production-orders*', 'labels.*']) ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V9l5 3V9l5 3V5h4v16M8 17h1m3 0h1m3 0h1" /></svg>
                    <span class="{{ request()->routeIs(['production.packaging*', 'production-orders*', 'labels.*']) ? 'text-blue-700 font-semibold' : '' }}">Sản xuất</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </summary>
            <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Lệnh sản xuất</div>
                <a href="{{ route('production-orders.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('production-orders*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Lệnh sản xuất</a>
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Đóng gói & nhãn</div>
                <a href="{{ route('production.packaging.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('production.packaging*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Xác nhận đóng gói</a>
                <a href="{{ route('labels.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('labels.*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">In nhãn theo lô đơn hàng</a>
            </div>
        </details>
    @endif

    @if($currentUser)
        <details class="group/menu rounded-md border {{ request()->routeIs(['warehouse.*', 'production-orders*']) ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs(['warehouse.*', 'production-orders*']) ? 'open' : '' }}>
            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-500 {{ request()->routeIs(['warehouse.*', 'production-orders*']) ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18v13H3zM3 7l2-4h14l2 4M8 11v5m4-5v5m4-5v5"></path></svg>
                    <span class="{{ request()->routeIs(['warehouse.*', 'production-orders*']) ? 'text-blue-700 font-semibold' : '' }}">Kho</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </summary>
            <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Nhập kho</div>
                <a href="{{ route('warehouse.purchase-orders') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.purchase-orders*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Nhận đơn mua (NL thô / phụ liệu)</a>
                <a href="{{ route('warehouse.production-batches') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.production-batches*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Nhập thành phẩm</a>
                <a href="{{ route('supplier-returns.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('supplier-returns*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Trả hàng nhà cung cấp</a>
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Xuất kho</div>
                <a href="{{ route('warehouse.production-issues') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.production-issues*', 'production-orders*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Xuất NL cho lệnh sản xuất</a>
                <a href="{{ route('warehouse.material-issues') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.material-issues*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Xuất đơn bán nguyên liệu thô</a>
                <a href="{{ route('warehouse.sales-orders') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.sales-orders*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Đóng gói và xuất hàng</a>
                <a href="{{ route('warehouse.delivered-sales-orders') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.delivered-sales-orders*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Đơn đã giao</a>
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Tồn kho</div>
                <a href="{{ route('warehouse.stock') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.stock*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Tồn kho dược liệu / thành phẩm</a>
                <a href="{{ route('warehouse.material-stock') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.material-stock*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Tồn nguyên liệu thô / phụ liệu</a>
                <a href="{{ route('warehouse.sales-order-stock-checks') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('warehouse.sales-order-stock-checks*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Kiểm tra tồn đơn bán</a>
            </div>
        </details>
    @endif

    @if($currentUser)
        <details class="group/menu rounded-md border {{ request()->routeIs(['qa.batches*', 'material-lots*', 'qa.production-batches*', 'product-quality-standards*']) ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs(['qa.batches*', 'material-lots*', 'qa.production-batches*', 'product-quality-standards*']) ? 'open' : '' }}>
            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 {{ request()->routeIs(['qa.batches*', 'material-lots*', 'qa.production-batches*', 'product-quality-standards*']) ? 'text-blue-600' : 'text-slate-500' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6m-5 0v6.2L5.4 18a2 2 0 001.7 3h9.8a2 2 0 001.7-3L14 9.2V3M8 15h8" /></svg>
                    <span class="{{ request()->routeIs(['qa.batches*', 'material-lots*', 'qa.production-batches*', 'product-quality-standards*']) ? 'text-blue-700 font-semibold' : '' }}">QC</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </summary>
            <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Kiểm nghiệm</div>
                <a href="{{ route('material-lots.index', ['type' => 'raw_material']) }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('material-lots*') && request('type', 'raw_material') === 'raw_material' ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Kiểm tra lô NCC</a>
                <a href="{{ route('material-lots.index', ['type' => 'accessory']) }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('material-lots*') && request('type', 'raw_material') === 'accessory' ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Kiểm tra phụ liệu</a>
                <a href="{{ route('qa.production-batches.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('qa.production-batches*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Phiếu kiểm nghiệm (PKN)</a>
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Tiêu chuẩn</div>
                <a href="{{ route('product-quality-standards.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('product-quality-standards*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Tiêu chuẩn chất lượng</a>
            </div>
        </details>
    @endif

    @if($currentUser)
        <details class="group/menu rounded-md border {{ request()->routeIs('traceability*') ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs('traceability*') ? 'open' : '' }}>
            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-500 {{ request()->routeIs('traceability*') ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h10M4 17h16"></path></svg>
                    <span class="{{ request()->routeIs('traceability*') ? 'text-blue-700 font-semibold' : '' }}">Truy xuất nguồn gốc</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </summary>
            <div class="px-2 pb-2 pt-1 border-t border-slate-200 bg-white rounded-b-md">
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Tra cứu</div>
                <a href="{{ route('traceability.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('traceability.index') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Tra cứu lô và chuỗi liên kết</a>
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Quản lý</div>
                <a href="{{ route('traceability.management.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('traceability.management.*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Quản lý mã truy xuất</a>
            </div>
        </details>
    @endif

    @if($currentUser)
        <details class="group/menu rounded-md border {{ request()->routeIs(['departments*', 'users*']) ? 'bg-blue-50/50 border-blue-200' : 'bg-slate-50 border-slate-200 hover:bg-slate-100' }} transition" {{ request()->routeIs(['departments*', 'users*']) ? 'open' : '' }}>
            <summary class="list-none cursor-pointer flex items-center justify-between px-3 py-2 text-xs font-medium text-slate-600 hover:text-slate-900">
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-slate-500 {{ request()->routeIs(['departments*', 'users*', 'traceability*']) ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span class="{{ request()->routeIs(['departments*', 'users*', 'traceability*']) ? 'text-blue-700 font-semibold' : '' }}">Quản trị</span>
                </div>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200 group-open/menu:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
            </summary>
            <div class="px-2 pb-2 pt-1 space-y-1 border-t border-slate-200 bg-white rounded-b-md">
                <div class="mt-3 first:mt-1 mb-0.5 flex items-center gap-2 px-2.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 after:content-[''] after:h-px after:flex-1 after:bg-slate-200">Tổ chức</div>
                <a href="{{ route('departments.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('departments*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Phòng ban</a>
                @if($currentUser->isITDepartment())
                    <a href="{{ route('users.index') }}" class="block pl-5 pr-2.5 py-1.5 rounded text-xs transition {{ request()->routeIs('users*') ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">Nhân sự</a>
                @endif
            </div>
        </details>
    @endif
</div>