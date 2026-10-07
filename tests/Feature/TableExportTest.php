<?php

namespace Tests\Feature;

use App\Exports\DepartmentsExport;
use App\Exports\UsersExport;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TableExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_export_employees_using_the_current_filters(): void
    {
        Excel::fake();

        $itUser = User::factory()->create(['role' => 'it']);
        User::factory()->create(['name' => 'Working employee', 'status' => 'working']);
        User::factory()->create(['name' => 'Resigned employee', 'status' => 'resigned']);

        $this->actingAs($itUser)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSeeInOrder(['Import Excel', 'Export Excel']);

        $response = $this->actingAs($itUser)->get(route('users.export', ['status' => 'working']));

        $response->assertOk();
        Excel::assertDownloaded('nhan-su.xlsx', function (UsersExport $export) {
            $this->assertSame([
                'employee_code',
                'name',
                'email',
                'password',
                'phone',
                'address',
                'status',
                'department',
                'position',
            ], $export->headings());

            $users = $export->query()->get();
            $this->assertCount(2, $users);

            $employee = $users->firstWhere('name', 'Working employee');
            $row = $export->map($employee);
            $this->assertSame('Working employee', $row[1]);
            $this->assertSame('', $row[3]);
            $this->assertNotSame($employee->password, $row[3]);

            return true;
        });
    }

    public function test_it_can_export_departments(): void
    {
        Excel::fake();

        $itUser = User::factory()->create(['role' => 'it']);
        Department::create(['name' => 'Phòng nhân sự', 'code' => 'HR', 'description' => 'Quản lý nhân sự']);

        $this->actingAs($itUser)
            ->get(route('departments.index'))
            ->assertOk()
            ->assertSeeInOrder(['Import Excel', 'Export Excel']);

        $response = $this->actingAs($itUser)->get(route('departments.export'));

        $response->assertOk();
        Excel::assertDownloaded('phong-ban.xlsx', function (DepartmentsExport $export) {
            $this->assertSame(['code', 'name', 'description'], $export->headings());
            $this->assertCount(1, $export->query()->get());

            return true;
        });
    }

    public function test_non_it_users_cannot_export_either_table(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)->get(route('users.export'))->assertForbidden();
        $this->actingAs($user)->get(route('departments.export'))->assertForbidden();
    }
}
