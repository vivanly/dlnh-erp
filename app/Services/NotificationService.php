<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductBom;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMonthlyPlan;
use App\Models\PurchaseOrder;
use App\Models\SupplierBatch;
use App\Models\User;

class NotificationService
{
    /**
     * Danh sách việc đang chờ người dùng xử lý, mỗi mục gồm nhãn, số lượng và đường dẫn.
     */
    public function forUser(User $user): array
    {
        $items = [];
        $add = function (string $label, int $count, string $route, array $params = []) use (&$items) {
            if ($count > 0) {
                $items[] = ['label' => $label, 'count' => $count, 'url' => route($route, $params)];
            }
        };

        $isDirector = $user->isGeneralDirector() || $user->isDirector();

        if ($user->isSalesDirector() || $user->isBiddingDirector() || $isDirector) {
            $add('Đơn hàng cần duyệt', Order::where('status', 'pending_sales_approval')->count(), 'sales-order-approvals.index');
        }

        if ($isDirector) {
            $add('Kế hoạch sản xuất cần duyệt', Order::where('status', 'pending_production_approval')->count(), 'production-planning.approvals');
            $add('Kế hoạch tháng cần duyệt', ProductionMonthlyPlan::where('status', 'pending_approval')->count(), 'production-monthly-plans.approvals');
            $add('Định mức (BOM) cần duyệt', ProductBom::where('status', 'pending_approval')->count(), 'boms.index');
        }

        if ($user->isSalesDirector() || $user->isGeneralDirector() || $user->isITDepartment()) {
            $add('Đơn mua hàng cần duyệt', PurchaseOrder::where('status', 'pending')->count(), 'purchase-orders.index', ['status' => 'pending']);
        }

        if ($user->isQADepartment()) {
            $add('Đơn hàng cần chốt lô', Order::where('status', 'pending_qa')->count(), 'qa.order-batches');
            $add('Lô NCC chờ kiểm tra', SupplierBatch::where('status', 'pending_qa')->count(), 'qa.batches.index');
        }

        if ($user->isQADepartment() || $user->isQCDepartment()) {
            $add('Lô thành phẩm chờ phiếu kiểm nghiệm', ProductionFinishedBatch::where('status', 'active')
                ->whereNotNull('warehouse_received_at')
                ->where(fn ($query) => $query->whereNull('qc_test_report_file')->orWhere('qc_result', 'failed'))
                ->count(), 'qa.production-batches.index');
        }

        if ($user->isWarehouseDepartment()) {
            $add('Đơn nguyên liệu thô cần xuất kho', Order::where('status', 'pending_material_issue')->count(), 'warehouse.material-issues');
            $add('Đơn hàng cần xác nhận tồn kho', Order::where('status', 'pending_warehouse_check')->whereNotNull('sales_approved_at')->count(), 'warehouse.sales-order-stock-checks');
            $add('Đơn mua chờ nhận hàng', PurchaseOrder::where('status', 'approved')->count(), 'warehouse.purchase-orders');
        }

        if ($user->isProductionDepartment()) {
            $add('Đơn hàng cần đóng gói', Order::where('status', 'ready_to_ship')->count(), 'production.packaging.index');
        }

        return $items;
    }
}
