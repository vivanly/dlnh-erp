<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
	{
		Schema::table('users', function (Blueprint $table) {
			// Liên kết tới bảng departments (cho phép null nếu là tài khoản quản trị tối cao)
			$table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
       
			// Vai trò của nhân viên trong bộ phận (Ví dụ: manager, staff)
			$table->string('role')->default('staff');
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropForeign(['department_id']);
			$table->dropColumn(['department_id', 'role']);
		});
	}
};
