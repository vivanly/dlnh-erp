<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductBomItem;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMaterialLot;
use App\Models\ProductionOrder;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\SalesOrderPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderPlannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_order_reserves_confirmed_fifo_stock_and_skips_qa_lot_selection(): void
    {
        $salesUser = User::factory()->create(['role' => 'sales']);
        $salesDirector = User::factory()->create(['role' => 'sales_director']);
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $qaUser = User::factory()->create(['role' => 'qa']);
        $customer = Customer::create([
            'code' => 'CUST-WAIT-QA',
            'name' => 'Wait QA Customer',
            'type' => 'Retail',
        ]);
        $product = Product::create([
            'name' => 'Wait QA product',
            'slug' => 'wait-qa-product',
            'sku' => 'WAIT-QA-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'WAIT-WH-STOCK-LOT',
            'initial_quantity' => 6,
            'current_quantity' => 6,
            'status' => 'active',
        ]);

        $this->actingAs($salesUser)
            ->post(route('orders.store'), [
                'order_code' => 'DL'.today()->format('ym').'0001',
                'customer_id' => $customer->id,
                'order_type' => 'DL',
                'order_date' => today()->toDateString(),
                'delivery_date' => today()->addDays(3)->toDateString(),
                'province_city' => 'Hà Nội',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'packaging_spec' => '1',
                    'finished_quantity' => 5,
                ]],
            ])
            ->assertRedirect(route('orders.index'));

        $order = Order::with('items')->sole();
        $this->assertSame('draft', $order->status);
        $this->actingAs($salesUser)
            ->post(route('orders.submit-sales', $order))
            ->assertRedirect();
        $order->refresh();
        $this->assertSame('pending_sales_approval', $order->status);
        $this->assertNull($order->qa_confirmed_at);
        $this->assertNull($order->sales_approved_at);
        $this->assertSame(0, ProductionOrder::count());
        $this->assertSame(0, SalesOrderLotAllocation::count());
        $this->assertSame(0, InventoryMovement::count());

        $this->actingAs($qaUser)
            ->get('/qa/order-batches')
            ->assertNotFound();

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.sales-order-stock-checks'))
            ->assertOk()
            ->assertDontSee($order->order_code);

        $this->actingAs($salesUser)
            ->post(route('orders.approve-sales', $order))
            ->assertForbidden();

        $this->actingAs($salesDirector)
            ->get(route('sales-order-approvals.index'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee($product->name);

        $this->actingAs($salesDirector)
            ->post(route('orders.reject-sales', $order), ['reason' => 'Cần xác nhận lại số lượng'])
            ->assertRedirect();
        $this->assertSame('sales_rejected', $order->fresh()->status);
        $this->assertSame('Cần xác nhận lại số lượng', $order->fresh()->sales_rejection_reason);

        $this->actingAs($salesUser)
            ->put(route('orders.update', $order), [
                'order_code' => $order->order_code,
                'customer_id' => $customer->id,
                'order_type' => 'DL',
                'order_date' => today()->toDateString(),
                'delivery_date' => today()->addDays(3)->toDateString(),
                'province_city' => 'Hà Nội',
                'contact_person' => null,
                'notes' => null,
                'status' => 'pending_warehouse_check',
                'items' => [[
                    'id' => $order->items->first()->id,
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'packaging_spec' => '1',
                    'finished_quantity' => 5,
                    'ppcb_id' => null,
                    'notes' => null,
                ]],
            ])
            ->assertRedirect(route('orders.show', $order));
        $this->actingAs($salesUser)
            ->post(route('orders.submit-sales', $order))
            ->assertRedirect();
        $this->assertSame('pending_sales_approval', $order->fresh()->status);
        $this->assertNull($order->fresh()->sales_rejection_reason);

        $this->actingAs($salesDirector)
            ->post(route('orders.approve-sales', $order))
            ->assertRedirect();

        $this->assertSame('pending_warehouse_check', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->sales_approved_at);
        $this->assertSame($salesDirector->id, $order->fresh()->sales_approved_by);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.sales-order-stock-checks'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Khả dụng 6.0000');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-order-stock-checks.confirm', $order), [
                'confirmed_quantities' => [
                    $order->items->first()->id => 5,
                ],
            ])
            ->assertRedirect(route('warehouse.sales-order-stock-checks'));

        $this->assertSame('ready_to_ship', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->qa_confirmed_at);
        $this->assertNotNull($order->fresh()->warehouse_stock_checked_at);
        $this->assertSame($warehouseUser->id, $order->fresh()->warehouse_stock_checked_by);
        $this->assertEquals(6, $order->items->first()->fresh()->warehouse_stock_available_quantity);
        $this->assertEquals(5, $order->items->first()->fresh()->warehouse_stock_confirmed_quantity);
        $this->assertEquals(6, $batch->fresh()->current_quantity);
        $this->assertSame(1, SalesOrderLotAllocation::count());
        $this->assertEquals(5, SalesOrderLotAllocation::sole()->reserved_quantity);
        $this->assertSame(0, InventoryMovement::count());

        $this->actingAs($qaUser)
            ->get('/qa/order-batches')
            ->assertNotFound();
    }

    public function test_warehouse_partial_fifo_confirmation_sends_shortage_to_planning(): void
    {
        $warehouseUser = User::factory()->create(['name' => 'Kho xác nhận', 'role' => 'warehouse']);
        $qaUser = User::factory()->create(['role' => 'qa']);
        $customer = Customer::create([
            'code' => 'CUST-STOCK-SHORTAGE',
            'name' => 'Stock shortage customer',
            'type' => 'Retail',
        ]);
        $product = Product::create([
            'name' => 'Stock shortage product',
            'slug' => 'stock-shortage-product',
            'sku' => 'STOCK-SHORTAGE-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'STOCK-SHORTAGE-LOT',
            'initial_quantity' => 3,
            'current_quantity' => 3,
            'status' => 'active',
        ]);
        $newerBatch = SupplierBatch::create([
            'goods_receipt_item_id' => 2,
            'product_id' => $product->id,
            'batch_number' => 'STOCK-SHORTAGE-LOT-2',
            'initial_quantity' => 7,
            'current_quantity' => 7,
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-STOCK-SHORTAGE-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_warehouse_check',
            'sales_approved_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 20,
            'packaging_spec' => '1',
        ]);
        $this->actingAs($warehouseUser)
            ->get(route('warehouse.sales-order-stock-checks'))
            ->assertOk()
            ->assertSee('Khả dụng 10.0000')
            ->assertSee('Thiếu');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-order-stock-checks.confirm', $order), [
                'confirmed_quantities' => [$item->id => 7],
            ])
            ->assertRedirect(route('warehouse.sales-order-stock-checks'));

        $order->refresh();
        $item->refresh();
        $this->assertSame('pending_planning', $order->status);
        $this->assertSame($warehouseUser->id, $order->warehouse_stock_checked_by);
        $this->assertNotNull($order->warehouse_stock_checked_at);
        $this->assertEquals(10, $item->warehouse_stock_available_quantity);
        $this->assertEquals(7, $item->warehouse_stock_confirmed_quantity);
        $allocations = SalesOrderLotAllocation::orderBy('id')->get();
        $this->assertCount(2, $allocations);
        $this->assertSame($batch->id, $allocations[0]->supplier_batch_id);
        $this->assertEquals(3, $allocations[0]->reserved_quantity);
        $this->assertSame($newerBatch->id, $allocations[1]->supplier_batch_id);
        $this->assertEquals(4, $allocations[1]->reserved_quantity);

        $this->actingAs($warehouseUser)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Thiếu 13.0000')
            ->assertSee('Kho xác nhận 7.0000 / cần 20.0000')
            ->assertSee('Tồn hệ thống lúc kiểm: 10.0000')
            ->assertSee('Kho xác nhận');

        $planningUser = User::factory()->create(['role' => 'production_planner']);
        $this->actingAs($planningUser)
            ->get(route('production-planning.index'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('13.0000');

        $this->actingAs($planningUser)
            ->post(route('production-planning.plan', $order), [
                'selected_item_ids' => [$item->id],
                'planned_quantities' => [$item->id => 13],
            ])
            ->assertRedirect(route('production-planning.index'));
        $this->assertSame('pending_production_approval', $order->fresh()->status);
        $this->assertEquals(13, ProductionOrder::sole()->planned_quantity);
        $this->assertSame($planningUser->id, ProductionOrder::sole()->created_by);
    }

    public function test_shortage_creates_bom_material_demand_without_decrementing_inventory(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-PLAN-1',
            'name' => 'Planner Test Customer',
            'type' => 'Retail',
        ]);
        $finishedProduct = Product::create([
            'name' => 'Hoài Sơn thái lát',
            'slug' => 'hoai-son-thai-lat-planner-test',
            'sku' => 'HS-PLANNER-TEST',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $rawProduct = Product::create([
            'name' => 'Củ mài',
            'slug' => 'cu-mai-planner-test',
            'sku' => 'CM-PLANNER-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $order = Order::create([
            'order_code' => 'SO-PLANNER-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(7),
            'province_city' => 'Hà Nội',
            'status' => 'pending_planning',
            'qa_confirmed_at' => now(),
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $finishedProduct->id,
            'quantity' => 100,
            'packaging_spec' => '1',
            'finished_quantity' => 100,
        ]);
        $bom = ProductBom::create([
            'product_id' => $finishedProduct->id,
            'version' => 1,
            'output_quantity' => 100,
            'output_unit' => 'kg',
            'yield_rate' => 0.834,
            'is_active' => true,
        ]);
        ProductBomItem::create([
            'product_bom_id' => $bom->id,
            'component_product_id' => $rawProduct->id,
            'quantity' => 100,
            'unit' => 'kg',
        ]);
        $planningUser = User::factory()->create(['role' => 'production_planner']);
        $this->actingAs($planningUser)
            ->get(route('production-planning.index'))
            ->assertOk()
            ->assertSee('Nhu cầu')
            ->assertSee('Đã có lô')
            ->assertSee('Còn thiếu')
            ->assertSee('100.0000')
            ->assertSee('Sản lượng sản xuất dự kiến');

        $hasProductionShortage = app(SalesOrderPlanner::class)->plan($order, null, [$orderItem->id => 150]);

        $this->assertTrue($hasProductionShortage);
        $this->assertSame('pending_production_approval', $order->fresh()->status);

        $productionOrder = ProductionOrder::with('materials')->sole();
        $this->assertSame('pending_director_approval', $productionOrder->status);
        $this->assertNull($productionOrder->provisional_batch_number);
        $this->assertEquals(150, $productionOrder->planned_quantity);
        $this->assertNull($productionOrder->product_bom_id);
        $this->assertSame(0, $productionOrder->materials->count());
        $this->assertSame(0, InventoryMovement::count());
        $this->assertSame(0, $productionOrder->finishedBatches()->count());

        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $this->actingAs($warehouseUser)
            ->post(route('production-orders.issue-materials', $productionOrder), ['lots' => [1 => 100]])
            ->assertSessionHas('error');

        $director = User::factory()->create(['role' => 'general_director']);
        $this->actingAs($director)
            ->get(route('production-planning.approvals'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee($productionOrder->production_code);

        $this->actingAs($director)
            ->post(route('production-planning.approve', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('waiting_production', $order->fresh()->status);
        $this->assertSame($director->id, $order->fresh()->production_plan_approved_by);
        $this->assertNotNull($order->fresh()->production_plan_approved_at);
        $this->assertSame('released', $productionOrder->fresh()->status);
        $this->assertSame(0, ProductionFinishedBatch::count());
        $this->assertSame(0, ProductionMaterialLot::count());
    }

    public function test_sufficient_lot_stock_is_reserved_without_decrementing_physical_quantity(): void
    {
        $customer = Customer::create([
            'code' => 'CUST-RESERVE-1',
            'name' => 'Reservation Test Customer',
            'type' => 'Retail',
        ]);
        $product = Product::create([
            'name' => 'Dược liệu có sẵn',
            'slug' => 'duoc-lieu-co-san-reserve-test',
            'sku' => 'DL-RESERVE-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'LOT-RESERVE-TEST',
            'initial_quantity' => 15,
            'current_quantity' => 15,
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-RESERVE-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(5),
            'province_city' => 'Hà Nội',
            'status' => 'pending_planning',
            'qa_confirmed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'packaging_spec' => '1',
            'finished_quantity' => 10,
        ]);
        $item->lotAllocations()->create([
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 10,
            'status' => 'reserved',
        ]);

        $hasProductionShortage = app(SalesOrderPlanner::class)->plan($order, null);

        $this->assertFalse($hasProductionShortage);
        $this->assertSame('ready_to_ship', $order->fresh()->status);
        $this->assertEquals(15, $batch->fresh()->current_quantity);
        $this->assertEquals(10, SalesOrderLotAllocation::sole()->reserved_quantity);
        $this->assertSame(0, InventoryMovement::count());
    }
}
