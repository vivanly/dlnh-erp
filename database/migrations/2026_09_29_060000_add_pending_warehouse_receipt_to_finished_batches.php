<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->decimal('pending_warehouse_quantity', 15, 4)->default(0)->after('current_quantity');
            $table->timestamp('warehouse_received_at')->nullable();
            $table->foreignId('warehouse_received_by')->nullable();
            $table->foreign('warehouse_received_by', 'prod_finished_recv_user_fk')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropForeign('prod_finished_recv_user_fk');
            $table->dropColumn(['pending_warehouse_quantity', 'warehouse_received_at', 'warehouse_received_by']);
        });
    }
};