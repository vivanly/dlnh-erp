<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo bảng material_lot_inspections để lưu kết quả kiểm tra
        if (!Schema::hasTable('material_lot_inspections')) {
            Schema::create('material_lot_inspections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_lot_id')->constrained('material_lots')->cascadeOnDelete();
                $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('result', 24)->default('pending');
                $table->text('note')->nullable();
                $table->timestamp('checked_at')->nullable();
                // Các cột mới cho quy trình chi tiết
                $table->string('inspection_stage')->default('qc_inspection');
                $table->text('detailed_findings')->nullable();
                $table->text('corrective_action')->nullable();
                $table->timestamps();
            });
        }

        // Thêm cột vào material_lots để quản lý kho tạm
        Schema::table('material_lots', function (Blueprint $table) {
            if (!Schema::hasColumn('material_lots', 'temporary_location_code')) {
                $table->string('temporary_location_code')->nullable()->after('status');
            }
            if (!Schema::hasColumn('material_lots', 'temporary_stored_at')) {
                $table->timestamp('temporary_stored_at')->nullable()->after('temporary_location_code');
            }
            if (!Schema::hasColumn('material_lots', 'temporary_stored_by')) {
                $table->foreignId('temporary_stored_by')->nullable()->after('temporary_stored_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('material_lots', 'qa_reviewed_at')) {
                $table->timestamp('qa_reviewed_at')->nullable()->after('qc_at');
            }
            if (!Schema::hasColumn('material_lots', 'qa_reviewed_by')) {
                $table->foreignId('qa_reviewed_by')->nullable()->after('qa_reviewed_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('material_lots', function (Blueprint $table) {
            if (Schema::hasColumn('material_lots', 'temporary_location_code')) {
                $table->dropColumn('temporary_location_code');
            }
            if (Schema::hasColumn('material_lots', 'temporary_stored_at')) {
                $table->dropColumn('temporary_stored_at');
            }
            if (Schema::hasColumn('material_lots', 'temporary_stored_by')) {
                $table->dropConstrainedForeignId('temporary_stored_by');
            }
            if (Schema::hasColumn('material_lots', 'qa_reviewed_at')) {
                $table->dropColumn('qa_reviewed_at');
            }
            if (Schema::hasColumn('material_lots', 'qa_reviewed_by')) {
                $table->dropConstrainedForeignId('qa_reviewed_by');
            }
        });

        Schema::dropIfExists('material_lot_inspections');
    }
};
