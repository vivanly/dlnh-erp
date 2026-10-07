<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_import_an_employee_from_csv(): void
    {
        $itUser = User::factory()->create(['role' => 'it']);
        $this->actingAs($itUser)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Import Excel');

        $this->actingAs($itUser)
            ->get(route('users.import.form'))
            ->assertOk()
            ->assertSee('Import Nhân sự từ Excel');

        $file = UploadedFile::fake()->createWithContent(
            'employees.csv',
            "employee_code,name,email,password,phone,address,status,department,position\n"
            . "EMP-2001,Nguyen Test,nguyen.test@example.com,secret123,0900000000,Test address,working,,Staff\n"
        );

        $this->actingAs($itUser)
            ->post(route('users.import'), ['file' => $file])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'Import thành công 1 nhân viên!');

        $employee = User::where('email', 'nguyen.test@example.com')->firstOrFail();

        $this->assertSame('EMP-2001', $employee->employee_code);
        $this->assertTrue(Hash::check('secret123', $employee->password));
    }

    public function test_duplicate_employee_email_is_skipped_and_reported(): void
    {
        $itUser = User::factory()->create(['role' => 'it']);
        User::factory()->create(['email' => 'existing@example.com']);
        $file = UploadedFile::fake()->createWithContent(
            'employees.csv',
            "employee_code,name,email,password\n"
            . "EMP-2002,Duplicate Test,existing@example.com,secret123\n"
        );

        $this->actingAs($itUser)
            ->post(route('users.import'), ['file' => $file])
            ->assertRedirect(route('users.import.form'))
            ->assertSessionHas('warning', 'Đã import 0 nhân viên; bỏ qua 1 dòng không hợp lệ.')
            ->assertSessionHas('import_errors');

        $this->assertDatabaseMissing('users', ['employee_code' => 'EMP-2002']);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_non_it_users_cannot_import_employees(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)
            ->get(route('users.import.form'))
            ->assertForbidden();
    }
}
