<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_label_prints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id');
            $table->foreign('order_item_id', 'sales_label_print_item_fk')
                ->references('id')->on('order_items')->cascadeOnDelete();
            $table->foreignId('sales_order_lot_allocation_id');
            $table->foreign('sales_order_lot_allocation_id', 'sales_label_print_alloc_fk')
                ->references('id')->on('sales_order_lot_allocations')->cascadeOnDelete();
            $table->unsignedInteger('copies_count');
            $table->string('print_kind', 16)->default('required');
            $table->string('reason', 255)->nullable();
            $table->foreignId('printed_by')->nullable();
            $table->foreign('printed_by', 'sales_label_print_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->index('order_item_id', 'sales_label_print_item_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_label_prints');
    }
};