<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_return_orders', function (Blueprint $table) {
            $table->id();
            $table->string('return_code')->nullable()->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_batch_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->string('unit', 32);
            $table->text('reason');
            $table->string('qc_test_report', 255);
            $table->date('qc_date');
            $table->string('status', 32)->default('pending_dispatch');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['supplier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_return_orders');
    }
};