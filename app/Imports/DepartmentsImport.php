<?php

namespace App\Imports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DepartmentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private int $importedCount = 0;

    public function model(array $row): Department
    {
        $department = new Department([
            'code' => trim($row['code']),
            'name' => trim($row['name']),
            'description' => $row['description'] ?? null,
        ]);

        $this->importedCount++;

        return $department;
    }

    public function rules(): array
    {
        return [
            '*.code' => 'required|string|max:50|unique:departments,code',
            '*.name' => 'required|string|max:255',
            '*.description' => 'nullable|string',
        ];
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }
}
