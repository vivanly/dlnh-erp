<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductBomItem;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMaterialLot;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStockAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_report_subtracts_open_sales_and_production_reservations_per_lot(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $customer = Customer::create(['code' => 'STOCK-AVAILABLE-CUST', 'name' => 'Stock Availability', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Stock availability product',
            'slug' => 'stock-availability-product',
            'sku' => 'STOCK-AVAILABLE-1',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'STOCK-AVAILABLE-LOT',
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'exp_date' => today()->addDays(90),
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-STOCK-AVAILABLE',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(5),
            'province_city' => 'Hà Nội',
            'status' => 'waiting_production',
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'packaging_spec' => '1',
            'finished_quantity' => 4,
        ]);
        SalesOrderLotAllocation::create([
            'order_item_id' => $orderItem->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 4,
            'shipped_quantity' => 1,
            'status' => 'reserved',
        ]);
        $bom = ProductBom::create([
            'product_id' => $product->id,
            'version' => 1,
            'output_quantity' => 4,
            'output_unit' => 'kg',
            'yield_rate' => 1,
            'is_active' => true,
        ]);
        $bomItem = ProductBomItem::create([
            'product_bom_id' => $bom->id,
            'component_product_id' => $product->id,
            'quantity' => 10,
            'unit' => 'kg',
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'MO-STOCK-AVAILABLE',
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'product_bom_id' => $bom->id,
            'product_id' => $product->id,
            'planned_quantity' => 4,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'planned',
        ]);
        $material = ProductionOrderMaterial::create([
            'production_order_id' => $productionOrder->id,
            'product_bom_item_id' => $bomItem->id,
            'product_id' => $product->id,
            'required_quantity' => 10,
            'unit' => 'kg',
        ]);
        ProductionMaterialLot::create([
            'production_order_material_id' => $material->id,
            'supplier_batch_id' => $batch->id,
            'allocated_quantity' => 10,
            'issued_quantity' => 4,
        ]);
        ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $product->id,
            'batch_number' => 'STOCK-AVAILABLE-INTERNAL-LOT',
            'initial_quantity' => 8,
            'current_quantity' => 8,
            'unit' => 'kg',
            'status' => 'active',
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.stock'))
            ->assertOk()
            ->assertSee('Lô NCC: 1')
            ->assertSee('Lô nội bộ: 1');

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.stock.product', ['product' => $product->id, 'lot_type' => 'supplier']))
            ->assertOk()
            ->assertSee('Lô NCC')
            ->assertSee('Tồn khả dụng sau giữ chỗ')
            ->assertSee('20,00 kg')
            ->assertSee('9,00 kg')
            ->assertSee('11,00 kg');

        $qaUser = User::factory()->create(['role' => 'qa']);
        $this->actingAs($qaUser)
            ->get(route('qa.batches.index'))
            ->assertOk()
            ->assertSee('Đã giữ')
            ->assertSee('Khả dụng')
            ->assertSee('9.00')
            ->assertSee('11.00');
    }
}