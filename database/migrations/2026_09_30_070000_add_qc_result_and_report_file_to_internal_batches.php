<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('internal_batches', function (Blueprint $table) {
            $table->string('qc_result', 16)->nullable();
            $table->string('qc_test_report_file')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('internal_batches', function (Blueprint $table) {
            $table->dropColumn(['qc_result', 'qc_test_report_file']);
        });
    }
};