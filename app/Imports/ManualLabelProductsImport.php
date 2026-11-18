<?php

namespace App\Imports;

use App\Models\ManualLabelProduct;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ManualLabelProductsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    private int $importedCount = 0;

    public function prepareForValidation(array $row, int $index): array
    {
        $row['_empty_product_row'] = collect($row)->every(
            fn ($value) => $value === null || (is_string($value) && trim($value) === '')
        );

        $row['ten_hang'] = $this->text($row['ten_hang'] ?? $row['ten_hang_hoa'] ?? null);
        $row['chung_loai'] = $this->text($row['chung_loai'] ?? $row['phan_loai'] ?? null);
        $row['ten_khoa_hoc'] = $this->text($row['ten_khoa_hoc'] ?? null);
        $row['tieu_chuan'] = $this->text($row['tieu_chuan'] ?? $row['tieu_chuan_ap_dung'] ?? null);
        $row['nguon_goc'] = $this->text($row['nguon_goc'] ?? null);
        $row['bo_phan_dung'] = $this->text($row['bo_phan_dung'] ?? null);
        $row['so_dang_ky'] = $this->text($row['so_dang_ky'] ?? null);
        $row['name_key'] = $row['ten_hang'] === null ? null : ManualLabelProduct::key($row['ten_hang']);

        return $row;
    }

    public function model(array $row): ?ManualLabelProduct
    {
        if ($row['_empty_product_row']) {
            return null;
        }

        $this->importedCount++;

        return new ManualLabelProduct([
            'name_key' => $row['name_key'],
            'name' => $row['ten_hang'],
            'category' => $row['chung_loai'],
            'scientific_name' => $row['ten_khoa_hoc'],
            'standard' => $row['tieu_chuan'],
            'origin' => $row['nguon_goc'],
            'part_used' => $row['bo_phan_dung'],
            'registration' => $row['so_dang_ky'],
        ]);
    }

    public function rules(): array
    {
        $rules = [
            '*.ten_hang' => 'required|string|max:255',
            '*.name_key' => 'required|string|max:255|distinct|unique:manual_label_products,name_key',
            '*.chung_loai' => 'nullable|string|max:64',
            '*.ten_khoa_hoc' => 'nullable|string|max:255',
            '*.tieu_chuan' => 'nullable|string|max:255',
            '*.nguon_goc' => 'nullable|string|max:255',
            '*.bo_phan_dung' => 'nullable|string|max:255',
            '*.so_dang_ky' => 'nullable|string|max:255',
        ];

        return array_map(
            fn ($rule) => 'exclude_if:*._empty_product_row,true|'.$rule,
            $rules
        );
    }

    public function customValidationMessages(): array
    {
        return [
            '*.ten_hang.required' => 'Thiếu tên hàng.',
            '*.name_key.unique' => 'Tên hàng đã tồn tại.',
            '*.name_key.distinct' => 'Tên hàng bị trùng trong file import.',
        ];
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }
}
