<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_label_prints', function (Blueprint $table) {
            $table->id();
            $table->string('label_type', 16);
            $table->boolean('traditional_medicine_name')->default(false);
            $table->string('product_name');
            $table->string('batch');
            $table->string('scientific_name')->nullable();
            $table->string('mfg_date', 32)->nullable();
            $table->string('part_used')->nullable();
            $table->string('exp_date', 32)->nullable();
            $table->string('origin')->nullable();
            $table->string('net_weight', 64)->nullable();
            $table->string('registration')->nullable();
            $table->string('standard')->nullable();
            $table->string('storage')->nullable();
            $table->boolean('include_packaging_control')->default(false);
            $table->string('packaging_controller', 60)->nullable();
            $table->string('packaging_performer', 60)->nullable();
            $table->boolean('watermark')->default(false);
            $table->unsignedInteger('copies_count');
            $table->unsignedInteger('delivered_count')->default(0);
            $table->string('note')->nullable();
            $table->foreignId('printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('manual_label_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manual_label_print_id')->constrained('manual_label_prints')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('received_by')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_label_deliveries');
        Schema::dropIfExists('manual_label_prints');
    }
};
