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
			$table->string('employee_code')->unique()->nullable(); // Mã nhân viên
			$table->string('phone')->nullable();                    // Số điện thoại
			$table->text('address')->nullable();                    // Địa chỉ
			$table->string('status')->default('working');           // Trạng thái làm việc (working: Đang làm, resigned: Nghỉ việc...)
		});
	}

	public function down(): void
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn(['employee_code', 'phone', 'address', 'status']);
		});
	}
};
