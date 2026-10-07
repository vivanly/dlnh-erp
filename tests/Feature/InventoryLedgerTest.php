<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SupplierBatch;
use App\Services\InventoryLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_ledger_posts_stock_and_reports_manual_balance_drift(): void
    {
        $product = Product::create([
            'name' => 'Ledger reconciliation product',
            'slug' => 'ledger-reconciliation-product',
            'sku' => 'LEDGER-RECON-1',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'LEDGER-RECON-LOT-1',
            'initial_quantity' => 10,
            'current_quantity' => 0,
            'status' => 'active',
        ]);
        $ledger = app(InventoryLedger::class);

        $ledger->post($batch, 'RECEIVE_PURCHASE', 'in', 10, 'kg');
        $this->assertEquals(10, $batch->fresh()->current_quantity);
        $this->assertCount(0, $ledger->reconcile());

        $batch->decrement('current_quantity', 1);
        $mismatches = $ledger->reconcile();
        $this->assertCount(1, $mismatches);
        $this->assertSame('LEDGER-RECON-LOT-1', $mismatches->sole()['batch_code']);
        $this->assertEquals(-1, $mismatches->sole()['difference']);

        Artisan::call('inventory:reconcile');
        $this->assertStringContainsString('LEDGER-RECON-LOT-1', Artisan::output());
        $this->assertEquals(9, $batch->fresh()->current_quantity);
    }
}
