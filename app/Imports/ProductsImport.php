<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Ppcb;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private int $importedCount = 0;

    public function model(array $row): Product
    {
        $name = trim($row['ten_hang_hoa']);
        $sku = trim($row['ma_hang_sku']);
        $ppcbCode = trim((string) ($row['ma_ppcb'] ?? ''));
        $ppcbId = $ppcbCode === '' ? null : Ppcb::where('ma', $ppcbCode)->value('id');

        $product = new Product([
            'name' => $name,
            'slug' => Product::uniqueSlug($name, $sku),
            'sku' => $sku,
            'gtin' => $row['gtin'] ?? null,
            'part_used' => $row['bo_phan_dung'] ?? null,
            'ppcb_id' => $ppcbId,
            'origin' => $row['nguon_goc'] ?? null,
            'unit' => $row['dvt'] ?? null,
            'classification' => $row['phan_loai'] ?? null,
            'scientific_name' => $row['ten_khoa_hoc'] ?? null,
            'scientific_name_reference' => $row['tai_lieu_tham_khao'] ?? null,
            'note' => $row['ghi_chu'] ?? null,
        ]);

        $this->importedCount++;

        return $product;
    }

    public function rules(): array
    {
        return [
            '*.ma_hang_sku' => 'required|string|max:255|unique:products,sku',
            '*.ten_hang_hoa' => 'required|string|max:255',
            '*.gtin' => 'nullable|string|max:255|unique:products,gtin',
            '*.ma_ppcb' => 'nullable|string|exists:ppcb,ma',
            '*.phan_loai' => 'nullable|string|max:255',
            '*.ten_khoa_hoc' => 'nullable|string|max:255',
            '*.nguon_goc' => 'nullable|string|max:255',
            '*.bo_phan_dung' => 'nullable|string|max:255',
            '*.dvt' => 'nullable|string|max:255',
            '*.tai_lieu_tham_khao' => 'nullable|string|max:255',
            '*.ghi_chu' => 'nullable|string',
        ];
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }
}
