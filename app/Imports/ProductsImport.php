<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // $row tương ứng với tiêu đề các cột trong file Excel của bạn
        return new Product([
            'name'                      => $row['ten_hang_hoa'] ?? null,
            'slug'                      => Str::slug($row['ten_hang_hoa'] ?? ''),
            'sku'                       => $row['ma_hang_sku'] ?? null,
            'gtin'                      => $row['gtin'] ?? null,
            'part_used'                 => $row['bo_phan_dung'] ?? null,
            'origin'                    => $row['nguon_goc'] ?? null,
            'unit'                      => $row['dvt'] ?? null,
            'classification'            => $row['phan_loai'] ?? null,
            'scientific_name'           => $row['ten_khoa_hoc'] ?? null,
            'scientific_name_reference' => $row['tai_lieu_tham_khao'] ?? null,
            'note'                      => $row['ghi_chu'] ?? null,
        ]);
    }
}