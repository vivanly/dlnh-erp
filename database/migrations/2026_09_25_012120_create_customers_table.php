<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Mã Khách Hàng
            $table->string('name');           // Tên Khách Hàng
            $table->string('type');           // Phân Loại (Lẻ, Thầu, Cửa Hàng, Bệnh Viện)
            $table->string('license_number')->nullable(); // Giấy Phép Kinh Doanh
            $table->string('phone')->nullable();          // Số Điện Thoại
            $table->text('address')->nullable();          // Địa Chỉ
            $table->string('email')->nullable();          // Email
            $table->text('note')->nullable();             // Ghi Chú
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
