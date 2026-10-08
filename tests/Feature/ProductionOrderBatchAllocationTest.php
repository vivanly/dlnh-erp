<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionOrderBatchAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_order_can_fill_multiple_lots_and_one_lot_can_combine_orders(): void
    {
        $qa = User::factory()->create(['role' => 'it']);
        $product = Product::create([
            'name' => 'Allocation test product',
            'slug' => 'allocation-test-product',
            'sku' => 'ALLOC-TEST',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $firstOrder = $this->completedOrder($product, 'MO-ALLOC-1', 500);
        $secondOrder = $this->completedOrder($product, 'MO-ALLOC-2', 50);

        foreach ([['ALLOC-LOT-1', 200], ['ALLOC-LOT-2', 200]] as [$batchNumber, $quantity]) {
            $this->actingAs($qa)->post(route('qa.internal-lots.store'), [
                'product_id' => $product->id,
                'batch_number' => $batchNumber,
                'planned_quantity' => 200,
                'production_order_allocations' => [$firstOrder->id => $quantity],
            ])->assertRedirect(route('qa.internal-lots.index'))->assertSessionHas('success');
        }

        $combinedLot = ProductionFinishedBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'ALLOC-LOT-3',
            'provisional_batch_number' => 'ALLOC-LOT-3',
            'planned_quantity' => 200,
            'initial_quantity' => 0,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 0,
            'unit' => 'kg',
            'status' => 'pending_qa',
        ]);
        $this->actingAs($qa)->post(route('qa.internal-lots.allocate-production-order', $combinedLot), [
            'production_order_id' => $firstOrder->id,
            'quantity' => 100,
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($qa)->post(route('qa.internal-lots.allocate-production-order', $combinedLot), [
            'production_order_id' => $secondOrder->id,
            'quantity' => 50,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(0.0, (float) $firstOrder->fresh()->pending_finished_quantity);
        $this->assertSame(0.0, (float) $secondOrder->fresh()->pending_finished_quantity);
        $this->assertEquals(150, $combinedLot->fresh()->initial_quantity);
        $this->assertEquals(150, $combinedLot->fresh()->pending_warehouse_quantity);
        $this->assertEquals(150, $combinedLot->orderAllocations()->sum('quantity'));
        $this->assertCount(2, $combinedLot->allocatedProductionOrders);
        $this->assertEquals(500, $firstOrder->batchAllocations()->sum('quantity'));

        $this->actingAs($qa)->get(route('qa.internal-lots.index'))
            ->assertOk()
            ->assertSee('ALLOC-LOT-3')
            ->assertSee('MO-ALLOC-1')
            ->assertSee('MO-ALLOC-2');
        $formOrder = $this->completedOrder($product, 'MO-ALLOC-FORM', 10);
        $this->actingAs($qa)->get(route('qa.internal-lots.create', ['production_order_id' => $formOrder->id]))
            ->assertOk()
            ->assertSee('production_order_allocations['.$formOrder->id.']', false);

        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $this->actingAs($warehouse)->post(route('warehouse.production-batches.receive', $combinedLot), [
            'received_quantity' => 20,
        ])->assertRedirect()->assertSessionHas('success');
        $thirdOrder = $this->completedOrder($product, 'MO-ALLOC-3', 50);
        $this->actingAs($qa)->post(route('qa.internal-lots.allocate-production-order', $combinedLot), [
            'production_order_id' => $thirdOrder->id,
            'quantity' => 25,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertEquals(155, $combinedLot->fresh()->pending_warehouse_quantity);
        $this->assertEquals(175, $combinedLot->fresh()->initial_quantity);
        $this->actingAs($warehouse)->get(route('warehouse.production-batches'))
            ->assertOk()
            ->assertSee('MO-ALLOC-1')
            ->assertSee('MO-ALLOC-2')
            ->assertSee('MO-ALLOC-3');
    }

    private function completedOrder(Product $product, string $code, float $quantity): ProductionOrder
    {
        return ProductionOrder::create([
            'production_code' => $code,
            'product_id' => $product->id,
            'planned_quantity' => $quantity,
            'actual_quantity' => $quantity,
            'pending_finished_quantity' => $quantity,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
    }
}
