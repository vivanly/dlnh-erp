<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->foreignId('production_order_id')->nullable()->change();
            $table->foreign('production_order_id')
                ->references('id')
                ->on('production_orders')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->unsignedBigInteger('production_order_id')->nullable(false)->change();
            $table->foreign('production_order_id')
                ->references('id')
                ->on('production_orders')
                ->restrictOnDelete();
        });
    }
};
