<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\User;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private int $importedCount = 0;

    public function model(array $row): User
    {
        $departmentId = filled($row['department'] ?? null)
            ? Department::where('name', trim($row['department']))->value('id')
            : null;

        $user = new User([
            'employee_code' => $row['employee_code'] ?? null,
            'name' => trim($row['name']),
            'email' => trim($row['email']),
            'password' => $row['password'],
            'phone' => $row['phone'] ?? null,
            'address' => $row['address'] ?? null,
            'status' => $row['status'] ?? 'working',
            'department_id' => $departmentId,
            'position' => $row['position'] ?? null,
        ]);

        $this->importedCount++;

        return $user;
    }

    public function rules(): array
    {
        return [
            '*.employee_code' => 'nullable|string|max:50|unique:users,employee_code',
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|max:255|unique:users,email',
            '*.password' => 'required|string|min:6',
            '*.phone' => 'nullable|string|max:255',
            '*.address' => 'nullable|string',
            '*.status' => 'nullable|in:working,resigned',
            '*.department' => 'nullable|string|exists:departments,name',
            '*.position' => 'nullable|string|max:100',
        ];
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }
}
