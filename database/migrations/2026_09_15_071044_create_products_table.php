<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
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
            $table->string('gtin')->nullable()->unique();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};