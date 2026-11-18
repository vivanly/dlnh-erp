<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_receipts', function (Blueprint $table) {
            $table->foreignId('preliminary_qa_by')->nullable()->change();
            $table->foreignId('preliminary_qc_by')->nullable()->change();
        });

        Schema::table('material_lot_inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('material_lot_inspections', 'report_number')) {
                $table->string('report_number')->nullable();
            }
            if (!Schema::hasColumn('material_lot_inspections', 'report_date')) {
                $table->date('report_date')->nullable();
            }
            if (!Schema::hasColumn('material_lot_inspections', 'report_file')) {
                $table->string('report_file')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_lot_inspections', function (Blueprint $table) {
            $columns = array_filter(
                ['report_number', 'report_date', 'report_file'],
                fn (string $column) => Schema::hasColumn('material_lot_inspections', $column)
            );
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
