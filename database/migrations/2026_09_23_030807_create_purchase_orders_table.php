<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique(); // Mã đơn mua (VD: PO-2026-0001)
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('order_date'); // Ngày đặt hàng
            $table->date('expected_delivery_date')->nullable(); // Ngày giao dự kiến
            
            // Trạng thái đơn hàng
            $table->string('status')->default('draft'); // draft, approved, completed, cancelled
            
            // Thông tin tài chính
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            
            $table->text('notes')->nullable(); // Ghi chú đơn hàng
            $table->timestamps();

            $table->index(['supplier_id', 'status']);
            $table->index(['status', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
