<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ProductBom;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMonthlyPlan;
use App\Models\ProductionOrder;
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

        if ($user->canApproveSalesOrder()) {
            $add('Đơn hàng cần duyệt', Order::where('status', 'pending_sales_approval')->count(), 'sales-order-approvals.index');
        }

        if ($user->canApproveProductionPlan()) {
            $add('Kế hoạch sản xuất cần duyệt', Order::where('status', 'pending_production_approval')->count(), 'production-planning.approvals');
        }

        if ($user->canApproveMonthlyPlan()) {
            $add('Kế hoạch tháng cần duyệt', ProductionMonthlyPlan::where('status', 'pending_approval')->count(), 'production-monthly-plans.approvals');
        }

        if ($user->canApproveBom()) {
            $add('Định mức (BOM) cần duyệt', ProductBom::where('status', 'pending_approval')->count(), 'boms.index');
        }

        if ($user->canApprovePurchaseOrder()) {
            $add('Đơn mua hàng cần duyệt', PurchaseOrder::where('status', 'pending')->count(), 'purchase-orders.index', ['status' => 'pending']);
        }

        if ($user->isQADepartment()) {
            $add('Đơn hàng cần chốt lô', Order::where('status', 'pending_qa')->count(), 'qa.order-batches');
            $add('Lệnh sản xuất hoàn tất chờ phân bổ lô', ProductionOrder::where('status', 'completed')->where('pending_finished_quantity', '>', 0)->count(), 'qa.production-output-lots.index');
            $add('Lô NCC chờ QA cập nhật COA', SupplierBatch::where('status', 'pending_qa')->where(fn ($q) => $q->whereNull('coa_file')->orWhereNull('mfg_date')->orWhereNull('exp_date'))->count(), 'qa.batches.index');
            $add('Lô nguyên liệu thô chờ QA cập nhật lô/COA', \App\Models\MaterialLot::where('status', 'pending_qa')->where('material_type', 'raw_material')->where(fn ($q) => $q->whereNull('batch_number')->orWhereNull('coa_file'))->count(), 'material-lots.index', ['type' => 'raw_material', 'status' => 'pending_qa']);
        }

        if ($user->isPlanningDepartment()) {
            $add('Đơn hàng chờ lập kế hoạch', Order::where('status', 'pending_planning')->whereNotNull('qa_confirmed_at')->count(), 'production-planning.index');
        }

        if ($user->isQCDepartment()) {
            $add('Lô NCC chờ QC xác nhận', SupplierBatch::where('status', 'pending_qa')->whereNotNull('coa_file')->whereNotNull('mfg_date')->whereNotNull('exp_date')->where('current_quantity', '>', 0)->count(), 'qa.batches.index');
            $add('Lô NCC nguyên liệu thô chờ QC xác nhận', \App\Models\MaterialLot::where('status', 'pending_qa')->where('material_type', 'raw_material')->whereNotNull('batch_number')->whereNotNull('coa_file')->count(), 'material-lots.index', ['type' => 'raw_material', 'status' => 'pending_qa']);
            $add('Phụ liệu chờ QC xác nhận', \App\Models\MaterialLot::where('status', 'pending_qa')->where('material_type', 'accessory')->count(), 'material-lots.index', ['type' => 'accessory', 'status' => 'pending_qa']);
        }

        if ($user->isQCDepartment()) {
            $add('Lô thành phẩm chờ phiếu kiểm nghiệm', ProductionFinishedBatch::where('status', 'active')
                ->whereNotNull('warehouse_received_at')
                ->where('pending_warehouse_quantity', '<=', 0.000001)
                ->where(fn ($query) => $query->whereNull('qc_test_report_file')->orWhere('qc_result', 'failed'))
                ->count(), 'qa.production-batches.index');
        }

        if ($user->isWarehouseDepartment()) {
            $add('Đơn nguyên liệu thô cần xuất kho', Order::where('status', 'pending_material_issue')->count(), 'warehouse.material-issues');
            $add('Lệnh sản xuất chờ Kho xuất nguyên liệu', ProductionOrder::where('status', 'released')->count(), 'warehouse.production-issues');
            $add('Lệnh sản xuất chờ Kho xác nhận hoàn tất', ProductionOrder::where('status', 'production_reported')->count(), 'production-orders.index');
            $add('Lô thành phẩm chờ Kho nhập', ProductionFinishedBatch::whereIn('status', ['pending_qa', 'active'])->where('pending_warehouse_quantity', '>', 0)->count(), 'warehouse.production-batches');
            $add('Đơn hàng cần xác nhận tồn kho', Order::where('status', 'pending_warehouse_check')->whereNotNull('sales_approved_at')->count(), 'warehouse.sales-order-stock-checks');
            $add('Đơn hàng chờ Kho đóng hàng', Order::where('status', 'ready_to_ship')
                ->whereNotNull('qa_confirmed_at')
                ->whereNull('warehouse_confirmed_at')
                ->whereNull('warehouse_packed_at')
                ->where(function ($query) {
                    $query->whereNotNull('production_packaged_at')
                        ->orWhereDoesntHave('items.lotAllocations', fn ($lots) => $lots
                            ->where('status', 'reserved')
                            ->whereNotNull('production_finished_batch_id'));
                })
                ->count(), 'warehouse.sales-orders');
            $add('Đơn mua chờ nhận hàng', PurchaseOrder::where('status', 'approved')->count(), 'warehouse.purchase-orders');
        }

        if ($user->isProductionDepartment()) {
            $add('Lệnh sản xuất cần báo sản lượng', ProductionOrder::where('status', 'materials_issued')->count(), 'production-orders.index');
            $add('Đơn hàng cần đóng gói', Order::where('status', 'ready_to_ship')
                ->whereNotNull('qa_confirmed_at')
                ->whereNull('production_packaged_at')
                ->whereHas('items.lotAllocations', fn ($query) => $query->where('status', 'reserved'))
                ->count(), 'production.packaging.index');
        }

        return $items;
    }
}
