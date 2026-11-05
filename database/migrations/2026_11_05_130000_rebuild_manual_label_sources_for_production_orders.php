<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('manual_label_order_lines');
        Schema::dropIfExists('manual_label_sources');
        Schema::dropIfExists('manual_label_catalog');

        Schema::create('manual_label_products', function (Blueprint $table) {
            $table->id();
            $table->string('name_key')->unique();
            $table->string('name');
            $table->string('category', 64)->nullable();
            $table->string('scientific_name')->nullable();
            $table->string('standard')->nullable();
            $table->string('origin')->nullable();
            $table->string('part_used')->nullable();
            $table->string('registration')->nullable();
            $table->timestamps();
        });

        Schema::create('manual_label_production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('label_code', 32)->unique();
            $table->date('order_date')->nullable();
            $table->string('production_order', 64)->nullable()->index();
            $table->string('name')->index();
            $table->string('batch', 100)->nullable()->index();
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('pack_spec', 64)->nullable();
            $table->string('quantity', 64)->nullable();
            $table->unsignedInteger('label_count')->nullable();
            $table->timestamps();
        });

        Schema::table('manual_label_prints', function (Blueprint $table) {
            $table->string('label_code', 32)->nullable()->index()->after('order_code');
        });
    }

    public function down(): void
    {
        Schema::table('manual_label_prints', function (Blueprint $table) {
            $table->dropColumn('label_code');
        });
        Schema::dropIfExists('manual_label_production_orders');
        Schema::dropIfExists('manual_label_products');
    }
};
