<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 12, 2);                        // Số lượng đặt
            $table->string('packaging_spec')->nullable();              // Quy Cách Đóng Gói
            $table->decimal('finished_quantity', 12, 2)->nullable();   // Số Lượng Thành Phẩm
            $table->foreignId('ppcb_id')->nullable()->constrained('ppcb')->nullOnDelete();
            $table->unsignedBigInteger('supplier_batches_id')->nullable(); // ID Lô nhà cung cấp (QA chỉ định)
            $table->unsignedBigInteger('internal_batches_id')->nullable(); // ID Lô nội bộ (QA chỉ định)
            $table->text('notes')->nullable();                         // Ghi chú
            
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};