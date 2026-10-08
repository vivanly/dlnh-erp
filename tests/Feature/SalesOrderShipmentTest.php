<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Ppcb;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\SalesOrderPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalesOrderShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa_must_match_order_ppcb_to_internal_lot_before_confirming_allocations(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $customer = Customer::create(['code' => 'PPCB-MATCH-CUSTOMER', 'name' => 'PPCB Match Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'PPCB match product',
            'slug' => 'ppcb-match-product',
            'sku' => 'PPCB-MATCH-TEST',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $orderPpcb = Ppcb::create(['ma' => 'PPCB-ORDER', 'ten_ppcb' => 'PPCB trên đơn']);
        $lotPpcb = Ppcb::create(['ma' => 'PPCB-LOT', 'ten_ppcb' => 'PPCB trên lô']);
        $order = Order::create([
            'order_code' => 'SO-PPCB-MATCH-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'pending_qa',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
            'ppcb_id' => $orderPpcb->id,
        ]);
        $batch = ProductionFinishedBatch::create([
            'product_id' => $product->id,
            'ppcb_id' => $lotPpcb->id,
            'batch_number' => 'PPCB-MISMATCH-LOT',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'pending_warehouse_quantity' => 0,
            'unit' => 'kg',
            'status' => 'active',
        ]);

        $this->actingAs($qaUser)
            ->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('PPCB trên đơn')
            ->assertSee('PPCB lô: PPCB-LOT · PPCB trên lô')
            ->assertSee('Sửa PPCB lô')
            ->assertSee('name="items[0][ppcb_id]"', false);

        $this->actingAs($qaUser)
            ->put(route('orders.update', $order), [
                'items' => [[
                    'id' => $item->id,
                    'ppcb_id' => $orderPpcb->id,
                    'allocations' => [['lot' => 'finished:'.$batch->id]],
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Có lỗi xảy ra: PPCB đơn hàng của PPCB match product không khớp PPCB lô PPCB-MISMATCH-LOT. Hãy sửa PPCB đơn hàng hoặc PPCB lô nội bộ trước khi chốt.');

        $this->assertSame('pending_qa', $order->fresh()->status);
        $this->assertSame($orderPpcb->id, $item->fresh()->ppcb_id);
        $this->assertDatabaseMissing('sales_order_lot_allocations', ['order_item_id' => $item->id]);

        $this->actingAs($qaUser)
            ->put(route('orders.update', $order), [
                'items' => [[
                    'id' => $item->id,
                    'ppcb_id' => $lotPpcb->id,
                    'allocations' => [['lot' => 'finished:'.$batch->id]],
                ]],
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame($lotPpcb->id, $item->fresh()->ppcb_id);
        $this->assertSame('pending_planning', $order->fresh()->status);
    }

    public function test_qa_can_assign_an_existing_internal_stock_lot_to_an_order(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $customer = Customer::create(['code' => 'STANDALONE-LOT-CUSTOMER', 'name' => 'Standalone Lot Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Standalone internal lot product',
            'slug' => 'standalone-internal-lot-product',
            'sku' => 'STANDALONE-LOT-TEST',
            'unit' => 'kg',
            'classification' => 'VT',
            'origin' => 'Việt Nam',
        ]);
        Product::create([
            'name' => 'Another classified product',
            'slug' => 'another-classified-product',
            'sku' => 'STANDALONE-LOT-OTHER',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $ppcb = Ppcb::create(['ma' => 'STANDALONE-PPCB', 'ten_ppcb' => 'Sơ chế độc lập']);
        $order = Order::create([
            'order_code' => 'SO-STANDALONE-LOT-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'pending_qa',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
            'ppcb_id' => $ppcb->id,
        ]);

        $this->actingAs($qaUser)
            ->get(route('qa.internal-lots.create'))
            ->assertOk()
            ->assertSee('Tạo mã lô nội bộ')
            ->assertSee('Lọc theo phân loại')
            ->assertSee('Tất cả phân loại')
            ->assertSee('value="DL"', false)
            ->assertSee('value="VT"', false)
            ->assertSee('data-classification="VT"', false)
            ->assertDontSee('name="production_order_id"', false)
            ->assertSee('Số lượng dự kiến có thể phân bổ / nhập kho')
            ->assertSee('Hồ sơ COA');
        $qcUser = User::factory()->create(['role' => 'qc']);
        $this->actingAs($qcUser)
            ->get(route('qa.production-batches.index'))
            ->assertOk()
            ->assertSee('QC')
            ->assertSee('Phiếu kiểm nghiệm (PKN)')
            ->assertDontSee('Cập nhật COA');

        $batch = ProductionFinishedBatch::create([
            'product_id' => $product->id,
            'origin' => 'Lào Cai',
            'ppcb_id' => $ppcb->id,
            'license_number' => 'GP-LOT-001',
            'batch_number' => 'STANDALONE-INTERNAL-LOT-1',
            'provisional_batch_number' => 'STANDALONE-INTERNAL-LOT-1',
            'planned_quantity' => 5,
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'pending_warehouse_quantity' => 0,
            'unit' => $product->unit,
            'mfg_date' => today(),
            'exp_date' => today()->addYear(),
            'status' => 'active',
            'warehouse_received_at' => now(),
        ]);
        $this->assertNull($batch->production_order_id);
        $this->assertSame('active', $batch->status);
        $this->assertEquals(5, $batch->current_quantity);
        $this->assertEquals(0, $batch->pending_warehouse_quantity);
        $this->assertSame('Lào Cai', $batch->origin);
        $this->assertSame($ppcb->id, $batch->ppcb_id);
        $this->assertSame('GP-LOT-001', $batch->license_number);

        $this->actingAs($qaUser)
            ->get(route('qa.internal-lots.index', ['search' => 'GP-LOT-001']))
            ->assertOk()
            ->assertSee('STANDALONE-INTERNAL-LOT-1')
            ->assertSee('Lào Cai')
            ->assertSee('STANDALONE-PPCB')
            ->assertSee('GP-LOT-001');
        $this->actingAs($qaUser)
            ->get(route('qa.internal-lots.edit', $batch))
            ->assertOk()
            ->assertSee('Số giấy phép')
            ->assertSee('Nguồn gốc');
        $this->actingAs($qaUser)
            ->put(route('qa.internal-lots.update', $batch), [
                'batch_number' => $batch->batch_number,
                'origin' => 'Yên Bái',
                'ppcb_id' => $ppcb->id,
                'license_number' => 'GP-LOT-002',
                'mfg_date' => today()->toDateString(),
                'exp_date' => today()->addYear()->toDateString(),
            ])
            ->assertRedirect(route('qa.internal-lots.index'))
            ->assertSessionHas('success');
        $batch->refresh();
        $this->assertSame('Yên Bái', $batch->origin);
        $this->assertSame('GP-LOT-002', $batch->license_number);

        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $this->actingAs($warehouseUser)
            ->get(route('warehouse.production-batches'))
            ->assertOk()
            ->assertDontSee('STANDALONE-INTERNAL-LOT-1');
        $this->actingAs($warehouseUser)
            ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => 5])
            ->assertRedirect(route('warehouse.production-batches'))
            ->assertSessionHas('error', 'Lô không còn số lượng chờ Kho nhập.');
        $this->assertEquals(5, $batch->fresh()->current_quantity);

        $this->actingAs($qaUser)
            ->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('STANDALONE-INTERNAL-LOT-1')
            ->assertSee('Khả dụng 5.0000');

        $this->actingAs($qaUser)
            ->put(route('orders.update', $order), [
                'items' => [[
                    'id' => $item->id,
                    'allocations' => [[
                        'lot' => 'finished:'.$batch->id,
                        'quantity' => 5,
                    ]],
                ]],
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $allocation = SalesOrderLotAllocation::sole();
        $this->assertSame($batch->id, $allocation->production_finished_batch_id);
        $this->assertNull($allocation->supplier_batch_id);
        $this->assertNull($batch->fresh()->production_order_id);

        $this->actingAs($qaUser)
            ->delete(route('qa.internal-lots.destroy', $batch))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('production_finished_batches', ['id' => $batch->id]);

        $this->actingAs($qaUser)
            ->post(route('qa.internal-lots.store'), [
                'product_id' => $product->id,
                'batch_number' => 'STANDALONE-INTERNAL-LOT-DELETE',
                'planned_quantity' => 1,
            ])
            ->assertRedirect(route('qa.internal-lots.index'))
            ->assertSessionHas('success');
        $unusedBatch = ProductionFinishedBatch::where('batch_number', 'STANDALONE-INTERNAL-LOT-DELETE')->sole();
        $this->assertSame('pending_qa', $unusedBatch->status);
        $this->assertEquals(1, $unusedBatch->pending_warehouse_quantity);
        $this->assertNull($unusedBatch->production_order_id);
        $this->actingAs($qaUser)
            ->delete(route('qa.internal-lots.destroy', $unusedBatch))
            ->assertRedirect(route('qa.internal-lots.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('production_finished_batches', ['id' => $unusedBatch->id]);
    }

    public function test_order_cannot_be_edited_after_qa_confirms_lots(): void
    {
        $itUser = User::factory()->create(['role' => 'it']);
        $customer = Customer::create(['code' => 'LOCKED-CUSTOMER', 'name' => 'Locked Customer', 'type' => 'Retail']);
        $order = Order::create([
            'order_code' => 'SO-LOCKED-AFTER-QA',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);

        $this->actingAs($itUser)
            ->get(route('orders.edit', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');
        $this->actingAs($itUser)
            ->put(route('orders.update', $order), ['customer_id' => $customer->id])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');
        $this->assertNotNull($order->fresh()->qa_confirmed_at);
        $this->assertSame('ready_to_ship', $order->fresh()->status);
        $this->actingAs($itUser)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee(route('orders.edit', $order));
    }

    public function test_qa_can_create_and_assign_a_standalone_lot_before_warehouse_receipt(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $customer = Customer::create(['code' => 'QA-CAPACITY-CUSTOMER', 'name' => 'QA Capacity Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'QA Capacity Product',
            'slug' => 'qa-capacity-product',
            'sku' => 'QA-CAPACITY',
            'unit' => 'kg',
            'classification' => 'VT',
            'origin' => 'Lào Cai',
        ]);
        $order = Order::create([
            'order_code' => 'SO-QA-CAPACITY',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'pending_qa',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'packaging_spec' => '1',
            'finished_quantity' => 4,
        ]);

        $this->actingAs($qaUser)
            ->post(route('qa.internal-lots.store'), [
                'product_id' => $product->id,
                'batch_number' => 'QA-CAPACITY-LOT-1',
                'planned_quantity' => 4,
                'origin' => 'Nguồn gốc gửi lên không đáng tin cậy',
            ])
            ->assertRedirect(route('qa.internal-lots.index'))
            ->assertSessionHas('success');

        $batch = ProductionFinishedBatch::sole();
        $this->assertSame('pending_qa', $batch->status);
        $this->assertEquals(4, $batch->planned_quantity);
        $this->assertEquals(4, $batch->pending_warehouse_quantity);
        $this->assertSame('Lào Cai', $batch->origin);
        $this->assertNull($batch->production_order_id);
        $this->assertSame(0, InventoryMovement::count());

        $this->actingAs($qaUser)
            ->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('QA có thể phân bổ lượng dự kiến của lô nội bộ vừa tạo độc lập')
            ->assertSee('QA-CAPACITY-LOT-1')
            ->assertSee('Chờ nhập 4.0000');

        $this->actingAs($qaUser)
            ->put(route('orders.update', $order), [
                'items' => [[
                    'id' => $item->id,
                    'allocations' => [[
                        'lot' => 'finished:'.$batch->id,
                        'quantity' => 4,
                    ]],
                ]],
            ])
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('success');

        $this->assertEquals(4, SalesOrderLotAllocation::sole()->reserved_quantity);
        $this->assertSame('pending_planning', $order->fresh()->status);
        $this->assertFalse(app(SalesOrderPlanner::class)->plan($order->fresh(), $qaUser->id));
        $this->assertSame('waiting_finished_goods_receipt', $order->fresh()->status);

        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $this->actingAs($warehouseUser)
            ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('waiting_finished_goods_receipt', $order->fresh()->status);
        $this->actingAs($warehouseUser)
            ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('ready_to_ship', $order->fresh()->status);
        $this->assertEquals(4, $batch->fresh()->current_quantity);
        $this->assertEquals(0, $batch->fresh()->pending_warehouse_quantity);
    }

    public function test_warehouse_cannot_receive_more_than_the_qa_lot_capacity(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create(['code' => 'LOT-CAPACITY-CUSTOMER', 'name' => 'Lot Capacity Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Lot capacity receiving product',
            'slug' => 'lot-capacity-receiving-product',
            'sku' => 'LOT-CAPACITY-RECEIVING',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $order = Order::create([
            'order_code' => 'SO-LOT-CAPACITY-RECEIPT',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'production_packaged_at' => now(),
        ]);
        $item = $order->items()->create(['product_id' => $product->id, 'quantity' => 4, 'packaging_spec' => 1]);
        $bom = ProductBom::create([
            'product_id' => $product->id,
            'version' => 1,
            'output_quantity' => 1,
            'output_unit' => 'kg',
            'yield_rate' => 1,
            'is_active' => true,
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'MO-LOT-CAPACITY-RECEIPT',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_bom_id' => $bom->id,
            'product_id' => $product->id,
            'planned_quantity' => 4,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
        $batch = ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $product->id,
            'batch_number' => 'LOT-CAPACITY-RECEIPT-1',
            'planned_quantity' => 3,
            'initial_quantity' => 4,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 1,
            'unit' => 'kg',
            'status' => 'pending_qa',
        ]);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => 1])
            ->assertRedirect()
            ->assertSessionHas('error', 'Số lượng nhập vượt lượng dự kiến của lô LOT-CAPACITY-RECEIPT-1 (3 kg).');

        $this->assertEquals(0, $batch->fresh()->current_quantity);
        $this->assertEquals(1, $batch->fresh()->pending_warehouse_quantity);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_stock_is_decremented_only_after_shipment_is_confirmed(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $productionUser = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'SHIP-CUSTOMER', 'name' => 'Shipment Test Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Shipment test product',
            'slug' => 'shipment-test-product',
            'sku' => 'SHIP-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'SHIP-LOT-1',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'coa_file' => 'coas/ship-lot.pdf',
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-SHIP-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 6,
            'packaging_spec' => '1',
            'finished_quantity' => 6,
        ]);
        $allocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 6,
            'status' => 'reserved',
        ]);

        $this->actingAs($productionUser)
            ->post(route('labels.orders.print', $order), [
                'additional' => [$allocation->id => 0],
            ])
            ->assertDownload('Nhan-SO-SHIP-TEST.xlsx');

        $this->actingAs($productionUser)
            ->post(route('production.packaging.confirm', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.confirm', $order), ['items' => [$item->id => 4]])
            ->assertRedirect();

        $this->assertEquals(10, $batch->fresh()->current_quantity);
        $this->assertSame(0, InventoryMovement::count());

        $this->assertSame(6, (int) SalesOrderLotAllocation::sole()->labelPrints()->sum('copies_count'));

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.confirm', $order), ['items' => [$item->id => 4]])
            ->assertRedirect(route('warehouse.sales-orders'));

        $this->assertEquals(10, $batch->fresh()->current_quantity);
        $this->assertSame(0, InventoryMovement::count());
        $this->assertEquals(4, $item->fresh()->packed_quantity);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.sales-orders.confirm-form', $order))
            ->assertOk()
            ->assertSee('Xác nhận đã gửi hàng');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertRedirect(route('warehouse.sales-orders'));

        $this->assertEquals(6, $batch->fresh()->current_quantity);
        $this->assertEquals(4, $item->fresh()->actual_quantity);
        $this->assertSame('ready_to_ship', $order->fresh()->status);
        $this->assertSame(1, InventoryMovement::where('movement_type', 'SALE_SHIPMENT')->count());

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.delivered-sales-orders'))
            ->assertOk()
            ->assertDontSee('SO-SHIP-TEST');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.confirm', $order), ['items' => [$item->id => 2]])
            ->assertRedirect(route('warehouse.sales-orders'));
        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertRedirect(route('warehouse.sales-orders'));

        $this->assertEquals(4, $batch->fresh()->current_quantity);
        $this->assertEquals(6, $item->fresh()->actual_quantity);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(2, InventoryMovement::where('movement_type', 'SALE_SHIPMENT')->count());
        $this->actingAs($warehouseUser)
            ->get(route('warehouse.delivered-sales-orders', ['search' => 'SO-SHIP-TEST']))
            ->assertOk()
            ->assertSee('SO-SHIP-TEST')
            ->assertSee('SHIP-LOT-1')
            ->assertSee('Đã trừ tồn đủ')
            ->assertSee('6.0000');
    }

    public function test_shipment_decrements_internal_lot_quantity_and_records_inventory_movement(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create(['code' => 'INTERNAL-SHIP-CUSTOMER', 'name' => 'Internal Shipment Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Internal shipment product',
            'slug' => 'internal-shipment-product',
            'sku' => 'INTERNAL-SHIP-TEST',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $batch = ProductionFinishedBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'INTERNAL-SHIP-LOT-1',
            'planned_quantity' => 10,
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'pending_warehouse_quantity' => 0,
            'unit' => 'kg',
            'status' => 'active',
            'qc_test_report_file' => 'qc-reports/internal-ship.pdf',
            'qc_result' => 'passed',
        ]);
        $order = Order::create([
            'order_code' => 'SO-INTERNAL-SHIP-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
            'warehouse_packed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'packed_quantity' => 4,
            'packaging_spec' => '1',
        ]);
        SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'production_finished_batch_id' => $batch->id,
            'reserved_quantity' => 4,
            'status' => 'reserved',
        ]);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertRedirect(route('warehouse.sales-orders'))
            ->assertSessionHas('success');

        $this->assertEquals(6, $batch->fresh()->current_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'production_finished_batch_id' => $batch->id,
            'movement_type' => 'SALE_SHIPMENT',
            'direction' => 'out',
            'quantity' => 4,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }

    public function test_delivered_order_tracking_flags_orders_without_stock_deduction_movements(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create(['code' => 'SHIP-MISSING-CUSTOMER', 'name' => 'Missing Deduction Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Missing deduction product',
            'slug' => 'missing-deduction-product',
            'sku' => 'MISSING-DEDUCTION',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'MISSING-DEDUCTION-LOT',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-MISSING-DEDUCTION',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today(),
            'province_city' => 'Hà Nội',
            'status' => 'completed',
            'warehouse_confirmed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'actual_quantity' => 3,
            'packaging_spec' => '1',
        ]);
        SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 3,
            'shipped_quantity' => 3,
            'status' => 'shipped',
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.delivered-sales-orders'))
            ->assertOk()
            ->assertSee('SO-MISSING-DEDUCTION')
            ->assertSee('MISSING-DEDUCTION-LOT')
            ->assertSee('Kiểm tra giao dịch trừ tồn')
            ->assertSee('0.0000');

        $this->assertEquals(10, $batch->fresh()->current_quantity);
        $this->assertSame(0, InventoryMovement::where('reference_id', $order->id)->count());
    }

    public function test_qa_must_confirm_the_selected_sale_lot_before_warehouse_can_pack(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $salesUser = User::factory()->create(['role' => 'sales']);
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $productionUser = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'QA-SHIP-CUSTOMER', 'name' => 'QA Shipment Test Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'QA shipment product',
            'slug' => 'qa-shipment-product',
            'sku' => 'QA-SHIP-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $automaticBatch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'QA-AUTO-LOT',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'status' => 'active',
        ]);
        $qaSelectedBatch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'QA-SELECTED-LOT',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'status' => 'active',
        ]);
        $otherOrder = Order::create([
            'order_code' => 'SO-OTHER-RESERVATION',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $otherItem = $otherOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => 7,
            'packaging_spec' => '1',
            'finished_quantity' => 7,
        ]);
        SalesOrderLotAllocation::create([
            'order_item_id' => $otherItem->id,
            'supplier_batch_id' => $automaticBatch->id,
            'reserved_quantity' => 7,
            'status' => 'reserved',
        ]);
        $order = Order::create([
            'order_code' => 'SO-QA-SHIP-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'pending_qa',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 6,
            'packaging_spec' => '1',
            'finished_quantity' => 6,
        ]);
        SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $automaticBatch->id,
            'reserved_quantity' => 6,
            'status' => 'reserved',
        ]);

        $this->actingAs($qaUser)
            ->get(route('qa.order-batches'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee($product->name);

        $this->actingAs($qaUser)
            ->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('items[0][allocations][0][lot]', false)
            ->assertSee('Khả dụng 3.0000')
            ->assertSee('Phần thiếu sẽ được Kế hoạch lập lệnh sản xuất.');

        $this->actingAs($qaUser)
            ->put(route('orders.update', $order), [
                'items' => [[
                    'id' => $item->id,
                    'allocations' => [
                        ['lot' => 'supplier:'.$automaticBatch->id, 'quantity' => 2],
                        ['lot' => 'supplier:'.$qaSelectedBatch->id, 'quantity' => 4],
                    ],
                ]],
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertNotNull($order->fresh()->qa_confirmed_at);
        $this->assertSame(4, SalesOrderLotAllocation::count());
        $this->assertSame('replaced', SalesOrderLotAllocation::where('order_item_id', $item->id)->where('supplier_batch_id', $automaticBatch->id)->where('status', 'replaced')->sole()->status);
        $this->assertEquals(3, SalesOrderLotAllocation::where('order_item_id', $item->id)->where('supplier_batch_id', $automaticBatch->id)->where('status', 'reserved')->value('reserved_quantity'));
        $this->assertEquals(3, SalesOrderLotAllocation::where('order_item_id', $item->id)->where('supplier_batch_id', $qaSelectedBatch->id)->value('reserved_quantity'));
        $this->assertEquals(10, $automaticBatch->fresh()->current_quantity);
        $this->assertEquals(10, $qaSelectedBatch->fresh()->current_quantity);
        $this->assertSame('pending_planning', $order->fresh()->status);

        $this->actingAs($qaUser)
            ->get(route('traceability.index', ['search' => $automaticBatch->batch_number]))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Đã thay lô');

        $this->actingAs($qaUser)
            ->delete(route('order-items.destroy', $item))
            ->assertForbidden();
        $this->assertNotNull($item->fresh());

        $planningUser = User::factory()->create(['role' => 'production_planner']);
        $this->actingAs($planningUser)
            ->get(route('production-planning.index'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee($customer->name)
            ->assertSee($product->name)
            ->assertSee('Cần 6.0000 kg');

        $planningUser = User::factory()->create(['role' => 'production_planner']);
        $this->actingAs($planningUser)
            ->get(route('production-planning.index'))
            ->assertOk()
            ->assertSee($order->order_code);

        $this->actingAs($planningUser)
            ->post(route('production-planning.plan', $order))
            ->assertRedirect(route('production-planning.index'));
        $this->assertSame('ready_to_ship', $order->fresh()->status);

        $this->actingAs($productionUser)
            ->post(route('labels.orders.print', $order), [
                'additional' => SalesOrderLotAllocation::where('order_item_id', $item->id)
                    ->where('status', 'reserved')
                    ->pluck('id')
                    ->mapWithKeys(fn ($id) => [$id => 0])
                    ->all(),
            ])
            ->assertDownload('Nhan-SO-QA-SHIP-TEST.xlsx');
        $this->actingAs($productionUser)
            ->post(route('production.packaging.confirm', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.sales-orders.confirm-form', $order))
            ->assertOk()
            ->assertSee('Xác nhận đóng hàng');
    }

    public function test_finished_lot_without_pkn_can_be_received_labeled_and_packed_but_not_shipped(): void
    {
        Storage::fake('local');
        $qaUser = User::factory()->create(['role' => 'qa']);
        $qcUser = User::factory()->create(['role' => 'qc']);
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $productionUser = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'PKN-GATE-CUSTOMER', 'name' => 'PKN Gate Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'PKN gate finished product',
            'slug' => 'pkn-gate-finished-product',
            'sku' => 'PKN-GATE-TEST',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $qcPpcb = Ppcb::create(['ma' => 'PKN-GATE-PPCB', 'ten_ppcb' => 'Quy trình PKN']);
        $order = Order::create([
            'order_code' => 'SO-PKN-GATE-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'pending_qa',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
            'ppcb_id' => $qcPpcb->id,
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
            'production_code' => 'MO-PKN-GATE-TEST',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_bom_id' => $bom->id,
            'product_id' => $product->id,
            'planned_quantity' => 5,
            'actual_quantity' => 5,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
        $batch = ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $product->id,
            'origin' => 'Hà Giang',
            'ppcb_id' => $qcPpcb->id,
            'license_number' => 'GP-PKN-TEST',
            'batch_number' => 'PKN-GATE-LOT-1',
            'provisional_batch_number' => 'PKN-GATE-LOT-1',
            'planned_quantity' => 5,
            'initial_quantity' => 5,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 5,
            'unit' => 'kg',
            'status' => 'pending_qa',
            'mfg_date' => today(),
            'exp_date' => today()->addYear(),
        ]);

        $this->assertSame('pending_qa', $batch->fresh()->status);
        $this->assertEquals(5, $batch->fresh()->pending_warehouse_quantity);

        $this->actingAs($qaUser)
            ->get(route('orders.edit', $order))
            ->assertOk()
            ->assertSee('PKN-GATE-LOT-1')
            ->assertSee('Chờ nhập 5.0000');
        $this->actingAs($qaUser)
            ->put(route('orders.update', $order), [
                'items' => [[
                    'id' => $item->id,
                    'allocations' => [[
                        'lot' => 'finished:'.$batch->id,
                        'quantity' => 5,
                    ]],
                ]],
            ])
            ->assertRedirect(route('orders.show', $order));
        $order->refresh();
        $this->assertNotNull($order->qa_confirmed_at);
        $this->assertTrue(app(SalesOrderPlanner::class)->plan($order, $qaUser->id) === false);
        $this->assertSame('waiting_finished_goods_receipt', $order->fresh()->status);

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => 2])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertEquals(2, $batch->fresh()->current_quantity);
        $this->assertSame('waiting_finished_goods_receipt', $order->fresh()->status);
        $this->actingAs($warehouseUser)
            ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => 3])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertEquals(5, $batch->fresh()->current_quantity);
        $this->assertSame('ready_to_ship', $order->fresh()->status);

        $allocation = SalesOrderLotAllocation::sole();
        $this->actingAs($productionUser)
            ->post(route('labels.orders.print', $order), ['additional' => [$allocation->id => 0]])
            ->assertDownload('Nhan-SO-PKN-GATE-TEST.xlsx');
        $this->actingAs($productionUser)
            ->get(route('production.packaging.index'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('Xác nhận đơn đã đóng gói');
        $this->actingAs($productionUser)
            ->post(route('production.packaging.confirm', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.confirm', $order), ['items' => [$item->id => 5]])
            ->assertRedirect(route('warehouse.sales-orders'));

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.sales-orders.confirm-form', $order))
            ->assertOk()
            ->assertSee('Chưa thể xác nhận gửi hàng')
            ->assertSee('Lô nội bộ PKN-GATE-LOT-1: chưa có PKN đạt.')
            ->assertSee('disabled', false);

        $this->actingAs($qcUser)
            ->get(route('qa.production-batches.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('PKN-GATE-TEST')
            ->assertSee('Hà Giang')
            ->assertSee('PKN-GATE-PPCB')
            ->assertSee('GP-PKN-TEST')
            ->assertSee('name="qc_test_report"', false)
            ->assertSee('name="qc_date"', false)
            ->assertSee('name="qc_test_report_file"', false)
            ->assertDontSee('name="origin"', false)
            ->assertDontSee('name="ppcb_id"', false)
            ->assertDontSee('name="license_number"', false);

        $this->actingAs($warehouseUser)
            ->from(route('warehouse.sales-orders.confirm-form', $order))
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertRedirect(route('warehouse.sales-orders.confirm-form', $order))
            ->assertSessionHas('error');
        $this->assertEquals(5, $batch->fresh()->current_quantity);
        $this->assertSame(0, InventoryMovement::where('movement_type', 'SALE_SHIPMENT')->count());

        $this->actingAs($qcUser)
            ->post(route('qa.production-batches.approve', $batch), [
                'qc_test_report' => 'PKN-GATE-001',
                'qc_date' => today()->toDateString(),
                'qc_result' => 'failed',
                'qc_test_report_file' => UploadedFile::fake()->create('pkn-gate.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($warehouseUser)
            ->from(route('warehouse.sales-orders.confirm-form', $order))
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertRedirect(route('warehouse.sales-orders.confirm-form', $order))
            ->assertSessionHas('error');

        $this->actingAs($qcUser)
            ->post(route('qa.production-batches.approve', $batch), [
                'qc_test_report' => 'PKN-GATE-002',
                'qc_date' => today()->toDateString(),
                'qc_result' => 'passed',
                'qc_test_report_file' => UploadedFile::fake()->create('pkn-gate-pass.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertRedirect(route('warehouse.sales-orders'))
            ->assertSessionHas('success');
        $this->assertEquals(0, $batch->fresh()->current_quantity);
        $this->assertSame(1, InventoryMovement::where('movement_type', 'SALE_SHIPMENT')->count());
    }

    public function test_existing_wip_lot_can_be_labeled_and_packed_without_pkn_but_not_shipped(): void
    {
        Storage::fake('local');
        $qaUser = User::factory()->create(['role' => 'qa']);
        $qcUser = User::factory()->create(['role' => 'qc']);
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $productionUser = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'WIP-PKN-CUSTOMER', 'name' => 'WIP PKN Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'WIP PKN product',
            'slug' => 'wip-pkn-product',
            'sku' => 'WIP-PKN-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $order = Order::create([
            'order_code' => 'SO-WIP-PKN-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(2),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
            'packed_quantity' => 5,
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
            'production_code' => 'MO-WIP-PKN-TEST',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_bom_id' => $bom->id,
            'product_id' => $product->id,
            'planned_quantity' => 5,
            'actual_quantity' => 5,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
        $batch = ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $product->id,
            'batch_number' => 'WIP-PKN-LOT-1',
            'provisional_batch_number' => 'WIP-PKN-LOT-1',
            'planned_quantity' => 5,
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'warehouse_received_at' => now(),
            'unit' => 'kg',
            'status' => 'active',
            'mfg_date' => today(),
            'exp_date' => today()->addYear(),
        ]);
        $allocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'production_finished_batch_id' => $batch->id,
            'reserved_quantity' => 5,
            'status' => 'reserved',
        ]);

        $this->actingAs($productionUser)
            ->post(route('labels.orders.print', $order), ['additional' => [$allocation->id => 0]])
            ->assertDownload('Nhan-SO-WIP-PKN-TEST.xlsx');
        $this->actingAs($productionUser)
            ->post(route('production.packaging.confirm', $order))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.confirm', $order), ['items' => [$item->id => 5]])
            ->assertRedirect(route('warehouse.sales-orders'))
            ->assertSessionHas('success');
        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertSessionHas('error');
        $this->assertEquals(5, $batch->fresh()->current_quantity);

        $this->actingAs($qcUser)
            ->post(route('qa.production-batches.approve', $batch), [
                'qc_test_report' => 'WIP-PKN-001',
                'qc_date' => today()->toDateString(),
                'qc_result' => 'passed',
                'qc_test_report_file' => UploadedFile::fake()->create('wip-pkn.pdf', 32, 'application/pdf'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.ship', $order))
            ->assertSessionHas('success');
        $this->assertEquals(0, $batch->fresh()->current_quantity);
        $this->actingAs($qaUser)
            ->get(route('qa.internal-lots.index'))
            ->assertOk()
            ->assertSee('Gợi ý chốt lô đã hết')
            ->assertSee($batch->batch_number);
        $this->actingAs($qaUser)
            ->patch(route('qa.internal-lots.close', $batch))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('closed', $batch->fresh()->status);
    }
}
