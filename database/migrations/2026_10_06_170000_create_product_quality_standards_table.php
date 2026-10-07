<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_quality_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('standard_type');
            $table->string('indicator');
            $table->text('requirement');
            $table->text('method');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'standard_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_quality_standards');
    }
};