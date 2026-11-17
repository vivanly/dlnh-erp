<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductionOrder;
use App\Models\SalesOrderLotAllocation;
use App\Models\SalesOrderReturn;
use App\Models\SalesOrderReturnItem;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesOrderReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_return_is_quarantined_then_qc_can_restock_only_the_source_lot(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $qc = User::factory()->create(['role' => 'qc']);
        [$order, $item, $batch, $allocation] = $this->shippedSupplierItem();

        $this->actingAs($warehouse)
            ->post(route('warehouse.customer-returns.receive', $order), [
                'items' => [$allocation->id => ['quantity' => 2]],
                'note' => 'Bao bì móp',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $returnItem = SalesOrderReturnItem::sole();
        $this->assertSame('pending_inspection', $returnItem->status);
        $this->assertSame($batch->id, $returnItem->supplier_batch_id);
        $this->assertSame(0.0, (float) $batch->fresh()->current_quantity);
        $this->assertSame(0, InventoryMovement::count());
        $this->actingAs($qc)
            ->get(route('warehouse.customer-returns'))
            ->assertOk()
            ->assertSee('Chờ QC kiểm tra');

        $this->actingAs($qc)
            ->patch(route('warehouse.customer-returns.inspect', $returnItem), [
                'decision' => 'restock',
                'inspection_note' => 'QC đạt, nhập lại đúng lô.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('restocked', $returnItem->fresh()->status);
        $this->assertSame('processed', SalesOrderReturn::sole()->status);
        $this->assertSame(2.0, (float) $batch->fresh()->current_quantity);
        $this->assertSame('CUSTOMER_RETURN', InventoryMovement::sole()->movement_type);
        $this->assertSame($item->id, $returnItem->fresh()->order_item_id);
    }

    public function test_return_window_and_shipped_quantity_are_enforced(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        [$order, , , $allocation] = $this->shippedSupplierItem();
        $order->update(['warehouse_confirmed_at' => now()->subDays(31)]);

        $this->actingAs($warehouse)
            ->post(route('warehouse.customer-returns.receive', $order), [
                'items' => [$allocation->id => ['quantity' => 1]],
            ])
            ->assertSessionHas('error', 'Chỉ nhận hàng trả trong vòng 30 ngày kể từ khi đơn được giao.');
        $this->assertSame(0, SalesOrderReturn::count());

        $order->update(['warehouse_confirmed_at' => now()]);
        $this->actingAs($warehouse)
            ->post(route('warehouse.customer-returns.receive', $order), [
                'items' => [$allocation->id => ['quantity' => 6]],
            ])
            ->assertSessionHas('error');
        $this->assertSame(0, SalesOrderReturn::count());
    }

    public function test_qc_rework_decision_creates_an_approval_gated_replacement_order(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $qc = User::factory()->create(['role' => 'qc']);
        [$order, $item, , $allocation] = $this->shippedSupplierItem();
        ProductBom::create([
            'product_id' => $item->product_id,
            'version' => 1,
            'output_quantity' => 5,
            'output_unit' => 'kg',
            'yield_rate' => 1,
            'is_active' => true,
        ]);
        ProductionOrder::create([
            'production_code' => 'MO-RETURN-ORIGINAL',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_bom_id' => ProductBom::where('product_id', $item->product_id)->value('id'),
            'product_id' => $item->product_id,
            'planned_quantity' => 5,
            'actual_quantity' => 5,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
        $this->actingAs($warehouse)->post(route('warehouse.customer-returns.receive', $order), [
            'items' => [$allocation->id => ['quantity' => 2]],
        ])->assertSessionHas('success');
        $returnItem = SalesOrderReturnItem::sole();

        $this->actingAs($qc)
            ->patch(route('warehouse.customer-returns.inspect', $returnItem), [
                'decision' => 'rework',
                'inspection_note' => 'Cần tái sản xuất để thay thế.',
            ])
            ->assertSessionHas('success');

        $productionOrder = ProductionOrder::where('status', 'pending_director_approval')->sole();
        $this->assertSame('pending_director_approval', $productionOrder->status);
        $this->assertSame($order->id, $productionOrder->order_id);
        $this->assertSame($item->id, $productionOrder->order_item_id);
        $this->assertSame($productionOrder->id, $returnItem->fresh()->rework_production_order_id);
        $this->assertSame('pending_production_approval', $order->fresh()->status);
        $this->assertSame(3.0, (float) $item->fresh()->actual_quantity);

        $director = User::factory()->create(['role' => 'general_director']);
        $this->actingAs($director)
            ->post(route('production-planning.approve', $order))
            ->assertSessionHas('success');
        $this->assertSame('released', $productionOrder->fresh()->status);

        $qa = User::factory()->create(['role' => 'qa']);
        $this->actingAs($qa)
            ->post(route('production-orders.assign-batch-number', $productionOrder), [
                'batch_number' => 'RETURN-REWORK-LOT',
            ])
            ->assertSessionHas('success');
        $replacementAllocation = SalesOrderLotAllocation::where('status', 'reserved')->sole();
        $this->assertEquals(2, $replacementAllocation->reserved_quantity);
        $this->assertSame($productionOrder->id, $replacementAllocation->finishedBatch->production_order_id);
    }

    private function shippedSupplierItem(): array
    {
        $customer = Customer::create([
            'code' => 'RETURN-CUSTOMER',
            'name' => 'Return customer',
            'type' => 'Retail',
        ]);
        $product = Product::create([
            'name' => 'Return product',
            'slug' => 'return-product',
            'sku' => 'RETURN-TEST',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $order = Order::create([
            'order_code' => 'DL'.today()->format('ym').'9998',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today(),
            'province_city' => 'Hà Nội',
            'status' => 'completed',
            'warehouse_confirmed_at' => now(),
            'qa_confirmed_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'actual_quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'RETURN-SUPPLIER-LOT',
            'initial_quantity' => 5,
            'current_quantity' => 0,
            'status' => 'active',
            'coa_file' => 'coa.pdf',
        ]);
        $allocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 5,
            'shipped_quantity' => 5,
            'status' => 'shipped',
        ]);

        return [$order, $item, $batch, $allocation];
    }
}
