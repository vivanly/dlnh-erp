<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('type');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
