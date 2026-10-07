<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['production_material_lots', 'sales_order_lot_allocations', 'inventory_movements'] as $tableName) {
            if (Schema::hasColumn($tableName, 'internal_batch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('internal_batch_id');
                });
            }
        }

        foreach (['supplier_batches_id', 'internal_batches_id'] as $column) {
            if (Schema::hasColumn('order_items', $column)) {
                Schema::table('order_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('order_items', 'production_finished_batch_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('production_finished_batch_id');
            });
        }

        Schema::dropIfExists('internal_batches');
    }

    public function down(): void
    {
        Schema::create('internal_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_batch_id')->constrained('supplier_batches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('ppcb_id')->constrained('ppcb')->cascadeOnDelete();
            $table->string('internal_batch_code')->unique();
            $table->decimal('initial_quantity', 15, 2);
            $table->decimal('current_quantity', 15, 2);
            $table->string('qc_test_report')->nullable();
            $table->date('qc_date')->nullable();
            $table->string('license_number')->nullable();
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('qc_result', 24)->nullable();
            $table->string('qc_test_report_file')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->foreignId('internal_batch_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('sales_order_lot_allocations', function (Blueprint $table) {
            $table->foreignId('internal_batch_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('internal_batch_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('supplier_batches_id')->nullable();
            $table->unsignedBigInteger('internal_batches_id')->nullable();
            $table->foreignId('production_finished_batch_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
