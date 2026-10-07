<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('sales_approved_at')->nullable();
            $table->foreignId('sales_approved_by')->nullable();
            $table->foreign('sales_approved_by', 'orders_sales_approved_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->text('sales_rejection_reason')->nullable();
        });

        DB::table('orders')
            ->where('status', 'pending_warehouse_check')
            ->update(['sales_approved_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_sales_approved_user_fk');
            $table->dropColumn(['sales_approved_at', 'sales_approved_by', 'sales_rejection_reason']);
        });
    }
};