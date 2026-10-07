<?php

use Illuminate\database\migrations\migration;
use Illuminate\database\schema\blueprint;
use Illuminate\support\Facades\schema;

return new class extends migration
{
    public function up(): void
    {
        schema::create('suppliers', function (blueprint $table) {
            $table->id();
            $table->string('name'); // tên nhà cung cấp
            $table->string('code')->unique(); // mã nhà cung cấp
            $table->string('email')->nullable(); // email
            $table->string('phone')->nullable(); // số điện thoại
            $table->text('address')->nullable(); // địa chỉ
            $table->string('contact_person')->nullable(); // người liên hệ
            $table->text('notes')->nullable(); // ghi chú
            $table->timestamps();
        });
    }

    public function down(): void
    {
        schema::dropIfExists('suppliers');
    }
};
