<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('unit', 32);
            $table->decimal('to_base_factor', 20, 10);
            $table->timestamps();
            $table->unique(['product_id', 'unit']);
        });

        Schema::create('product_boms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->decimal('output_quantity', 15, 4)->default(1);
            $table->string('output_unit', 32);
            $table->decimal('yield_rate', 8, 5)->default(1);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'version']);
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_bom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('unit', 32);
            $table->timestamps();
            $table->index(['product_bom_id', 'component_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bom_items');
        Schema::dropIfExists('product_boms');
        Schema::dropIfExists('product_unit_conversions');
    }
};