<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_approval_links_are_grouped_under_board_menu(): void
    {
        $director = User::factory()->create(['role' => 'general_director']);

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ban Giám Đốc')
            ->assertSee('Duyệt đơn mua')
            ->assertSee('Duyệt đơn bán')
            ->assertSee('Duyệt kế hoạch')
            ->assertSee('Duyệt kế hoạch tháng')
            ->assertSee('Kinh doanh')
            ->assertSee('QA')
            ->assertSee('QC')
            ->assertSee('Kho')
            ->assertSee('Sản xuất')
            ->assertSee('Quản trị');
    }

    public function test_purchase_approval_queue_is_highlighted_only_under_board_menu(): void
    {
        $director = User::factory()->create(['role' => 'general_director']);

        $this->actingAs($director)
            ->get(route('purchase-orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('<span class="text-blue-700 font-semibold">Ban Giám Đốc</span>', false)
            ->assertSee('Duyệt đơn mua')
            ->assertSee('Đơn mua hàng');
    }

    public function test_it_is_excluded_from_production_plan_approval_workflow(): void
    {
        $itUser = User::factory()->create(['role' => 'it']);

        $this->actingAs($itUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Duyệt kế hoạch')
            ->assertSee('Duyệt kế hoạch tháng');

        $this->get(route('production-planning.approvals'))
            ->assertOk()
            ->assertDontSee(route('production-planning.approve', ['order' => 1]));
    }

    public function test_regular_user_can_view_all_modules_without_write_controls(): void
    {
        $viewer = User::factory()->create(['role' => 'admin']);
        $this->actingAs($viewer);

        foreach ([
            'dashboard',
            'suppliers.index',
            'customers.index',
            'purchase-orders.index',
            'orders.index',
            'departments.index',
            'products.index',
            'ppcb.index',
            'boms.index',
            'boms.plan',
            'production-monthly-plans.index',
            'production-monthly-plans.approvals',
            'production-planning.index',
            'production-planning.approvals',
            'sales-order-approvals.index',
            'production-orders.index',
            'production.packaging.index',
            'warehouse.purchase-orders',
            'warehouse.sales-order-stock-checks',
            'warehouse.production-batches',
            'warehouse.sales-orders',
            'warehouse.stock',
            'qa.internal-lots.index',
            'qa.production-batches.index',
            'qa.production-output-lots.index',
            'qa.batches.index',
            'qa.coas.index',
            'product-regulatory-documents.index',
            'labels.index',
            'traceability.index',
            'traceability.management.index',
        ] as $routeName) {
            $this->get(route($routeName))->assertOk();
        }

        $this->get(route('boms.index'))->assertDontSee('Tạo phiên bản BOM');
        $this->get(route('production-monthly-plans.index'))->assertDontSee('Lưu dự thảo');
        $this->get(route('production-planning.index'))->assertDontSee('Lập kế hoạch / lệnh');
        $this->get(route('customers.create'))->assertForbidden();
        $this->get(route('orders.create'))->assertForbidden();
        $this->get(route('qa.internal-lots.create'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertDontSee('href="'.route('users.index').'"', false);
        $this->post(route('customers.store'), [])->assertForbidden();
    }

    public function test_admin_cannot_approve_purchase_sales_bom_or_production_plan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::create(['name' => 'Admin approval supplier', 'code' => 'ADMIN-APPROVAL-SUPPLIER']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-ADMIN-APPROVAL',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'order_date' => today(),
            'status' => 'pending',
            'subtotal' => 100,
            'tax_amount' => 5,
            'grand_total' => 105,
        ]);
        $customer = Customer::create([
            'code' => 'ADMIN-APPROVAL-CUSTOMER',
            'name' => 'Admin approval customer',
            'type' => 'Retail',
        ]);
        $order = Order::create([
            'order_code' => 'SO-ADMIN-APPROVAL',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_sales_approval',
        ]);
        $finishedProduct = Product::create([
            'name' => 'Admin approval finished product',
            'slug' => 'admin-approval-finished-product',
            'sku' => 'ADMIN-APPROVAL-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $bom = ProductBom::create([
            'product_id' => $finishedProduct->id,
            'version' => 1,
            'output_quantity' => 10,
            'output_unit' => 'kg',
            'yield_rate' => 1,
            'is_active' => false,
            'status' => 'pending_approval',
        ]);
        $productionPlan = Order::create([
            'order_code' => 'SO-ADMIN-PRODUCTION-APPROVAL',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_production_approval',
        ]);

        $this->actingAs($admin)
            ->post(route('purchase-orders.approve', $purchaseOrder))
            ->assertForbidden();
        $this->assertSame('pending', $purchaseOrder->fresh()->status);

        $this->actingAs($admin)
            ->post(route('orders.approve-sales', $order))
            ->assertForbidden();
        $this->assertSame('pending_sales_approval', $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('boms.approve', $bom))
            ->assertForbidden();
        $this->assertSame('pending_approval', $bom->fresh()->status);

        $this->actingAs($admin)
            ->get(route('production-planning.approvals'))
            ->assertOk()
            ->assertDontSee(route('production-planning.approve', ['order' => $productionPlan->id]));
        $this->assertSame('pending_production_approval', $productionPlan->fresh()->status);
    }

    public function test_rejection_requires_a_reason_for_purchase_sales_and_production_plan(): void
    {
        $director = User::factory()->create(['role' => 'general_director']);
        $salesDirector = User::factory()->create(['role' => 'sales_director']);
        $supplier = Supplier::create(['name' => 'Rejection reason supplier', 'code' => 'REASON-SUPPLIER']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-REASON-REQUIRED',
            'supplier_id' => $supplier->id,
            'user_id' => $salesDirector->id,
            'order_date' => today(),
            'status' => 'pending',
            'subtotal' => 100,
            'tax_amount' => 5,
            'grand_total' => 105,
        ]);
        $customer = Customer::create([
            'code' => 'REASON-CUSTOMER',
            'name' => 'Rejection reason customer',
            'type' => 'Retail',
        ]);
        $salesOrder = Order::create([
            'order_code' => 'SO-REASON-REQUIRED',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_sales_approval',
        ]);
        $productionPlan = Order::create([
            'order_code' => 'SO-PLAN-REASON-REQUIRED',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_production_approval',
        ]);

        $this->actingAs($director)
            ->post(route('purchase-orders.reject', $purchaseOrder), [])
            ->assertSessionHasErrors('rejection_reason');
        $this->assertSame('pending', $purchaseOrder->fresh()->status);

        $this->actingAs($salesDirector)
            ->post(route('orders.reject-sales', $salesOrder), [])
            ->assertSessionHasErrors('reason');
        $this->assertSame('pending_sales_approval', $salesOrder->fresh()->status);

        $this->actingAs($director)
            ->post(route('production-planning.reject', $productionPlan), [])
            ->assertSessionHasErrors('reason');
        $this->assertSame('pending_production_approval', $productionPlan->fresh()->status);
    }

    public function test_approved_documents_cannot_be_approved_again(): void
    {
        $director = User::factory()->create(['role' => 'general_director']);
        $salesDirector = User::factory()->create(['role' => 'sales_director']);
        $supplier = Supplier::create(['name' => 'Reapproval supplier', 'code' => 'REAPPROVAL-SUPPLIER']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-REAPPROVAL',
            'supplier_id' => $supplier->id,
            'user_id' => $salesDirector->id,
            'order_date' => today(),
            'status' => 'approved',
            'subtotal' => 100,
            'tax_amount' => 5,
            'grand_total' => 105,
        ]);
        $customer = Customer::create([
            'code' => 'REAPPROVAL-CUSTOMER',
            'name' => 'Reapproval customer',
            'type' => 'Retail',
        ]);
        $salesOrder = Order::create([
            'order_code' => 'SO-REAPPROVAL',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_warehouse_check',
        ]);
        $productionPlan = Order::create([
            'order_code' => 'SO-PLAN-REAPPROVAL',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'waiting_production',
        ]);
        $finishedProduct = Product::create([
            'name' => 'Reapproval finished product',
            'slug' => 'reapproval-finished-product',
            'sku' => 'REAPPROVAL-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $bom = ProductBom::create([
            'product_id' => $finishedProduct->id,
            'version' => 1,
            'output_quantity' => 10,
            'output_unit' => 'kg',
            'yield_rate' => 1,
            'is_active' => true,
            'status' => 'approved',
        ]);

        $this->actingAs($salesDirector)
            ->post(route('purchase-orders.approve', $purchaseOrder))
            ->assertSessionHas('error');
        $this->assertSame('approved', $purchaseOrder->fresh()->status);

        $this->actingAs($salesDirector)
            ->post(route('orders.approve-sales', $salesOrder))
            ->assertSessionHas('error');
        $this->assertSame('pending_warehouse_check', $salesOrder->fresh()->status);

        $this->actingAs($director)
            ->post(route('boms.approve', $bom))
            ->assertSessionHas('error');
        $this->assertSame('approved', $bom->fresh()->status);

        $this->actingAs($director)
            ->post(route('production-planning.approve', $productionPlan))
            ->assertSessionHas('error');
        $this->assertSame('waiting_production', $productionPlan->fresh()->status);
    }

    public function test_warehouse_cannot_pack_more_than_the_reserved_quantity(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create([
            'code' => 'OVER-SHIP-CUSTOMER',
            'name' => 'Over shipment customer',
            'type' => 'Retail',
        ]);
        $product = Product::create([
            'name' => 'Over shipment product',
            'slug' => 'over-shipment-product',
            'sku' => 'OVER-SHIP-PRODUCT',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = \App\Models\SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'OVER-SHIP-LOT',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'coa_file' => 'coas/over-ship.pdf',
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-OVER-SHIP',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
        ]);
        $item->lotAllocations()->create([
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 5,
            'shipped_quantity' => 0,
            'status' => 'reserved',
        ]);

        $this->actingAs($warehouse)
            ->post(route('warehouse.sales-orders.confirm', $order), [
                'items' => [$item->id => 6],
            ])
            ->assertSessionHasErrors('items');

        $this->assertNull($order->fresh()->warehouse_packed_at);
        $this->assertEquals(0, $item->fresh()->packed_quantity);
        $this->assertEquals(5, $batch->fresh()->current_quantity);
    }

    public function test_expired_supplier_lot_is_not_available_for_stock_check(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create([
            'code' => 'EXPIRED-LOT-CUSTOMER',
            'name' => 'Expired lot customer',
            'type' => 'Retail',
        ]);
        $product = Product::create([
            'name' => 'Expired lot product',
            'slug' => 'expired-lot-product',
            'sku' => 'EXPIRED-LOT-PRODUCT',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'EXPIRED-LOT-1',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'exp_date' => today()->subDay(),
            'coa_file' => 'coas/expired-lot.pdf',
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-EXPIRED-LOT',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_warehouse_check',
            'sales_approved_at' => now(),
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
        ]);

        $this->actingAs($warehouse)
            ->get(route('warehouse.sales-order-stock-checks'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertDontSee('EXPIRED-LOT-1')
            ->assertSee('Chưa có lô khả dụng');
    }

    public function test_finished_goods_receipt_cannot_exceed_pending_quantity(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create(['code' => 'RECEIPT-OVER-CUSTOMER', 'name' => 'Receipt over customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Receipt over product',
            'slug' => 'receipt-over-product',
            'sku' => 'RECEIPT-OVER-PRODUCT',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $order = Order::create([
            'order_code' => 'SO-RECEIPT-OVER',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'waiting_production',
        ]);
        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
        ]);
        $bom = ProductBom::create([
            'product_id' => $product->id,
            'version' => 1,
            'output_quantity' => 5,
            'output_unit' => 'kg',
            'yield_rate' => 1,
            'is_active' => true,
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'MO-RECEIPT-OVER',
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'product_bom_id' => $bom->id,
            'product_id' => $product->id,
            'planned_quantity' => 5,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
        $batch = ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $product->id,
            'batch_number' => 'RECEIPT-OVER-LOT',
            'planned_quantity' => 5,
            'initial_quantity' => 0,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 5,
            'unit' => 'kg',
            'status' => 'planned',
        ]);

        $this->actingAs($warehouse)
            ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => 6])
            ->assertSessionHas('error');

        $this->assertEquals(0, $batch->fresh()->current_quantity);
        $this->assertEquals(5, $batch->fresh()->pending_warehouse_quantity);
    }

    public function test_shipment_cannot_be_confirmed_before_warehouse_packing(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create(['code' => 'SHIP-BEFORE-PACK-CUSTOMER', 'name' => 'Ship before pack customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Ship before pack product',
            'slug' => 'ship-before-pack-product',
            'sku' => 'SHIP-BEFORE-PACK',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'SHIP-BEFORE-PACK-LOT',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'coa_file' => 'coas/ship-before-pack.pdf',
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-SHIP-BEFORE-PACK',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
        ]);
        $item->lotAllocations()->create([
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 5,
            'shipped_quantity' => 0,
            'status' => 'reserved',
        ]);

        $this->actingAs($warehouse)
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertSessionHas('error');

        $this->assertNull($order->fresh()->warehouse_confirmed_at);
        $this->assertEquals(5, $batch->fresh()->current_quantity);
    }
}
