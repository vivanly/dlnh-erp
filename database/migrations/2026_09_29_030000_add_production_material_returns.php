<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->decimal('consumed_quantity', 15, 4)->default(0);
        });

        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->decimal('returned_quantity', 15, 4)->default(0);
        });

        DB::table('production_order_materials')->update([
            'consumed_quantity' => DB::raw('issued_quantity'),
        ]);
    }

    public function down(): void
    {
        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->dropColumn('returned_quantity');
        });

        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->dropColumn('consumed_quantity');
        });
    }
};