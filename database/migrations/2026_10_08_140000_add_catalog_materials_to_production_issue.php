<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->string('material_type', 30)->nullable()->after('product_id');
            $table->unsignedBigInteger('material_id')->nullable()->after('material_type');
            $table->index(['production_order_id', 'material_type', 'material_id'], 'prod_order_materials_catalog_idx');
        });

        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('supplier_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->dropColumn('batch_number');
        });

        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->dropIndex('prod_order_materials_catalog_idx');
            $table->dropColumn(['material_type', 'material_id']);
        });
    }
};