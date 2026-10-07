<?php

namespace App\Exports;

use App\Models\Ppcb;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PpcbsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Builder $ppcbs)
    {
    }

    public function query(): Builder
    {
        return $this->ppcbs;
    }

    public function headings(): array
    {
        return ['ma', 'ten_ppcb', 'chi_tiet_ppcb', 'ghi_chu'];
    }

    public function map($ppcb): array
    {
        return [
            $ppcb->ma,
            $ppcb->ten_ppcb,
            $ppcb->chi_tiet_ppcb,
            $ppcb->ghi_chu,
        ];
    }
}
