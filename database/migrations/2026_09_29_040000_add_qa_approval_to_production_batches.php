<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->string('provisional_batch_number')->nullable()->after('batch_number');
            $table->timestamp('qa_approved_at')->nullable()->after('received_by');
            $table->foreignId('qa_approved_by')->nullable()->after('qa_approved_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('provisional_batch_number')->nullable()->after('planned_quantity');
        });

        Schema::table('sales_order_lot_allocations', function (Blueprint $table) {
            $table->timestamp('label_printed_at')->nullable();
            $table->foreignId('label_printed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('production_batch_code_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_finished_batch_id');
            $table->foreign('production_finished_batch_id', 'prod_batch_code_hist_batch_fk')
                ->references('id')->on('production_finished_batches')->cascadeOnDelete();
            $table->string('old_batch_number');
            $table->string('new_batch_number');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batch_code_histories');

        Schema::table('sales_order_lot_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('label_printed_by');
            $table->dropColumn('label_printed_at');
        });

        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('qa_approved_by');
            $table->dropColumn(['provisional_batch_number', 'qa_approved_at']);
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn('provisional_batch_number');
        });
    }
};