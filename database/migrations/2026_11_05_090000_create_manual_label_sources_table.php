<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_label_sources', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 8)->nullable();
            $table->string('item_code', 64)->nullable();
            $table->string('name');
            $table->string('origin_code', 64)->nullable();
            $table->string('batch', 100);
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->timestamps();

            $table->unique(['batch', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_label_sources');
    }
};
