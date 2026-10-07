<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_monthly_plan_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('product_bom_id')->nullable()->change();
            $table->foreignId('ppcb_id')->nullable()->after('product_id')->constrained('ppcb')->nullOnDelete();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('product_bom_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_monthly_plan_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ppcb_id');
        });
    }
};
