<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['excipients', 'packaging_materials'] as $tableName) {
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

        Schema::table('product_bom_items', function (Blueprint $table) {
            $table->foreignId('component_product_id')->nullable()->change();
            $table->string('component_type')->nullable()->after('component_product_id');
            $table->unsignedBigInteger('component_id')->nullable()->after('component_type');
            $table->index(['component_type', 'component_id'], 'product_bom_items_component_idx');
        });

        DB::table('product_bom_items')
            ->whereNotNull('component_product_id')
            ->update([
                'component_type' => Product::class,
                'component_id' => DB::raw('component_product_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('product_bom_items', function (Blueprint $table) {
            $table->dropIndex('product_bom_items_component_idx');
            $table->dropColumn(['component_type', 'component_id']);
        });

        Schema::dropIfExists('packaging_materials');
        Schema::dropIfExists('excipients');
    }
};
