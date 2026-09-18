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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
			$table->string('name'); // Tên phòng ban (Ví dụ: Kinh doanh, Kho, Sản xuất...)
			$table->string('code')->unique(); // Mã phòng ban viết tắt (Ví dụ: KD, KHO, SX...)
			$table->text('description')->nullable(); // Mô tả chi tiết chức năng phòng ban
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
