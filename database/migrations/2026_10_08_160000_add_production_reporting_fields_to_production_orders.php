<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->timestamp('production_reported_at')->nullable();
            $table->foreignId('production_reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('warehouse_confirmed_at')->nullable();
            $table->foreignId('warehouse_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->decimal('reported_returned_quantity', 15, 4)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_reported_by');
            $table->dropConstrainedForeignId('warehouse_confirmed_by');
            $table->dropColumn(['production_reported_at', 'warehouse_confirmed_at']);
        });

        Schema::table('production_material_lots', function (Blueprint $table) {
            $table->dropColumn('reported_returned_quantity');
        });
    }
};
