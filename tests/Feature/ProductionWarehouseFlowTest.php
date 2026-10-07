<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductBomItem;
use App\Models\ProductionBatchCodeHistory;
use App\Models\ProductionBatchInput;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\SupplierBatch;
use App\Models\User;
use App\Services\SalesOrderPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionWarehouseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_order_search_matches_material_products_and_lots_can_be_searched_by_product(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $finishedProduct = Product::create([
            'name' => 'Searchable finished product',
            'slug' => 'searchable-finished-product',
            'sku' => 'SEARCH-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $rawProduct = Product::create([
            'name' => 'Searchable raw material',
            'slug' => 'searchable-raw-material',
            'sku' => 'SEARCH-RAW',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $otherProduct = Product::create([
            'name' => 'Other raw material',
            'slug' => 'other-raw-material',
            'sku' => 'OTHER-RAW',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'MO-SEARCH-MATERIAL',
            'product_id' => $finishedProduct->id,
            'planned_quantity' => 5,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'released',
        ]);
        ProductionOrder::create([
            'production_code' => 'MO-OTHER-PRODUCTION-ORDER',
            'product_id' => $otherProduct->id,
            'planned_quantity' => 5,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'released',
        ]);
        ProductionOrderMaterial::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $rawProduct->id,
            'required_quantity' => 3,
            'issued_quantity' => 0,
            'unit' => 'kg',
        ]);
        SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $rawProduct->id,
            'batch_number' => 'SEARCH-RAW-LOT-1',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'status' => 'active',
        ]);
        SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $otherProduct->id,
            'batch_number' => 'OTHER-RAW-LOT-1',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('production-orders.index', ['search' => 'Searchable raw material']))
            ->assertOk()
            ->assertSee('MO-SEARCH-MATERIAL')
            ->assertSee($finishedProduct->name)
            ->assertDontSee('MO-OTHER-PRODUCTION-ORDER');

        $this->get(route('production-orders.show', $productionOrder))
            ->assertOk()
            ->assertSee('id="supplier-batch-search"', false)
            ->assertSee('data-searchable="Searchable raw material SEARCH-RAW-LOT-1"', false)
            ->assertSee('data-searchable="Other raw material OTHER-RAW-LOT-1"', false);
    }

    public function test_warehouse_records_actual_issue_and_finished_receipt_quantities_once(): void
    {
        Storage::fake('local');
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);
        $productionUser = User::factory()->create(['role' => 'production']);
        $qaUser = User::factory()->create(['role' => 'qa']);
        $qcUser = User::factory()->create(['role' => 'qc']);
        $customer = Customer::create(['code' => 'PROD-WH-CUST', 'name' => 'Production Warehouse Customer', 'type' => 'Retail']);
        $finishedProduct = Product::create([
            'name' => 'Production warehouse finished product',
            'slug' => 'production-warehouse-finished-product',
            'sku' => 'PROD-WH-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $rawProduct = Product::create([
            'name' => 'Production warehouse raw material',
            'slug' => 'production-warehouse-raw-material',
            'sku' => 'PROD-WH-RAW',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $order = Order::create([
            'order_code' => 'SO-PROD-WH-TEST',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(5),
            'province_city' => 'Hà Nội',
            'status' => 'waiting_production',
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $finishedProduct->id,
            'quantity' => 8,
            'packaging_spec' => '1',
            'finished_quantity' => 8,
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'MO-PROD-WH-TEST',
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'product_id' => $finishedProduct->id,
            'planned_quantity' => 8,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'released',
        ]);
        ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $finishedProduct->id,
            'batch_number' => 'PROD-WH-FIN-LOT-1',
            'provisional_batch_number' => 'PROD-WH-FIN-LOT-1',
            'planned_quantity' => 3,
            'initial_quantity' => 0,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 0,
            'unit' => 'kg',
            'status' => 'planned',
        ]);
        $firstRawBatch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $rawProduct->id,
            'batch_number' => 'PROD-WH-RAW-1',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'status' => 'active',
        ]);
        $secondRawBatch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $rawProduct->id,
            'batch_number' => 'PROD-WH-RAW-2',
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'status' => 'active',
        ]);
        $this->actingAs($warehouseUser)
            ->get(route('production-orders.show', $productionOrder))
            ->assertOk()
            ->assertSee('lots['.$firstRawBatch->id.']', false)
            ->assertSee('PROD-WH-RAW-1');

        $this->actingAs($qaUser)
            ->get(route('production-orders.show', $productionOrder))
            ->assertOk()
            ->assertDontSee('PROD-WH-RAW-1')
            ->assertDontSee('internal:');

        $issueData = ['lots' => [
            $firstRawBatch->id => '5.0000',
            $secondRawBatch->id => '7.0000',
        ]];
        $issueResponse = $this->actingAs($warehouseUser)
            ->post(route('production-orders.issue-materials', $productionOrder), $issueData)
            ->assertRedirect();
        $this->assertFalse($issueResponse->getSession()->has('error'));

        $material = ProductionOrderMaterial::where('production_order_id', $productionOrder->id)->where('product_id', $rawProduct->id)->sole();
        $firstAllocation = $material->lots()->where('supplier_batch_id', $firstRawBatch->id)->sole();
        $secondAllocation = $material->lots()->where('supplier_batch_id', $secondRawBatch->id)->sole();
        $this->assertEquals(0, $firstRawBatch->fresh()->current_quantity);
        $this->assertEquals(13, $secondRawBatch->fresh()->current_quantity);
        $this->assertEquals(5, $firstAllocation->fresh()->issued_quantity);
        $this->assertEquals(7, $secondAllocation->fresh()->issued_quantity);
        $this->assertEquals(12, $material->fresh()->issued_quantity);
        $this->assertEquals(12, InventoryMovement::where('movement_type', 'EXPORT_PRODUCTION')->sum('quantity'));

        $this->actingAs($warehouseUser)
            ->post(route('production-orders.issue-materials', $productionOrder), $issueData)
            ->assertRedirect();
        $this->assertEquals(12, InventoryMovement::where('movement_type', 'EXPORT_PRODUCTION')->sum('quantity'));

        $this->actingAs($warehouseUser)
            ->get(route('production-orders.show', $productionOrder))
            ->assertOk()
            ->assertSee('returned_materials['.$firstAllocation->id.']', false);

        $this->actingAs($warehouseUser)
            ->post(route('production-orders.receive-finished-batch', $productionOrder), [
                'actual_quantity' => '8.0000',
                'returned_materials' => [$firstAllocation->id => '0.6789', $secondAllocation->id => '0'],
                'mfg_date' => today()->toDateString(),
            ])
            ->assertRedirect(route('warehouse.production-batches'));

        $finishedBatches = ProductionFinishedBatch::orderBy('id')->get();
        $finishedBatch = $finishedBatches->first();
        $this->assertCount(1, $finishedBatches);
        $this->assertEquals(3, $finishedBatch->pending_warehouse_quantity);
        $this->assertEquals(5, $productionOrder->fresh()->pending_finished_quantity);
        $this->assertSame(0, InventoryMovement::where('movement_type', 'RECEIVE_PRODUCTION')->count());
        $this->assertEquals(4.2454, ProductionBatchInput::sum('consumed_quantity'));
        $this->assertEquals(11.3211, $material->fresh()->consumed_quantity);
        $this->assertSame('completed', $productionOrder->fresh()->status);

        $this->actingAs($qaUser)
            ->post(route('qa.internal-lots.store'), [
                'product_id' => $finishedProduct->id,
                'batch_number' => 'PROD-WH-FIN-LOT-2',
                'planned_quantity' => 5,
            ])
            ->assertRedirect(route('qa.internal-lots.index'))
            ->assertSessionHas('success');
        $finishedBatches = ProductionFinishedBatch::orderBy('id')->get();
        $finishedBatch = $finishedBatches->first();
        $this->assertCount(2, $finishedBatches);
        $this->assertEquals(5, $productionOrder->fresh()->pending_finished_quantity);
        $this->assertEquals(5, $finishedBatches[1]->pending_warehouse_quantity);
        $this->assertNull($finishedBatches[1]->production_order_id);
        $this->assertSame(0, $finishedBatches[1]->inputs()->count());
        $this->assertEquals(4.2454, ProductionBatchInput::sum('consumed_quantity'));

        $this->actingAs($warehouseUser)
            ->get(route('warehouse.production-batches'))
            ->assertOk()
            ->assertSee('Chờ QC · không chặn nhập kho');
        foreach ($finishedBatches as $batch) {
            $pendingQuantity = (float) $batch->pending_warehouse_quantity;
            $firstReceipt = $pendingQuantity / 2;
            $this->actingAs($warehouseUser)
                ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => $firstReceipt])
                ->assertRedirect()
                ->assertSessionHas('success');
            $this->assertEquals($firstReceipt, $batch->fresh()->current_quantity);
            $this->assertEquals($pendingQuantity - $firstReceipt, $batch->fresh()->pending_warehouse_quantity);
            $remainingQuantity = $pendingQuantity - $firstReceipt;

            $this->actingAs($warehouseUser)
                ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => $remainingQuantity + 0.0001])
                ->assertRedirect()
                ->assertSessionHas('error');
            $this->assertEquals($firstReceipt, $batch->fresh()->current_quantity);
            $this->assertEquals($remainingQuantity, $batch->fresh()->pending_warehouse_quantity);

            $this->actingAs($warehouseUser)
                ->post(route('warehouse.production-batches.receive', $batch), ['received_quantity' => $remainingQuantity])
                ->assertRedirect()
                ->assertSessionHas('success');
        }
        $finishedBatches = ProductionFinishedBatch::orderBy('id')->get();
        $finishedBatch = $finishedBatches->first();
        $this->assertEquals(3, $finishedBatch->current_quantity);
        $this->assertEquals(5, $finishedBatches[1]->fresh()->current_quantity);
        $this->assertEquals(0, $finishedBatch->pending_warehouse_quantity);
        $this->assertEquals(8, InventoryMovement::where('movement_type', 'RECEIVE_PRODUCTION')->sum('quantity'));

        $this->actingAs($qaUser)
            ->put(route('orders.update', $order), [
                'items' => [[
                    'id' => $orderItem->id,
                    'allocations' => [
                        ['lot' => 'finished:'.$finishedBatches[0]->id],
                        ['lot' => 'finished:'.$finishedBatches[1]->id],
                    ],
                ]],
            ])
            ->assertRedirect(route('orders.show', $order));
        $this->assertSame('pending_planning', $order->fresh()->status);
        $this->assertFalse(app(SalesOrderPlanner::class)->plan($order->fresh(), $qaUser->id));
        $this->assertSame('ready_to_ship', $order->fresh()->status);

        $this->actingAs($productionUser)
            ->post(route('labels.orders.print', $order), ['additional' => $orderItem->lotAllocations()->pluck('id')->mapWithKeys(fn ($id) => [$id => 0])->all()])
            ->assertDownload('Nhan-SO-PROD-WH-TEST.xlsx');
        $this->actingAs($productionUser)
            ->get(route('production.packaging.index'))
            ->assertOk()
            ->assertSee($order->order_code);
        $this->actingAs($productionUser)
            ->post(route('production.packaging.confirm', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.sales-orders.confirm', $order), ['items' => [$orderItem->id => 8]])
            ->assertRedirect(route('warehouse.sales-orders'));

        $this->actingAs($warehouseUser)
            ->get(route('traceability.index', ['search' => $finishedBatch->batch_number]))
            ->assertOk()
            ->assertSee('Lô nguyên liệu NCC cấu thành lô thành phẩm')
            ->assertSee('PROD-WH-RAW-1')
            ->assertSee('4.2454')
            ->assertSee('MO-PROD-WH-TEST');

        $this->assertSame('ready_to_ship', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->production_packaged_at);
        $this->assertSame(2, $orderItem->lotAllocations()->where('status', 'reserved')->count());

        $qcQueueBatch = ProductionFinishedBatch::where('batch_number', 'PROD-WH-FIN-LOT-1')->sole();
        $this->assertSame('active', $qcQueueBatch->status);
        $this->assertNotNull($qcQueueBatch->warehouse_received_at);
        $this->assertNull($qcQueueBatch->qc_test_report_file);
        $this->actingAs($qcUser)
            ->get(route('qa.production-batches.index', ['search' => 'PROD-WH-FIN-LOT-1']))
            ->assertOk()
            ->assertSee('PROD-WH-FIN-LOT-1')
            ->assertSee('name="qc_test_report_file"', false);

        $this->actingAs($qcUser)
            ->post(route('qa.production-batches.approve', $finishedBatches->first()), [
                'qc_test_report' => 'PKN-PROD-1',
                'qc_date' => today()->toDateString(),
                'qc_result' => 'passed',
                'mfg_date' => today()->toDateString(),
                'exp_date' => today()->addYear()->toDateString(),
            ])
            ->assertSessionHasErrors('qc_test_report_file');

        foreach ($finishedBatches as $index => $batch) {
            $this->actingAs($qcUser)
                ->post(route('qa.production-batches.approve', $batch), [
                    'qc_test_report' => 'PKN-PROD-'.($index + 1),
                    'qc_date' => today()->toDateString(),
                    'qc_result' => 'passed',
                    'qc_test_report_file' => UploadedFile::fake()->create('pkn-'.($index + 1).'.pdf', 32, 'application/pdf'),
                    'mfg_date' => today()->toDateString(),
                    'exp_date' => today()->addYear()->toDateString(),
                ])
                ->assertRedirect();
        }

        $this->assertSame('active', $finishedBatch->fresh()->status);
        $this->assertNotEmpty($finishedBatch->fresh()->qc_test_report_file);
        Storage::disk('local')->assertExists($finishedBatch->fresh()->qc_test_report_file);
        $this->assertSame('PROD-WH-FIN-LOT-1', $finishedBatch->fresh()->batch_number);
        $this->assertSame('PROD-WH-FIN-LOT-1', $finishedBatch->fresh()->provisional_batch_number);
        $this->assertSame(0, ProductionBatchCodeHistory::count());

        $this->actingAs($qaUser)
            ->get(route('traceability.index', ['search' => 'PROD-WH-FIN-LOT-1']))
            ->assertOk()
            ->assertSee('PROD-WH-FIN-LOT-1')
            ->assertDontSee('Lịch sử mã lô')
            ->assertSee('Phiếu kiểm nghiệm QC')
            ->assertSee('Xem / tải tệp PKN')
            ->assertSee(route('private-documents.production-qc-report', $finishedBatch), false)
            ->assertDontSee(asset('storage/' . $finishedBatch->fresh()->qc_test_report_file), false);

        $this->actingAs($qaUser)
            ->get(route('private-documents.production-qc-report', $finishedBatch))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertEquals(3, $finishedBatch->fresh()->current_quantity);
        $this->assertEquals(0, $finishedBatch->fresh()->pending_warehouse_quantity);

        $closeCandidate = ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $finishedProduct->id,
            'batch_number' => 'PROD-WH-FIN-LOT-CLOSE',
            'initial_quantity' => 1,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 0,
            'qc_test_report' => 'PKN-CLOSE-TEST',
            'qc_test_report_file' => 'qc/close-lot.pdf',
            'qc_result' => 'passed',
            'unit' => 'kg',
            'status' => 'active',
        ]);
        $reservedLot = $orderItem->lotAllocations()->create([
            'production_finished_batch_id' => $closeCandidate->id,
            'reserved_quantity' => 1,
            'shipped_quantity' => 0,
            'status' => 'reserved',
        ]);

        $this->actingAs($qaUser)
            ->patch(route('qa.internal-lots.close', $closeCandidate))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('active', $closeCandidate->fresh()->status);

        $reservedLot->update(['shipped_quantity' => 1]);
        $this->actingAs($qaUser)
            ->get(route('qa.internal-lots.index'))
            ->assertOk()
            ->assertSee('Gợi ý chốt lô đã hết')
            ->assertSee('PROD-WH-FIN-LOT-CLOSE');
        $this->actingAs($qaUser)
            ->patch(route('qa.internal-lots.close', $closeCandidate))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('closed', $closeCandidate->fresh()->status);
    }
}
