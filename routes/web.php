<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PrivateDocumentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRegulatoryDocumentController;
use App\Http\Controllers\ProductQualityStandardController;
use App\Http\Controllers\ProductStorageMethodController;
use App\Http\Controllers\PpcbController;
use App\Http\Controllers\suppliercontroller; 
use App\Http\Controllers\PurchaseOrderController; 
use App\Http\Controllers\GoodsReceiptController; 
use App\Http\Controllers\SupplierBatchController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\ProductionMonthlyPlanController;
use App\Http\Controllers\TraceabilityController;
use App\Http\Controllers\TraceabilityManagementController;
use App\Http\Controllers\LabelController;
use App\Models\Department; 
use App\Models\User;         
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : response()->view('auth.login');
});

Route::get('/dashboard', function () {
    $totalDepartments = Department::count();
    $totalUsers = User::count();
    $workingUsers = User::where('status', 'working')->orWhereNull('status')->count();
    $currentUser = auth()->user();
    $isIT = $currentUser->isITDepartment();
    $isSales = $currentUser->isSalesDepartment();
    $isWarehouse = $currentUser->isWarehouseDepartment();
    $isPlanning = $currentUser->isPlanningDepartment();
    $isProduction = $currentUser->isProductionDepartment();
    $isQA = $currentUser->isQADepartment();

    return view('dashboard', compact(
        'totalDepartments',
        'totalUsers',
        'workingUsers',
        'isIT',
        'isSales',
        'isWarehouse',
        'isPlanning',
        'isProduction',
        'isQA',
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/notifications/summary', [\App\Http\Controllers\NotificationController::class, 'summary'])->name('notifications.summary');
    Route::get('/documents/supplier-batches/{supplierBatch}/coa', [PrivateDocumentController::class, 'supplierCoa'])
        ->name('private-documents.supplier-coa');
    Route::get('/documents/production-batches/{productionFinishedBatch}/qc-report', [PrivateDocumentController::class, 'productionQualityReport'])
        ->name('private-documents.production-qc-report');

    // Hồ sơ nhân viên / Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/avatar', [ProfileController::class, 'avatar'])->name('profile.avatar');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Trang đổi mật khẩu riêng biệt
    Route::get('/profile/password', function () {
        return view('profile.change-password');
    })->name('password.edit');

    // Quản lý Phòng ban
    Route::get('/departments/export', [DepartmentController::class, 'export'])->name('departments.export');
    Route::get('/departments/import', [DepartmentController::class, 'importForm'])->name('departments.import.form');
    Route::post('/departments/import', [DepartmentController::class, 'import'])->name('departments.import');
    Route::resource('departments', DepartmentController::class)->except(['show']);

    // Quản lý Nhân viên
    Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
    Route::get('/users/import', [UserController::class, 'importForm'])->name('users.import.form');
    Route::post('/users/import', [UserController::class, 'import'])->name('users.import');
    Route::resource('users', UserController::class)->except(['show']);

    // Quản lý PPCB
    Route::get('/ppcb/export', [PpcbController::class, 'export'])->name('ppcb.export');
    Route::get('/ppcb/import', [PpcbController::class, 'importForm'])->name('ppcb.import.form');
    Route::post('/ppcb/import', [PpcbController::class, 'import'])->name('ppcb.import');
    Route::resource('ppcb', PpcbController::class)->except(['show']);

    // Quản lý định mức nguyên liệu sản xuất
    Route::get('/boms', [BomController::class, 'index'])->name('boms.index');
    Route::get('/boms/mrp', [BomController::class, 'plan'])->name('boms.plan');
    Route::get('/boms/create', [BomController::class, 'create'])->name('boms.create');
    Route::post('/boms', [BomController::class, 'store'])->name('boms.store');
    Route::post('/boms/{productBom}/approve', [BomController::class, 'approve'])->name('boms.approve');
    Route::post('/boms/{productBom}/reject', [BomController::class, 'reject'])->name('boms.reject');

    Route::get('/production-planning/approvals', [OrderController::class, 'productionApprovalQueue'])->name('production-planning.approvals');
    Route::post('/production-planning/approvals/{order}/approve', [OrderController::class, 'approveProductionPlan'])->name('production-planning.approve');
    Route::post('/production-planning/approvals/{order}/reject', [OrderController::class, 'rejectProductionPlan'])->name('production-planning.reject');
    Route::get('/production-monthly-plans', [ProductionMonthlyPlanController::class, 'index'])->name('production-monthly-plans.index');
    Route::post('/production-monthly-plans', [ProductionMonthlyPlanController::class, 'store'])->name('production-monthly-plans.store');
    Route::get('/production-monthly-plans/{plan}/edit', [ProductionMonthlyPlanController::class, 'edit'])->name('production-monthly-plans.edit');
    Route::put('/production-monthly-plans/{plan}', [ProductionMonthlyPlanController::class, 'update'])->name('production-monthly-plans.update');
    Route::post('/production-monthly-plans/{plan}/submit', [ProductionMonthlyPlanController::class, 'submit'])->name('production-monthly-plans.submit');
    Route::get('/production-monthly-plans/approvals', [ProductionMonthlyPlanController::class, 'approvals'])->name('production-monthly-plans.approvals');
    Route::post('/production-monthly-plans/{plan}/approve', [ProductionMonthlyPlanController::class, 'approve'])->name('production-monthly-plans.approve');
    Route::post('/production-monthly-plans/{plan}/reject', [ProductionMonthlyPlanController::class, 'reject'])->name('production-monthly-plans.reject');

    // Lệnh sản xuất: QA chỉ định lô, Kho xuất nguyên liệu và nhận thành phẩm
    Route::get('/production-orders', [ProductionController::class, 'index'])->name('production-orders.index');
    Route::get('/qa/internal-lots', [ProductionController::class, 'internalLotsIndex'])->name('qa.internal-lots.index');
    Route::get('/qa/internal-lots/create', [ProductionController::class, 'createInternalLot'])->name('qa.internal-lots.create');
    Route::post('/qa/internal-lots', [ProductionController::class, 'storeInternalLot'])->name('qa.internal-lots.store');
    Route::post('/qa/internal-lots/{productionFinishedBatch}/allocate-production-order', [ProductionController::class, 'allocateProductionOrderToLot'])->name('qa.internal-lots.allocate-production-order');
    Route::get('/qa/internal-lots/{productionFinishedBatch}/edit', [ProductionController::class, 'editInternalLot'])->name('qa.internal-lots.edit');
    Route::put('/qa/internal-lots/{productionFinishedBatch}', [ProductionController::class, 'updateInternalLot'])->name('qa.internal-lots.update');
    Route::delete('/qa/internal-lots/{productionFinishedBatch}', [ProductionController::class, 'destroyInternalLot'])->name('qa.internal-lots.destroy');
    Route::patch('/qa/internal-lots/{productionFinishedBatch}/close', [ProductionController::class, 'closeFinishedBatch'])->name('qa.internal-lots.close');
    Route::get('/qa/production-batches', [ProductionController::class, 'qaFinishedBatches'])->name('qa.production-batches.index');
    Route::get('/qa/production-output-lots', [ProductionController::class, 'qaLotsNeeded'])->name('qa.production-output-lots.index');
    Route::post('/qa/production-batches/{productionFinishedBatch}/approve', [ProductionController::class, 'approveFinishedBatch'])->name('qa.production-batches.approve');
    Route::get('/production/packaging', [ProductionController::class, 'packagingQueue'])->name('production.packaging.index');
    Route::post('/production/packaging/{order}/confirm', [ProductionController::class, 'confirmOrderPackaged'])->name('production.packaging.confirm');
    Route::get('/production-orders/{productionOrder}', [ProductionController::class, 'show'])->name('production-orders.show');
    Route::post('/production-orders/{productionOrder}/issue-materials', [ProductionController::class, 'issueMaterials'])->name('production-orders.issue-materials');
    Route::post('/production-orders/{productionOrder}/report-output', [ProductionController::class, 'reportProductionOutput'])->name('production-orders.report-output');
    Route::post('/production-orders/{productionOrder}/confirm-completion', [ProductionController::class, 'confirmProductionCompletion'])->name('production-orders.confirm-completion');

    // Truy vết lô nguyên liệu / thành phẩm / giao hàng
    Route::get('/traceability', [TraceabilityController::class, 'index'])->name('traceability.index');
    Route::get('/traceability/manage', [TraceabilityManagementController::class, 'index'])->name('traceability.management.index');
    Route::put('/traceability/settings/base-url', [TraceabilityManagementController::class, 'updateBaseUrl'])->name('traceability.management.base-url.update');
    Route::delete('/traceability/settings/base-url', [TraceabilityManagementController::class, 'deleteBaseUrl'])->name('traceability.management.base-url.delete');
    Route::put('/traceability/lots/{type}/{batch}/code', [TraceabilityManagementController::class, 'updateLotCode'])->name('traceability.management.lot-code.update');
    Route::delete('/traceability/lots/{type}/{batch}/code', [TraceabilityManagementController::class, 'deleteLotCode'])->name('traceability.management.lot-code.delete');

    // Quản lý Nhà cung cấp
    Route::resource('suppliers', suppliercontroller::class)->except(['show']);

    // Quản lý Sản phẩm dược liệu (Import & Resource)
    Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import.form');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::resource('products', ProductController::class)->except(['show']);
    foreach (['raw-materials' => \App\Http\Controllers\RawMaterialController::class, 'accessories' => \App\Http\Controllers\AccessoryController::class] as $catalogPrefix => $catalogController) {
        Route::get("/{$catalogPrefix}/export", [$catalogController, 'export'])->name("{$catalogPrefix}.export");
        Route::get("/{$catalogPrefix}/import", [$catalogController, 'importForm'])->name("{$catalogPrefix}.import.form");
        Route::post("/{$catalogPrefix}/import", [$catalogController, 'import'])->name("{$catalogPrefix}.import");
    }
    Route::resource('raw-materials', \App\Http\Controllers\RawMaterialController::class)->except(['show']);
    Route::resource('accessories', \App\Http\Controllers\AccessoryController::class)->except(['show']);
    Route::get('/api/raw-materials/search', [\App\Http\Controllers\RawMaterialController::class, 'searchAjax'])->name('api.raw-materials.search');
    Route::get('/api/accessories/search', [\App\Http\Controllers\AccessoryController::class, 'searchAjax'])->name('api.accessories.search');
    Route::resource('product-regulatory-documents', ProductRegulatoryDocumentController::class)->except(['show']);
    Route::resource('product-quality-standards', ProductQualityStandardController::class)->except(['show']);
    Route::resource('product-storage-methods', ProductStorageMethodController::class)->except(['show']);

    // ==========================================
    // MODULE: QUẢN LÝ ĐƠN MUA HÀNG (PURCHASE ORDERS)
    // ==========================================
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    Route::get('/purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('purchase-orders.edit');
    Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('purchase-orders.update');
    Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('purchase-orders.destroy');
    Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    
    // Luồng quy trình duyệt & giao hàng PO
    Route::post('/purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::put('/purchase-orders/{purchaseOrder}/complete-inspection', [PurchaseOrderController::class, 'completeWithInspection'])->name('purchase-orders.complete-inspection');
    Route::get('/purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'exportPdf'])->name('purchase-orders.pdf');

    // ==========================================
    // MODULE: QUẢN LÝ NHẬN HÀNG & KIỂM ĐỊNH QC (GOODS RECEIPTS)
    // ==========================================
    Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->name('goods-receipts.index');
    Route::get('/goods-receipts/create/{purchaseOrder}', [GoodsReceiptController::class, 'create'])->name('goods-receipts.create');
    Route::post('/goods-receipts/store/{purchaseOrder}', [GoodsReceiptController::class, 'store'])->name('goods-receipts.store');
    Route::get('/goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show'])->name('goods-receipts.show');

    // ==========================================
    // MODULE: XÁC NHẬN KHO
    // ==========================================
    Route::get('/warehouse/purchase-orders', [WarehouseController::class, 'purchaseOrders'])->name('warehouse.purchase-orders');
    Route::post('/warehouse/purchase-orders/{purchaseOrder}/mark-delivered', [WarehouseController::class, 'markPurchaseOrderDelivered'])->name('warehouse.purchase-orders.mark-delivered');
    Route::get('/warehouse/material-stock', [WarehouseController::class, 'materialStock'])->name('warehouse.material-stock');
    Route::get('/warehouse/production-issues', [WarehouseController::class, 'productionIssues'])->name('warehouse.production-issues');
    Route::get('/warehouse/material-issues', [WarehouseController::class, 'materialIssues'])->name('warehouse.material-issues');
    Route::post('/warehouse/material-issues/{order}/issue', [WarehouseController::class, 'issueMaterialOrder'])->name('warehouse.material-issues.issue');
    Route::get('/purchase-orders/{purchaseOrder}/material-receipt', [\App\Http\Controllers\MaterialReceiptController::class, 'create'])->name('material-receipts.create');
    Route::post('/purchase-orders/{purchaseOrder}/material-receipt', [\App\Http\Controllers\MaterialReceiptController::class, 'store'])->name('material-receipts.store');
    Route::get('/warehouse/stock', [WarehouseController::class, 'stockOverview'])->name('warehouse.stock');
    Route::get('/warehouse/stock/{product}', [WarehouseController::class, 'showStockProduct'])->name('warehouse.stock.product');
    Route::get('/labels', [LabelController::class, 'index'])->name('labels.index');
    Route::get('/labels/orders/{order}', [LabelController::class, 'showOrder'])->name('labels.orders.show');
    Route::post('/labels/orders/{order}/print', [LabelController::class, 'printOrder'])->name('labels.orders.print');
    Route::post('/labels/orders/{order}/print-direct', [LabelController::class, 'printOrderDirect'])->name('labels.orders.print-direct');
    Route::get('/warehouse/sales-order-stock-checks', [WarehouseController::class, 'salesOrderStockChecks'])->name('warehouse.sales-order-stock-checks');
    Route::post('/warehouse/sales-order-stock-checks/{order}/confirm', [WarehouseController::class, 'confirmSalesOrderStockCheck'])->name('warehouse.sales-order-stock-checks.confirm');
    Route::get('/warehouse/production-batches', [WarehouseController::class, 'productionBatchReceipts'])->name('warehouse.production-batches');
    Route::post('/warehouse/production-batches/{productionFinishedBatch}/receive', [WarehouseController::class, 'receiveProductionBatch'])->name('warehouse.production-batches.receive');
    Route::get('/warehouse/sales-orders', [WarehouseController::class, 'salesOrders'])->name('warehouse.sales-orders');
    Route::get('/warehouse/delivered-sales-orders', [WarehouseController::class, 'deliveredSalesOrders'])->name('warehouse.delivered-sales-orders');
    Route::get('/warehouse/sales-orders/{order}/confirm', [WarehouseController::class, 'showSalesOrder'])->name('warehouse.sales-orders.confirm-form');
    Route::post('/warehouse/sales-orders/{order}/confirm', [WarehouseController::class, 'confirmSalesOrder'])->name('warehouse.sales-orders.confirm');
    Route::post('/warehouse/sales-orders/{order}/ship', [WarehouseController::class, 'confirmSalesOrderShipment'])->name('warehouse.sales-orders.ship');

    // ==========================================
    // MODULE: QA & LÔ HÀNG NHÀ CUNG CẤP (SUPPLIER BATCHES & COA)
    // ==========================================
    Route::get('/qa/batches', [SupplierBatchController::class, 'index'])->name('qa.batches.index');
    Route::get('/qa/coas', [SupplierBatchController::class, 'coasIndex'])->name('qa.coas.index');
    Route::get('/qa/material-lots', [\App\Http\Controllers\MaterialLotController::class, 'index'])->name('material-lots.index');
    Route::get('/qa/material-lots/{materialLot}/coa', [\App\Http\Controllers\MaterialLotController::class, 'coa'])->name('material-lots.coa');
    Route::patch('/qa/material-lots/{materialLot}', [\App\Http\Controllers\MaterialLotController::class, 'updateCoa'])->name('material-lots.update-coa');
    Route::post('/qa/material-lots/{materialLot}/approve', [\App\Http\Controllers\MaterialLotController::class, 'approve'])->name('material-lots.approve');
    Route::post('/qa/material-lots/{materialLot}/reject', [\App\Http\Controllers\MaterialLotController::class, 'reject'])->name('material-lots.reject'); 
    
    Route::patch('/supplier-batches/{supplierBatch}/update-coa', [SupplierBatchController::class, 'updateCoa'])
        ->name('supplier-batches.update-coa');
    Route::post('/supplier-batches/{supplierBatch}/approve', [SupplierBatchController::class, 'approve'])
        ->name('supplier-batches.approve');
    Route::get('/supplier-returns', [\App\Http\Controllers\SupplierReturnController::class, 'index'])->name('supplier-returns.index');
    Route::post('/supplier-returns/items/{purchaseOrderItem}', [\App\Http\Controllers\SupplierReturnController::class, 'store'])->name('supplier-returns.store');
    Route::post('/supplier-returns/{supplierReturnOrder}/dispatch', [\App\Http\Controllers\SupplierReturnController::class, 'dispatch'])->name('supplier-returns.dispatch');
    Route::resource('supplier-batches', SupplierBatchController::class)->except(['index']);

    // ==========================================
    // MODULE: QUẢN LÝ KHÁCH HÀNG (CUSTOMERS)
    // ==========================================
    Route::resource('customers', CustomerController::class)->except(['show']);

    // ==========================================
    // MODULE: QUẢN LÝ ĐƠN HÀNG (SALES ORDERS) & CHI TIẾT VỊ THUỐC
    // ==========================================
    Route::get('/sales-order-approvals', [OrderController::class, 'salesApprovalQueue'])->name('sales-order-approvals.index');
    Route::post('/orders/{order}/approve-sales', [OrderController::class, 'approveSalesOrder'])->name('orders.approve-sales');
    Route::post('/orders/{order}/reject-sales', [OrderController::class, 'rejectSalesOrder'])->name('orders.reject-sales');
    Route::get('/qa/order-batches', [OrderController::class, 'qaBatchQueue'])->name('qa.order-batches');
    Route::get('/production-planning', [OrderController::class, 'productionPlanningIndex'])->name('production-planning.index');
    Route::get('/production-planning/history', [OrderController::class, 'productionPlanningHistory'])->name('production-planning.history');
    Route::post('/production-planning/orders/{order}/plan', [OrderController::class, 'sendToProduction'])->name('production-planning.plan');
    Route::resource('orders', OrderController::class);
    Route::post('/orders/{order}/items', [OrderItemController::class, 'store'])->name('order-items.store');
    Route::patch('/order-items/{item}/qa', [OrderItemController::class, 'updateQa'])->name('order-items.update-qa');
    Route::delete('/order-items/{item}', [OrderItemController::class, 'destroy'])->name('order-items.destroy');
    
    // ==========================================
    // KHU VỰC API / AJAX SEARCH DÙNG CHUNG CHO TOÀN BỘ HỆ THỐNG
    // ==========================================
    Route::get('/api/suppliers/search', [suppliercontroller::class, 'searchAjax'])->name('api.suppliers.search');
    Route::get('/api/purchase-items/search', [\App\Http\Controllers\PurchaseOrderController::class, 'searchItems'])->name('api.purchase-items.search');
    Route::get('/api/products/search', [ProductController::class, 'searchAjax'])->name('api.products.search');
    Route::get('/api/ppcb/search', [PpcbController::class, 'searchAjax'])->name('api.ppcb.search');
    Route::get('/api/customers/search', [CustomerController::class, 'searchAjax'])->name('api.customers.search');
    Route::get('/api/supplier-batches/search', [SupplierBatchController::class, 'searchAjax'])->name('api.supplier-batches.search');

});

require __DIR__.'/auth.php';
