<?php

namespace App\Imports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CatalogImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private int $importedCount = 0;

    public function __construct(private readonly string $modelClass)
    {
    }

    public function model(array $row): Model
    {
        $name = trim($row['ten_hang_hoa']);
        $sku = trim($row['ma_hang_sku']);
        $model = $this->modelClass;

        $this->importedCount++;

        return new $model([
            'name' => $name,
            'slug' => $model::uniqueSlug($name, $sku),
            'sku' => $sku,
            'classification' => $row['phan_loai'] ?? null,
            'unit' => $row['dvt'] ?? null,
            'origin' => $row['nguon_goc'] ?? null,
            'part_used' => $row['bo_phan_dung'] ?? null,
            'scientific_name' => $row['ten_khoa_hoc'] ?? null,
            'scientific_name_reference' => $row['tai_lieu_tham_khao'] ?? null,
            'note' => $row['ghi_chu'] ?? null,
        ]);
    }

    public function rules(): array
    {
        $table = (new $this->modelClass())->getTable();

        return [
            '*.ma_hang_sku' => 'required|string|max:255|unique:' . $table . ',sku',
            '*.ten_hang_hoa' => 'required|string|max:255',
            '*.phan_loai' => 'nullable|string|max:255',
            '*.dvt' => 'nullable|string|max:50',
            '*.nguon_goc' => 'nullable|string|max:255',
            '*.bo_phan_dung' => 'nullable|string|max:255',
            '*.ten_khoa_hoc' => 'nullable|string|max:255',
            '*.tai_lieu_tham_khao' => 'nullable|string|max:255',
            '*.ghi_chu' => 'nullable|string',
        ];
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }
}