<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $fillable = [
        'code',           // Mã Khách Hàng
        'name',           // Tên Khách Hàng
        'type',           // Phân Loại (Lẻ, Thầu, Cửa Hàng, Bệnh Viện)
        'license_number', // Giấy Phép Kinh Doanh
        'phone',          // Số Điện Thoại
        'address',        // Địa Chỉ
        'email',          // Email
        'note',           // Ghi Chú
    ];

    // Mối quan hệ với đơn bán hàng sau này
    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }
}