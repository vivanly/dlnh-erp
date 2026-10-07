<?php

namespace App\Imports;

use App\Models\Ppcb;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PpcbsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    private int $importedCount = 0;

    public function model(array $row): Ppcb
    {
        $ppcb = new Ppcb([
            'ma' => trim($row['ma']),
            'ten_ppcb' => trim($row['ten_ppcb']),
            'chi_tiet_ppcb' => $row['chi_tiet_ppcb'] ?? null,
            'ghi_chu' => $row['ghi_chu'] ?? null,
        ]);

        $this->importedCount++;

        return $ppcb;
    }

    public function rules(): array
    {
        return [
            '*.ma' => 'required|string|max:50|unique:ppcb,ma',
            '*.ten_ppcb' => 'required|string|max:255',
            '*.chi_tiet_ppcb' => 'nullable|string',
            '*.ghi_chu' => 'nullable|string',
        ];
    }

    public function importedCount(): int
    {
        return $this->importedCount;
    }
}
