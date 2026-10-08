<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = array (
  0 => 
  array (
    'id' => 1,
    'name' => 'Phòng IT',
    'code' => 'IT',
    'description' => 'Hộ trợ hệ thống công nghệ thông tin',
  ),
  1 => 
  array (
    'id' => 2,
    'name' => 'Phòng QA',
    'code' => 'QA',
    'description' => 'Kiểm soát chất lượng',
  ),
  2 => 
  array (
    'id' => 3,
    'name' => 'Phòng QC',
    'code' => 'QC',
    'description' => 'Kiểm tra chất lượng',
  ),
  3 => 
  array (
    'id' => 4,
    'name' => 'Kho',
    'code' => 'KHO',
    'description' => 'Quản lý nguyên liệu, thành phẩm, bán thành phẩm',
  ),
  4 => 
  array (
    'id' => 5,
    'name' => 'Phòng Kinh doanh',
    'code' => 'KINH-DOANH',
    'description' => 'Mua bán nguyên liệu, thành phẩm, bán thành phẩm',
  ),
  5 => 
  array (
    'id' => 6,
    'name' => 'Phòng Kế hoạch',
    'code' => 'KE-HOACH',
    'description' => 'Kế hoạch sản xuất thành phẩm, bán thành phẩm...',
  ),
  6 => 
  array (
    'id' => 7,
    'name' => 'Sản Xuất',
    'code' => 'SAN-XUAT',
    'description' => 'Sản xuất ra thành phẩm, bán thành phẩm...',
  ),
  7 => 
  array (
    'id' => 8,
    'name' => 'Phòng Kế Toán',
    'code' => 'KE-TOAN',
    'description' => 'Kiểm soát hóa đơn, chứng từ, chuỗi dòng tiền...',
  ),
  8 => 
  array (
    'id' => 9,
    'name' => 'Phòng Thầu',
    'code' => 'THAU',
    'description' => 'Kiểm soát các đơn thầu kế hoạch đi hàng cho đơn vị thầu...',
  ),
  9 => 
  array (
    'id' => 10,
    'name' => 'Phòng Cơ Điện',
    'code' => 'CO-DIEN',
    'description' => 'Quản lý, sửa chữa các thiết bị cơ sở hạ tầng..',
  ),
  10 => 
  array (
    'id' => 11,
    'name' => 'Phòng Hành Chính Nhân Sự',
    'code' => 'HCNS',
    'description' => 'Phòng tổng hợp...',
  ),
  11 => 
  array (
    'id' => 12,
    'name' => 'Phòng Dự Án',
    'code' => 'DU-AN',
    'description' => 'Dự án trồng cây dược liệu...',
  ),
  12 => 
  array (
    'id' => 13,
    'name' => 'Phòng Nghiên Cứu',
    'code' => 'NGHIEN-CUU',
    'description' => 'Nghiên cứu các vị thuốc, thuốc...',
  ),
);

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], $department);
        }
    }
}
