<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_label_production_orders', function (Blueprint $table) {
            $table->string('storage')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('manual_label_production_orders', function (Blueprint $table) {
            $table->dropColumn('storage');
        });
    }
};
