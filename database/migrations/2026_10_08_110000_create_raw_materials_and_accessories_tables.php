<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['raw_materials', 'accessories'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('classification')->nullable();
                $table->string('sku')->unique();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('scientific_name')->nullable();
                $table->string('origin')->nullable();
                $table->string('part_used')->nullable();
                $table->string('unit')->nullable();
                $table->string('scientific_name_reference')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->string('material_type', 30)->nullable()->after('product_id');
            $table->unsignedBigInteger('material_id')->nullable()->after('material_type');
            $table->index(['material_type', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropIndex(['material_type', 'material_id']);
            $table->dropColumn(['material_type', 'material_id']);
        });

        Schema::dropIfExists('accessories');
        Schema::dropIfExists('raw_materials');
    }
};
