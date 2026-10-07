<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete(); // Thuộc đơn mua nào (PO)
            $table->string('receipt_code')->unique(); // Mã phiếu nhận (Ví dụ: GRN-20260606-001)
            $table->date('receipt_date'); // Ngày thực tế nhận hàng
            $table->foreignId('receiver_id')->constrained('users')->restrictOnDelete(); // Nhân viên kho/thủ kho tiếp nhận
            $table->enum('qc_status', ['pending', 'passed', 'failed', 'partially_passed'])->default('pending'); // Trạng thái kiểm định QC
            $table->text('notes')->nullable(); // Ghi chú chung
            $table->timestamps();
            $table->index(['purchase_order_id', 'receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};
