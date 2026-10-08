<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('material_type', 30);
            $table->unsignedBigInteger('material_id');
            $table->string('movement_type', 30); // RECEIVE_PURCHASE, REJECT_PURCHASE, ISSUE_SALE
            $table->string('direction', 10); // in, out, none
            $table->decimal('quantity', 14, 4);
            $table->string('unit', 30)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['material_type', 'material_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->unsignedBigInteger('raw_material_id')->nullable()->after('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('raw_material_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('raw_material_id');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('raw_material_id');
        });
        Schema::dropIfExists('material_stock_movements');
    }
};
