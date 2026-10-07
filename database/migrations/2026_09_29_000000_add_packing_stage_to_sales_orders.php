<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('warehouse_packed_at')->nullable();
            $table->timestamp('qa_confirmed_at')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('packed_quantity', 15, 4)->default(0);
            $table->foreignId('production_finished_batch_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_finished_batch_id');
            $table->dropColumn('packed_quantity');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['warehouse_packed_at', 'qa_confirmed_at']);
        });
    }
};