<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('warehouse_stock_checked_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('warehouse_stock_available_quantity', 12, 4)->nullable();
            $table->decimal('warehouse_stock_confirmed_quantity', 12, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['warehouse_stock_available_quantity', 'warehouse_stock_confirmed_quantity']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_stock_checked_by');
        });
    }
};
