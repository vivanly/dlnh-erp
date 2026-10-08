<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_order_batch_allocations')) {
            Schema::create('production_order_batch_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('production_order_id');
                $table->unsignedBigInteger('production_finished_batch_id');
                $table->decimal('quantity', 15, 4);
                $table->timestamps();
                $table->unique(['production_order_id', 'production_finished_batch_id'], 'po_batch_allocation_unique');
                $table->index(['production_finished_batch_id', 'production_order_id'], 'po_batch_allocation_lookup_idx');
            });
        }

        Schema::table('production_order_batch_allocations', function (Blueprint $table) {
            $table->foreign('production_order_id', 'po_batch_alloc_order_fk')
                ->references('id')->on('production_orders')->cascadeOnDelete();
            $table->foreign('production_finished_batch_id', 'po_batch_alloc_batch_fk')
                ->references('id')->on('production_finished_batches')->cascadeOnDelete();
        });

        DB::table('production_finished_batches')
            ->whereNotNull('production_order_id')
            ->where('initial_quantity', '>', 0)
            ->orderBy('id')
            ->get(['id', 'production_order_id', 'initial_quantity', 'created_at', 'updated_at'])
            ->each(function ($batch) {
                DB::table('production_order_batch_allocations')->insert([
                    'production_order_id' => $batch->production_order_id,
                    'production_finished_batch_id' => $batch->id,
                    'quantity' => $batch->initial_quantity,
                    'created_at' => $batch->created_at,
                    'updated_at' => $batch->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_batch_allocations');
    }
};
