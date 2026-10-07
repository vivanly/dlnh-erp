<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('packaged_by');
            $table->dropColumn('packaged_at');
        });
    }

    public function down(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->timestamp('packaged_at')->nullable();
            $table->foreignId('packaged_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};