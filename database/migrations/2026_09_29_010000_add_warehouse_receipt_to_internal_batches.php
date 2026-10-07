<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_batches', function (Blueprint $table) {
            $table->decimal('initial_quantity', 15, 4)->change();
            $table->decimal('current_quantity', 15, 4)->change();
        });

        Schema::table('internal_batches', function (Blueprint $table) {
            $table->decimal('initial_quantity', 15, 4)->change();
            $table->decimal('current_quantity', 15, 4)->change();
            $table->decimal('planned_quantity', 15, 4)->nullable();
            $table->decimal('source_issued_quantity', 15, 4)->nullable();
            $table->timestamp('warehouse_received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('internal_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by');
            $table->dropColumn(['planned_quantity', 'source_issued_quantity', 'warehouse_received_at']);
            $table->decimal('initial_quantity', 15, 2)->change();
            $table->decimal('current_quantity', 15, 2)->change();
        });

        Schema::table('supplier_batches', function (Blueprint $table) {
            $table->decimal('initial_quantity', 10, 2)->change();
            $table->decimal('current_quantity', 10, 2)->change();
        });
    }
};