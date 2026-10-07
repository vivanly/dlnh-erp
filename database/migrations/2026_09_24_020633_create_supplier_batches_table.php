<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_item_id');
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('batch_number'); // Số lô của nhà cung cấp
            $table->decimal('initial_quantity', 10, 2); // Số lượng ban đầu (đồng bộ với received_quantity)
            $table->decimal('current_quantity', 10, 2); // Số lượng tồn thực tế của lô này
            $table->date('mfg_date')->nullable(); // Ngày sản xuất
            $table->date('exp_date')->nullable(); // Hạn sử dụng
            $table->string('coa_file')->nullable(); // Đường dẫn file PDF COA (QA cập nhật sau)
            $table->string('status')->default('active'); // Trạng thái: active, depleted,...
            $table->timestamps();
            $table->index(['product_id', 'status', 'exp_date']);
            $table->index(['goods_receipt_item_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_batches');
    }
};