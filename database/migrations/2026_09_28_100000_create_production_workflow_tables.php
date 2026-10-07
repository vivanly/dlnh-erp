<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_orders')) {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('production_code')->unique();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_bom_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('planned_quantity', 15, 4);
            $table->decimal('actual_quantity', 15, 4)->nullable();
            $table->string('unit', 32);
            $table->decimal('yield_rate', 8, 5);
            $table->string('status', 32)->default('planned');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('materials_issued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });
        }

        if (!Schema::hasTable('production_order_materials')) {
        Schema::create('production_order_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_bom_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('required_quantity', 15, 4);
            $table->decimal('issued_quantity', 15, 4)->default(0);
            $table->string('unit', 32);
            $table->timestamps();
            $table->index(['production_order_id', 'product_id'], 'prod_order_materials_order_product_idx');
        });
        }

        if (!Schema::hasTable('production_material_lots')) {
        Schema::create('production_material_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_material_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('internal_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('allocated_quantity', 15, 4);
            $table->decimal('issued_quantity', 15, 4)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('production_finished_batches')) {
        Schema::create('production_finished_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('batch_number')->unique();
            $table->decimal('initial_quantity', 15, 4);
            $table->decimal('current_quantity', 15, 4);
            $table->string('unit', 32);
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('status', 32)->default('active');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['product_id', 'status', 'exp_date']);
        });
        }

        if (!Schema::hasTable('production_batch_inputs')) {
        Schema::create('production_batch_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_finished_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_material_lot_id')->constrained()->restrictOnDelete();
            $table->decimal('consumed_quantity', 15, 4);
            $table->string('unit', 32);
            $table->timestamps();
            $table->unique(['production_finished_batch_id', 'production_material_lot_id'], 'prod_batch_inputs_finished_lot_unique');
        });
        }

        if (!Schema::hasTable('sales_order_lot_allocations')) {
        Schema::create('sales_order_lot_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('internal_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('production_finished_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('reserved_quantity', 15, 4);
            $table->decimal('shipped_quantity', 15, 4)->default(0);
            $table->string('status', 24)->default('reserved');
            $table->timestamps();
            $table->index(['order_item_id', 'status']);
        });
        }

        if (!Schema::hasTable('inventory_movements')) {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('internal_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('production_finished_batch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('movement_type', 32);
            $table->string('direction', 8);
            $table->decimal('quantity', 15, 4);
            $table->string('unit', 32);
            $table->string('reference_type', 64)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['reference_type', 'reference_id']);
            $table->index(['product_id', 'movement_type', 'created_at']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('sales_order_lot_allocations');
        Schema::dropIfExists('production_batch_inputs');
        Schema::dropIfExists('production_finished_batches');
        Schema::dropIfExists('production_material_lots');
        Schema::dropIfExists('production_order_materials');
        Schema::dropIfExists('production_orders');
    }
};