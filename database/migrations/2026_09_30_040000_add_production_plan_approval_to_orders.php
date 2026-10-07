<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('production_plan_approved_at')->nullable();
            $table->foreignId('production_plan_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('production_plan_rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_plan_approved_by');
            $table->dropColumn(['production_plan_approved_at', 'production_plan_rejection_reason']);
        });
    }
};