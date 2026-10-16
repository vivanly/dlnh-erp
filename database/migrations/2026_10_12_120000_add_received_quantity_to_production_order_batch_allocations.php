<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_order_batch_allocations', function (Blueprint $table) {
            $table->decimal('received_quantity', 15, 4)->default(0)->after('quantity');
            $table->boolean('is_receipt_linked')->default(false)->after('received_quantity');
        });

        DB::table('production_order_batch_allocations')
            ->join('production_finished_batches', 'production_finished_batches.id', '=', 'production_order_batch_allocations.production_finished_batch_id')
            ->whereColumn('production_finished_batches.production_order_id', 'production_order_batch_allocations.production_order_id')
            ->update(['production_order_batch_allocations.received_quantity' => DB::raw('LEAST(production_order_batch_allocations.quantity, COALESCE((SELECT SUM(im.quantity) FROM inventory_movements im WHERE im.production_finished_batch_id = production_finished_batches.id AND im.direction = "in"), 0))')]);

        DB::table('production_order_batch_allocations')
            ->join('production_orders', 'production_orders.id', '=', 'production_order_batch_allocations.production_order_id')
            ->whereNotNull('production_orders.production_monthly_plan_line_id')
            ->update(['production_order_batch_allocations.is_receipt_linked' => true]);
    }

    public function down(): void
    {
        Schema::table('production_order_batch_allocations', function (Blueprint $table) {
            $table->dropColumn(['received_quantity', 'is_receipt_linked']);
        });
    }
};
