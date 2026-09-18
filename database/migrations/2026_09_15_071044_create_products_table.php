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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            // Tên hàng hóa và định danh SEO
            $table->string('name');                         // Tên chính theo dược điển / (tên khác)
            $table->string('slug')->unique();               // Đường dẫn SEO
            
            // Các thuộc tính chuyên ngành dược liệu
            $table->string('part_used')->nullable();        // Bộ phận dùng (Hoa, Lá, Thân, Rễ...)
            $table->string('origin')->nullable();           // Nguồn gốc (VN,...)
            $table->string('unit')->nullable();             // Đơn vị tính (ĐVT: Kg, g,...)
            $table->string('classification')->nullable();   // Phân loại (DL/Sơ chế/VT/TCT/PL/TT...)
            
            // Mã định danh sản phẩm
            $table->string('sku')->unique();                // Mã hàng (Mã hóa, ví dụ: HACTISON)
            $table->string('gtin')->nullable()->unique();   // Mã GTIN (Mã vạch sản phẩm)
            
            // Tên khoa học và tài liệu tham khảo
            $table->string('scientific_name')->nullable();           // Tên khoa học (Ví dụ: Cynarae Scolymi Flos)
            $table->string('scientific_name_reference')->nullable(); // Tài liệu tham khảo tên khoa học (Theo ĐĐVN 6)
            
            // Thông tin bổ sung
            $table->text('note')->nullable();               // Ghi chú
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};