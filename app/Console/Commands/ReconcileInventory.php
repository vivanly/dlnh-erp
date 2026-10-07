<?php

namespace App\Console\Commands;

use App\Services\InventoryLedger;
use Illuminate\Console\Command;

class ReconcileInventory extends Command
{
    protected $signature = 'inventory:reconcile';

    protected $description = 'Compare current batch quantities with posted inventory movements';

    public function handle(InventoryLedger $inventoryLedger): int
    {
        $mismatches = $inventoryLedger->reconcile();

        if ($mismatches->isEmpty()) {
            $this->info('Inventory is reconciled: every batch matches its movement ledger.');

            return self::SUCCESS;
        }

        $this->table(
            ['Batch type', 'ID', 'Batch code', 'Product', 'Current', 'Ledger', 'Difference'],
            $mismatches->map(fn (array $row) => [
                $row['batch_type'],
                $row['batch_id'],
                $row['batch_code'],
                $row['product'],
                number_format($row['current_quantity'], 4, '.', ''),
                number_format($row['ledger_quantity'], 4, '.', ''),
                number_format($row['difference'], 4, '.', ''),
            ])->all(),
        );

        $this->error("Found {$mismatches->count()} batch balance mismatch(es). No stock was changed.");

        return self::FAILURE;
    }
}
