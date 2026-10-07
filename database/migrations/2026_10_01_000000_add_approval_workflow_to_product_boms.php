<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_boms', function (Blueprint $table) {
            $table->string('status', 32)->default('approved')->after('is_active');
            $table->foreignId('submitted_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('submitted_by');
            $table->foreignId('approved_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('product_boms', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['product_id', 'status']);
            $table->dropColumn([
                'status',
                'submitted_by',
                'submitted_at',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
