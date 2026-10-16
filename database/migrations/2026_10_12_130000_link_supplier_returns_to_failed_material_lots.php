<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_return_orders', function (Blueprint $table) {
            $table->foreignId('material_lot_id')
                ->nullable()
                ->after('purchase_order_item_id')
                ->constrained('material_lots')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_return_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('material_lot_id');
        });
    }
};
