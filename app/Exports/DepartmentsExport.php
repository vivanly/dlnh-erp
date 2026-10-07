<?php

namespace App\Exports;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DepartmentsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function query(): Builder
    {
        return Department::query()->orderBy('id');
    }

    public function headings(): array
    {
        return ['code', 'name', 'description'];
    }

    public function map($department): array
    {
        return [
            $department->code,
            $department->name,
            $department->description,
        ];
    }
}
