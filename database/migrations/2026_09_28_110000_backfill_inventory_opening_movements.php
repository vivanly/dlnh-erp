<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('supplier_batches')
            ->join('products', 'products.id', '=', 'supplier_batches.product_id')
            ->where('supplier_batches.current_quantity', '>', 0)
            ->select([
                'supplier_batches.id',
                'supplier_batches.product_id',
                'supplier_batches.current_quantity',
                'products.unit',
            ])
            ->orderBy('supplier_batches.id')
            ->chunk(500, function ($batches) use ($now) {
                $rows = [];
                foreach ($batches as $batch) {
                    $rows[] = [
                        'product_id' => $batch->product_id,
                        'supplier_batch_id' => $batch->id,
                        'movement_type' => 'OPENING_BALANCE',
                        'direction' => 'in',
                        'quantity' => $batch->current_quantity,
                        'unit' => $batch->unit ?: 'Kg',
                        'reference_type' => 'SupplierBatch',
                        'reference_id' => $batch->id,
                        'notes' => 'Tồn đầu kỳ khi triển khai sổ giao dịch kho.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows) {
                    DB::table('inventory_movements')->insert($rows);
                }
            });

        DB::table('internal_batches')
            ->join('products', 'products.id', '=', 'internal_batches.product_id')
            ->where('internal_batches.current_quantity', '>', 0)
            ->select([
                'internal_batches.id',
                'internal_batches.product_id',
                'internal_batches.current_quantity',
                'products.unit',
            ])
            ->orderBy('internal_batches.id')
            ->chunk(500, function ($batches) use ($now) {
                $rows = [];
                foreach ($batches as $batch) {
                    $rows[] = [
                        'product_id' => $batch->product_id,
                        'internal_batch_id' => $batch->id,
                        'movement_type' => 'OPENING_BALANCE',
                        'direction' => 'in',
                        'quantity' => $batch->current_quantity,
                        'unit' => $batch->unit ?: 'Kg',
                        'reference_type' => 'InternalBatch',
                        'reference_id' => $batch->id,
                        'notes' => 'Tồn đầu kỳ khi triển khai sổ giao dịch kho.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows) {
                    DB::table('inventory_movements')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        DB::table('inventory_movements')
            ->where('movement_type', 'OPENING_BALANCE')
            ->whereIn('reference_type', ['SupplierBatch', 'InternalBatch'])
            ->delete();
    }
};