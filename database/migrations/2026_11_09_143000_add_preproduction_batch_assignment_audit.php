<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->timestamp('batch_number_assigned_at')->nullable();
            $table->foreignId('batch_number_assigned_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('batch_number_assigned_by');
            $table->dropColumn('batch_number_assigned_at');
        });
    }
};
