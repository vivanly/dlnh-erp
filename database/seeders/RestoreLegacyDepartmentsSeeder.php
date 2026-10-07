<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RestoreLegacyDepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'IT', 'name' => 'Công nghệ thông tin'],
            ['code' => 'QA', 'name' => 'Đảm bảo chất lượng'],
            ['code' => 'QC', 'name' => 'Kiểm tra chất lượng'],
            ['code' => 'KHO', 'name' => 'Kho'],
            ['code' => 'KINH-DOANH', 'name' => 'Kinh doanh'],
            ['code' => 'KE-HOACH', 'name' => 'Kế hoạch'],
            ['code' => 'SAN-XUAT', 'name' => 'Sản xuất'],
            ['code' => 'KE-TOAN', 'name' => 'Kế toán'],
            ['code' => 'THAU', 'name' => 'Đấu thầu'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name']]
            );
        }

        $user = User::where('employee_code', 'NH0176')
            ->orWhere('email', 'admin@gmail.com')
            ->first() ?? new User();

        $user->fill([
            'name' => 'NH0176',
            'employee_code' => 'NH0176',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'department_id' => Department::where('code', 'IT')->value('id'),
            'position' => 'Trưởng Phòng',
            'role' => 'it',
            'status' => 'working',
            'email_verified_at' => now(),
        ]);
        $user->save();
    }
}
