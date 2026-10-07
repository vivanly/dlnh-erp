<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->timestamp('warehouse_confirmed_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('warehouse_confirmed_at')->nullable();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('actual_quantity', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('actual_quantity');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('warehouse_confirmed_at');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('warehouse_confirmed_at');
        });
    }
};