<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_label_prints', function (Blueprint $table) {
            $table->text('qr_content')->nullable()->after('storage');
        });
    }

    public function down(): void
    {
        Schema::table('manual_label_prints', function (Blueprint $table) {
            $table->dropColumn('qr_content');
        });
    }
};
