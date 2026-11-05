<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_label_sources', function (Blueprint $table) {
            $table->string('scientific_name')->nullable()->after('name');
            $table->string('part_used')->nullable()->after('scientific_name');
            $table->string('origin')->nullable()->after('origin_code');
            $table->string('registration')->nullable()->after('origin');
        });
    }

    public function down(): void
    {
        Schema::table('manual_label_sources', function (Blueprint $table) {
            $table->dropColumn(['scientific_name', 'part_used', 'origin', 'registration']);
        });
    }
};
