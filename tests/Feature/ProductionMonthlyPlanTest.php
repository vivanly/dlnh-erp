<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMonthlyPlan;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionMonthlyPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_plan_order_gets_batch_number_before_issue_and_tracks_output_in_same_lot(): void
    {
        $planner = User::factory()->create(['role' => 'planning']);
        $director = User::factory()->create(['role' => 'general_director']);
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $production = User::factory()->create(['role' => 'production']);
        $qa = User::factory()->create(['role' => 'qa']);
        $it = User::factory()->create(['role' => 'it']);
        $this->actingAs($it)
            ->get(route('production-monthly-plans.index'))
            ->assertOk()
            ->assertSee('Kế hoạch sản xuất tháng')
            ->assertSee('Lưu dự thảo');
        $this->post(route('production-monthly-plans.store'), [])->assertSessionHasErrors();
        $this->get(route('production-monthly-plans.approvals'))
            ->assertOk()
            ->assertDontSee(route('production-monthly-plans.approve', ['plan' => 1]));

        $finishedProduct = Product::create([
            'name' => 'Monthly finished product',
            'slug' => 'monthly-finished-product',
            'sku' => 'MONTHLY-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $rawProduct = Product::create([
            'name' => 'Monthly raw material',
            'slug' => 'monthly-raw-material',
            'sku' => 'MONTHLY-RAW',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $rawBatch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $rawProduct->id,
            'batch_number' => 'MONTHLY-RAW-LOT',
            'initial_quantity' => 20,
            'current_quantity' => 20,
            'status' => 'active',
        ]);

        $planMonth = now()->addMonth()->format('Y-m');
        $this->actingAs($planner)
            ->post(route('production-monthly-plans.store'), [
                'plan_month' => $planMonth,
                'lines' => [[
                    'product_id' => $finishedProduct->id,
                    'planned_quantity' => 5,
                    'notes' => 'Monthly demand',
                ]],
            ])
            ->assertRedirect(route('production-monthly-plans.index'))
            ->assertSessionHas('success');

        $plan = ProductionMonthlyPlan::with('lines')->sole();
        $this->actingAs($it)
            ->get(route('production-monthly-plans.index'))
            ->assertOk()
            ->assertSee($finishedProduct->name)
            ->assertSee('Gửi kế hoạch tháng này lên Ban Giám Đốc duyệt?', false);
        $this->get(route('production-monthly-plans.edit', $plan))->assertOk();
        $this->actingAs($planner)
            ->get(route('production-monthly-plans.index'))
            ->assertOk()
            ->assertSee('Kế hoạch sản xuất tháng')
            ->assertSee($finishedProduct->name);
        $this->actingAs($planner)
            ->post(route('production-monthly-plans.submit', $plan))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->actingAs($director)
            ->get(route('production-monthly-plans.approvals'))
            ->assertOk()
            ->assertSee($plan->plan_month->format('m/Y'))
            ->assertSee($finishedProduct->name)
            ->assertSee('Duyệt kế hoạch');
        $this->actingAs($director)
            ->post(route('production-monthly-plans.approve', $plan))
            ->assertRedirect()
            ->assertSessionHas('success');

        $productionOrder = ProductionOrder::with('materials')->sole();
        $this->assertNull($productionOrder->order_id);
        $this->assertNull($productionOrder->order_item_id);
        $this->assertSame('released', $productionOrder->status);
        $this->assertSame($plan->lines->first()->id, $productionOrder->production_monthly_plan_line_id);
        $this->assertSame(0, ProductionFinishedBatch::count());

        $this->assertSame(0, ProductionOrderMaterial::count());
        $this->assertNull($productionOrder->product_bom_id);
        $this->actingAs($qa)
            ->post(route('production-orders.assign-batch-number', $productionOrder), [
                'batch_number' => 'MONTHLY-FIN-LOT-1',
            ])
            ->assertRedirect(route('qa.production-batch-assignments.show', $productionOrder))
            ->assertSessionHas('success');
        $finishedBatch = ProductionFinishedBatch::sole();
        $this->assertSame($productionOrder->id, $finishedBatch->production_order_id);
        $this->assertSame('planned', $finishedBatch->status);

        $this->actingAs($warehouse)
            ->post(route('production-orders.issue-materials', $productionOrder), ['lots' => [$rawBatch->id => 5]])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->actingAs($warehouse)
            ->get(route('production-orders.show', $productionOrder))
            ->assertOk()
            ->assertDontSee('name="finished_batch_id"', false)
            ->assertSee('MONTHLY-FIN-LOT-1');
        $this->actingAs($production)
            ->post(route('production-orders.report-output', $productionOrder), [
                'actual_quantity' => 5,
                'mfg_date' => today()->toDateString(),
            ])
            ->assertRedirect(route('production.work-orders.show', $productionOrder))
            ->assertSessionHas('success');
        $this->actingAs($warehouse)
            ->post(route('production-orders.confirm-completion', $productionOrder), [])
            ->assertRedirect(route('warehouse.production-batches'))
            ->assertSessionHas('success');

        $finishedBatch->refresh();
        $this->assertSame('pending_qa', $finishedBatch->status);
        $this->assertEquals(5, $finishedBatch->pending_warehouse_quantity);
        $this->assertSame($productionOrder->id, $finishedBatch->production_order_id);
        $this->assertEquals(0, $productionOrder->fresh()->pending_finished_quantity);
        $this->assertEquals(5, $finishedBatch->inputs()->sum('consumed_quantity'));
        $this->assertSame($rawBatch->id, $finishedBatch->inputs()->first()->materialLot->supplier_batch_id);

        $this->actingAs($warehouse)
            ->get(route('warehouse.production-batches'))
            ->assertOk()
            ->assertSee('MONTHLY-FIN-LOT-1')
            ->assertSee($productionOrder->production_code);
        $this->actingAs($warehouse)
            ->post(route('warehouse.production-batches.receive', $finishedBatch), [
                'received_quantity' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $finishedBatch->refresh();
        $this->assertDatabaseHas('production_order_batch_allocations', [
            'production_order_id' => $productionOrder->id,
            'production_finished_batch_id' => $finishedBatch->id,
            'quantity' => 5,
            'received_quantity' => 5,
        ]);
        $this->assertEquals(5, $finishedBatch->inputs()->sum('consumed_quantity'));
        $this->assertSame($rawBatch->id, $finishedBatch->inputs()->first()->materialLot->supplier_batch_id);
        $this->assertEquals(0, $productionOrder->fresh()->pending_finished_quantity);
        $this->assertEquals(5, $finishedBatch->fresh()->current_quantity);
        $this->assertSame('active', $finishedBatch->fresh()->status);
        $this->assertEquals(0, $finishedBatch->fresh()->pending_warehouse_quantity);
        $this->assertEquals(5, InventoryMovement::where('movement_type', 'RECEIVE_PRODUCTION')->sum('quantity'));
        $this->assertEquals(15, $rawBatch->fresh()->current_quantity);
        $this->actingAs($warehouse)
            ->get(route('traceability.index', ['search' => $rawBatch->batch_number]))
            ->assertOk()
            ->assertSee($productionOrder->production_code)
            ->assertSee('MONTHLY-FIN-LOT-1')
            ->assertSee('5.0000');
    }
}
