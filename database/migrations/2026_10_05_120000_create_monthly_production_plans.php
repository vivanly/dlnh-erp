<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
            $table->dropForeign(['order_item_id']);
            $table->foreignId('order_id')->nullable()->change();
            $table->foreignId('order_item_id')->nullable()->change();
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->restrictOnDelete();
        });

        Schema::create('production_monthly_plans', function (Blueprint $table) {
            $table->id();
            $table->date('plan_month');
            $table->string('status', 24)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique('plan_month');
        });

        Schema::create('production_monthly_plan_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_monthly_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_bom_id')->constrained()->restrictOnDelete();
            $table->decimal('planned_quantity', 15, 4);
            $table->string('unit', 32);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['production_monthly_plan_id', 'product_id'], 'monthly_plan_product_unique');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignId('production_monthly_plan_line_id')
                ->nullable()
                ->after('order_item_id')
                ->constrained('production_monthly_plan_lines')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_monthly_plan_line_id');
        });
        Schema::dropIfExists('production_monthly_plan_lines');
        Schema::dropIfExists('production_monthly_plans');
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->restrictOnDelete();
        });
    }
};
