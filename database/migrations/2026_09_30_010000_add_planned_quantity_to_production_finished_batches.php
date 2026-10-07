<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->decimal('planned_quantity', 15, 4)->nullable()->after('provisional_batch_number');
            $table->string('qc_test_report')->nullable()->after('qa_approved_by');
            $table->date('qc_date')->nullable()->after('qc_test_report');
        });
    }

    public function down(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropColumn(['planned_quantity', 'qc_test_report', 'qc_date']);
        });
    }
};