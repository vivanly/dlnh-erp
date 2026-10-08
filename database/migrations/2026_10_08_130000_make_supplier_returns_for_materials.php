<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_return_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_batch_id')->nullable()->change();
            $table->string('material_type', 30)->nullable()->after('supplier_batch_id');
            $table->unsignedBigInteger('material_id')->nullable()->after('material_type');
            $table->foreignId('purchase_order_item_id')->nullable()->after('material_id')->constrained('purchase_order_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_return_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_order_item_id');
            $table->dropColumn(['material_type', 'material_id']);
        });
    }
};