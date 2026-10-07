<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->string('qc_test_report_file')->nullable()->after('qc_date');
        });
    }

    public function down(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropColumn('qc_test_report_file');
        });
    }
};