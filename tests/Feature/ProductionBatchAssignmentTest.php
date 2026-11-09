<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBatchAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_menus_show_only_their_own_actions_and_warehouse_can_find_returns(): void
    {
        $it = User::factory()->create(['role' => 'it']);
        $qa = User::factory()->create(['role' => 'qa']);
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $production = User::factory()->create(['role' => 'production']);
        $product = Product::create([
            'name' => 'Menu test product',
            'slug' => 'menu-test-product',
            'sku' => 'MENU-TEST',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $order = ProductionOrder::create([
            'production_code' => 'MO-MENU-TEST',
            'product_id' => $product->id,
            'planned_quantity' => 10,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'released',
        ]);

        $this->actingAs($qa)->get(route('qa.production-batch-assignments.index'))
            ->assertOk()->assertSee('MO-MENU-TEST')->assertDontSee('Lệnh cũ chưa gắn lô');
        $this->get('/qa/production-output-lots')->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('qa.production-batch-assignments.index'))
            ->assertOk()->assertSee('MO-MENU-TEST')->assertSee('Xem lệnh');
        $this->actingAs($it)->get(route('production-orders.show', $order))
            ->assertOk()->assertDontSee('name="batch_number"', false)
            ->assertDontSee('name="actual_quantity"', false)
            ->assertDontSee('Lập phiếu xuất và trừ kho');
        $this->actingAs($it)->get(route('qa.production-batch-assignments.show', $order))
            ->assertOk()->assertSee('name="batch_number"', false)
            ->assertDontSee('Lập phiếu xuất và trừ kho')->assertDontSee('name="actual_quantity"', false);
        $this->actingAs($warehouse)->get(route('warehouse.production-issues.show', $order))
            ->assertOk()->assertSee('Chờ QA gán số lô thành phẩm hợp lệ')->assertDontSee('Lập phiếu xuất và trừ kho');
        $this->actingAs($warehouse)->post(route('production-orders.assign-batch-number', $order), ['batch_number' => 'MENU-LOT'])
            ->assertForbidden();
        $this->actingAs($qa)->post(route('production-orders.assign-batch-number', $order), ['batch_number' => 'MENU-LOT'])
            ->assertSessionHas('success');
        $this->get(route('qa.production-batch-assignments.index'))->assertDontSee('MO-MENU-TEST');

        $order->update(['status' => 'materials_issued']);
        $this->actingAs($it)->get(route('warehouse.production-issues.show', $order))
            ->assertOk()->assertSee('Lập phiếu xuất và trừ kho')
            ->assertDontSee('name="actual_quantity"', false)->assertDontSee('name="batch_number"', false);
        $this->actingAs($it)->get(route('production.work-orders.show', $order))
            ->assertOk()->assertSee('name="actual_quantity"', false)
            ->assertDontSee('Lập phiếu xuất và trừ kho')->assertDontSee('name="batch_number"', false);
        $this->actingAs($qa)->get(route('production.work-orders.show', $order))
            ->assertOk()->assertDontSee('name="actual_quantity"', false);
        $this->actingAs($production)->post(route('production-orders.confirm-completion', $order), [])
            ->assertForbidden();

        $order->update(['status' => 'production_reported', 'actual_quantity' => 10]);
        $this->actingAs($warehouse)->get(route('warehouse.production-issues'))
            ->assertOk()->assertSee('MO-MENU-TEST')->assertSee('Xác nhận nguyên liệu trả')
            ->assertSee(route('warehouse.production-issues.show', $order));
        $this->get(route('warehouse.production-issues.show', $order))
            ->assertOk()->assertSee('Xác nhận hoàn thành lệnh');
    }

    public function test_quality_assigns_one_batch_before_material_issue_and_production_records_output_to_that_batch(): void
    {
        $qa = User::factory()->create(['role' => 'qa']);
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $production = User::factory()->create(['role' => 'production']);
        $finishedProduct = Product::create([
            'name' => 'Finished herbal product',
            'slug' => 'finished-herbal-product',
            'sku' => 'FIN-HERBAL',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $rawProduct = Product::create([
            'name' => 'Raw herbal material',
            'slug' => 'raw-herbal-material',
            'sku' => 'RAW-HERBAL',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'MO-GMP-BATCH-1',
            'product_id' => $finishedProduct->id,
            'planned_quantity' => 4,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'released',
        ]);
        $rawBatch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $rawProduct->id,
            'batch_number' => 'RAW-HERBAL-LOT-1',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'status' => 'active',
        ]);

        $this->actingAs($warehouse)
            ->post(route('production-orders.issue-materials', $productionOrder), ['lots' => [$rawBatch->id => 2]])
            ->assertSessionHas('error', 'QA phải gán số lô thành phẩm cho lệnh trước khi Kho xuất nguyên liệu.');
        $this->assertSame(0, InventoryMovement::count());

        $this->actingAs($qa)
            ->post(route('production-orders.assign-batch-number', $productionOrder), [
                'batch_number' => 'HERBAL-2026-001',
            ])
            ->assertRedirect(route('qa.production-batch-assignments.show', $productionOrder))
            ->assertSessionHas('success');

        $batch = ProductionFinishedBatch::sole();
        $this->assertSame('planned', $batch->status);
        $this->assertSame('HERBAL-2026-001', $productionOrder->fresh()->provisional_batch_number);
        $this->assertSame($productionOrder->id, $batch->production_order_id);
        $this->assertSame($qa->id, $batch->batch_number_assigned_by);
        $this->assertNotNull($batch->batch_number_assigned_at);

        $this->actingAs($qa)
            ->post(route('production-orders.assign-batch-number', $productionOrder), [
                'batch_number' => 'HERBAL-2026-002',
            ])
            ->assertSessionHas('error');
        $this->assertSame(1, ProductionFinishedBatch::count());

        $this->actingAs($warehouse)
            ->post(route('production-orders.issue-materials', $productionOrder), ['lots' => [$rawBatch->id => 2]])
            ->assertSessionHas('success');
        $this->actingAs($production)
            ->post(route('production-orders.report-output', $productionOrder), [
                'actual_quantity' => 3.5,
                'mfg_date' => today()->toDateString(),
            ])
            ->assertRedirect(route('production.work-orders.show', $productionOrder))
            ->assertSessionHas('success');

        $batch->refresh();
        $this->assertSame('pending_qa', $batch->status);
        $this->assertSame('HERBAL-2026-001', $batch->batch_number);
        $this->assertEquals(3.5, $batch->initial_quantity);
        $this->assertEquals(3.5, $batch->pending_warehouse_quantity);
        $this->assertEquals(4, $batch->planned_quantity);
        $this->assertEquals(0, $productionOrder->fresh()->pending_finished_quantity);
        $this->assertSame($productionOrder->id, $batch->productionOrder->id);
        $this->assertNull($batch->fresh()->qc_result);

        $otherOrder = ProductionOrder::create([
            'production_code' => 'MO-GMP-BATCH-2',
            'product_id' => $finishedProduct->id,
            'planned_quantity' => 1,
            'actual_quantity' => 1,
            'pending_finished_quantity' => 1,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
        $this->actingAs($qa)
            ->post(route('qa.internal-lots.allocate-production-order', $batch), [
                'production_order_id' => $otherOrder->id,
                'quantity' => 0.5,
            ])
            ->assertSessionHas('error');
        $this->assertSame(1, $batch->orderAllocations()->count());
    }
}
