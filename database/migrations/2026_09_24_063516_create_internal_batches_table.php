<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_batches', function (Blueprint $table) {
            $table->id();
            
            // Liên kết các bảng liên quan
            $table->foreignId('supplier_batch_id')->constrained('supplier_batches')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('ppcb_id')->constrained('ppcb')->onDelete('cascade');

            // Thông tin định danh lô nội bộ
            $table->string('internal_batch_code')->unique(); // Mã lô nội bộ
            
            // Quản lý số lượng
            $table->decimal('initial_quantity', 15, 2); // Số lượng ban đầu tách ra
            $table->decimal('current_quantity', 15, 2); // Số lượng tồn thực tế (dùng để trừ theo đơn hàng)

            // Thông tin QC và Pháp lý nội bộ
            $table->string('qc_test_report')->nullable();    // Phiếu kiểm nghiệm
            $table->date('qc_date')->nullable();             // Ngày ra phiếu kiểm nghiệm
            $table->string('license_number')->nullable();    // Số giấy phép

            // Ngày tháng & Hạn dùng
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();

            // Trạng thái lô: pending (chờ duyệt/lưu kho), active (dùng được)
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_batches');
    }
};