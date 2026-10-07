<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_order_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('product_bom_item_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows created without a BOM item cannot be restored to a required column.
    }
};
