<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->onDelete('cascade'); // Thuộc phiếu nhận hàng nào
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items')->onDelete('cascade'); // Thuộc dòng chi tiết PO nào
            $table->foreignId('herb_id')->constrained('products')->restrictOnDelete(); // Mã dược liệu
            $table->decimal('ordered_quantity', 10, 2); // Số lượng theo PO ban đầu
            $table->decimal('received_quantity', 10, 2); // Số lượng thực tế nhận đạt chuẩn (đưa vào kho)
            $table->decimal('returned_quantity', 10, 2)->default(0); // Số lượng trả lại do lỗi/không đạt QC
            $table->text('return_reason')->nullable(); // Lý do trả lại sản phẩm lỗi
            $table->timestamps();
            $table->index(['purchase_order_item_id', 'herb_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
