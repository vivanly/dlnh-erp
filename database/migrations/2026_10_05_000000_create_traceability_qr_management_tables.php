<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traceability_settings', function (Blueprint $table) {
            $table->id();
            $table->string('base_url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('traceability_lot_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_batch_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('production_finished_batch_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('trace_code', 255)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traceability_lot_codes');
        Schema::dropIfExists('traceability_settings');
    }
};
