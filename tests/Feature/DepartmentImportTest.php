<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DepartmentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_departments_from_the_export_format(): void
    {
        $itUser = User::factory()->create(['role' => 'it']);
        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "code,name,description\nHR,Phòng nhân sự,Quản lý nhân sự\n"
        );

        $this->actingAs($itUser)
            ->get(route('departments.index'))
            ->assertOk()
            ->assertSeeInOrder(['Import Excel', 'Export Excel']);

        $this->actingAs($itUser)
            ->post(route('departments.import'), ['file' => $file])
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success', 'Import thành công 1 phòng ban!');

        $this->assertDatabaseHas('departments', [
            'code' => 'HR',
            'name' => 'Phòng nhân sự',
            'description' => 'Quản lý nhân sự',
        ]);
    }

    public function test_duplicate_department_codes_are_skipped_and_reported(): void
    {
        $itUser = User::factory()->create(['role' => 'it']);
        Department::create(['code' => 'HR', 'name' => 'Phòng nhân sự']);
        $file = UploadedFile::fake()->createWithContent(
            'departments.csv',
            "code,name,description\nHR,Phòng mới,Mô tả mới\n"
        );

        $this->actingAs($itUser)
            ->post(route('departments.import'), ['file' => $file])
            ->assertRedirect(route('departments.import.form'))
            ->assertSessionHas('warning', 'Đã import 0 phòng ban; bỏ qua 1 dòng không hợp lệ.')
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('departments', 1);
        $this->assertDatabaseHas('departments', ['code' => 'HR', 'name' => 'Phòng nhân sự']);
    }

    public function test_non_it_users_cannot_import_departments(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)->get(route('departments.import.form'))->assertForbidden();
    }
}
