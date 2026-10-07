<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->string('origin')->nullable();
            $table->foreignId('ppcb_id')->nullable()->constrained('ppcb')->nullOnDelete();
            $table->string('license_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('production_finished_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ppcb_id');
            $table->dropColumn(['origin', 'license_number']);
        });
    }
};
