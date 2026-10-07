<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            
            $table->decimal('quantity', 12, 2); // Số lượng đặt
            $table->decimal('received_quantity', 12, 2)->default(0); // Số lượng thực nhận sau này
            $table->string('unit'); // Đơn vị tính (Kg, gram, bao...)
            
            $table->decimal('unit_price', 15, 2); // Giá mua 1 đơn vị
            $table->decimal('total_price', 15, 2); // Thành tiền = số lượng * đơn giá
            
            // Các trường đặc thù dược liệu khi nhập kho thực tế
            $table->string('batch_number')->nullable(); // Số lô
            $table->date('expiry_date')->nullable();    // Hạn sử dụng
            $table->string('coa_reference')->nullable(); // Giấy kiểm định COA
            
            $table->timestamps();

            $table->index(['purchase_order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
