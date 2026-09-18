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
        Schema::create('ppcb', function (Blueprint $table) {
            $table->id();
            $table->string('ma')->unique(); // Mã[cite: 1]
            $table->string('ten_ppcb'); // Tên PPCB[cite: 1]
            $table->text('chi_tiet_ppcb')->nullable(); // Chi tiết PPCB[cite: 1]
            $table->text('ghi_chu')->nullable(); // Ghi chú[cite: 1]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ppcb');
    }
};