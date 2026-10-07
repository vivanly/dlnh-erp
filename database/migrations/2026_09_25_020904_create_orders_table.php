<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();                    // Mã Đơn Hàng
            $table->unsignedBigInteger('customer_id');                 // Mã Khách Hàng (Liên kết bảng customers)
            $table->string('order_type');                              // Loại Đơn Hàng
            $table->date('order_date');                                // Ngày Nhận Đơn Hàng
            $table->date('delivery_date');                             // Ngày Hẹn Giao
            $table->string('province_city');                           // Địa Chỉ (Tỉnh/Thành phố)
            $table->string('contact_person')->nullable();              // Người thông tin về đơn hàng
            $table->text('notes')->nullable();                         // Ghi chú đơn hàng
            $table->string('status')->default('pending_qa');           // Trạng Thái
            
            $table->timestamps();

            // Khóa ngoại liên kết với bảng customers
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};