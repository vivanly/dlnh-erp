<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private readonly Builder $products)
    {
    }

    public function query(): Builder
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [
            'phan_loai',
            'ma_hang_sku',
            'ten_hang_hoa',
            'dvt',
            'nguon_goc',
            'bo_phan_dung',
            'ma_ppcb',
            'ten_khoa_hoc',
            'ghi_chu',
            'gtin',
            'tai_lieu_tham_khao',
        ];
    }

    public function map($product): array
    {
        return [
            $product->classification,
            $product->sku,
            $product->name,
            $product->unit,
            $product->origin,
            $product->part_used,
            $product->ppcb?->ma,
            $product->scientific_name,
            $product->note,
            $product->gtin,
            $product->scientific_name_reference,
        ];
    }
}
