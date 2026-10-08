<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CatalogExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Builder $items)
    {
    }

    public function query(): Builder
    {
        return $this->items;
    }

    public function headings(): array
    {
        return ['phan_loai', 'ma_hang_sku', 'ten_hang_hoa', 'dvt', 'nguon_goc', 'bo_phan_dung', 'ten_khoa_hoc', 'ghi_chu', 'tai_lieu_tham_khao'];
    }

    public function map($item): array
    {
        return [
            $item->classification,
            $item->sku,
            $item->name,
            $item->unit,
            $item->origin,
            $item->part_used,
            $item->scientific_name,
            $item->note,
            $item->scientific_name_reference,
        ];
    }
}