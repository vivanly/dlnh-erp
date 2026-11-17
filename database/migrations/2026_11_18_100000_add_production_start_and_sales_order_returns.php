<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->timestamp('production_started_at')->nullable()->after('materials_issued_at');
        });

        Schema::create('sales_order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->string('status', 32)->default('pending_inspection');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('sales_order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_order_lot_allocation_id')->constrained()->restrictOnDelete();
            $table->foreignId('production_finished_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('status', 32)->default('pending_inspection');
            $table->text('inspection_note')->nullable();
            $table->foreignId('inspected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->foreignId('rework_production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->timestamps();
            $table->index(['order_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_return_items');
        Schema::dropIfExists('sales_order_returns');

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('production_started_at');
        });
    }
};
