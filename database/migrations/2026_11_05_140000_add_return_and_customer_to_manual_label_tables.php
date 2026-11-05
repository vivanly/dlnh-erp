<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_label_prints', function (Blueprint $table) {
            $table->unsignedInteger('returned_count')->default(0)->after('delivered_count');
            $table->string('customer')->nullable()->after('returned_count');
        });

        Schema::table('manual_label_deliveries', function (Blueprint $table) {
            $table->string('kind', 10)->default('out')->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('manual_label_deliveries', fn (Blueprint $table) => $table->dropColumn('kind'));
        Schema::table('manual_label_prints', fn (Blueprint $table) => $table->dropColumn(['returned_count', 'customer']));
    }
};
