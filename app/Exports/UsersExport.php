<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Builder $users)
    {
    }

    public function query(): Builder
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'employee_code',
            'name',
            'email',
            'password',
            'phone',
            'address',
            'status',
            'department',
            'position',
        ];
    }

    public function map($user): array
    {
        return [
            $user->employee_code,
            $user->name,
            $user->email,
            '',
            $user->phone,
            $user->address,
            $user->status === 'resigned' ? 'Nghỉ việc' : 'Đang làm việc',
            $user->department?->name,
            $user->position,
        ];
    }
}
