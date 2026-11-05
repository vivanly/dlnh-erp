<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_label_order_lines', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 32)->index();
            $table->string('order_type', 8)->nullable();
            $table->string('customer')->nullable();
            $table->string('customer_type')->nullable();
            $table->date('received_date')->nullable();
            $table->text('delivery_date')->nullable();
            $table->unsignedInteger('line_no')->nullable();
            $table->string('name');
            $table->string('unit', 16)->nullable();
            $table->string('origin_code', 64)->nullable();
            $table->decimal('quantity', 12, 3)->nullable();
            $table->decimal('pack_weight', 12, 3)->nullable();
            $table->unsignedInteger('pack_count')->nullable();
            $table->text('processing')->nullable();
            $table->text('note')->nullable();
            $table->string('batch', 100)->nullable();
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->timestamps();
        });

        Schema::create('manual_label_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('name_key')->unique();
            $table->string('name');
            $table->string('scientific_name')->nullable();
            $table->string('part_used')->nullable();
            $table->string('origin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_label_catalog');
        Schema::dropIfExists('manual_label_order_lines');
    }
};
