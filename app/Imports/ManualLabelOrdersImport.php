<?php

namespace App\Imports;

use App\Http\Controllers\ManualLabelController;
use App\Models\ManualLabelProduct;
use App\Models\ManualLabelProductionOrder;
use App\Services\ManualLabelOrders;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ManualLabelOrdersImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $duplicates = 0;
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        ManualLabelOrders::ensureReady();

        DB::transaction(function () use ($rows) {
            // Serialize duplicate checks with other imports and label-code allocation.
            DB::table('manual_label_sequences')->where('id', 1)->lockForUpdate()->first();

            foreach ($rows as $index => $row) {
                $row = $row->toArray();
                if (collect($row)->every(fn ($value) => $value === null || trim((string) $value) === '')) {
                    continue;
                }

                $data = [];
                foreach (['production_order', 'name', 'batch', 'order_date', 'mfg_date', 'exp_date', 'pack_spec', 'quantity', 'label_type', 'traditional_medicine_name', 'qr_content', 'storage'] as $field) {
                    $value = $row[$field] ?? null;
                    $data[$field] = $value === null ? null : trim((string) $value);
                    if ($data[$field] === '') {
                        $data[$field] = null;
                    }
                }
                foreach (['order_date', 'mfg_date', 'exp_date'] as $field) {
                    $value = $data[$field];
                    $data[$field] = $value !== null && is_numeric($value) && (float) $value >= 1 && (float) $value <= 2958465
                        ? ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d')
                        : ManualLabelOrders::dateToIso($value);
                }
                $validator = Validator::make($data, [
                    'production_order' => ['required', 'string', 'max:64'],
                    'name' => ['required', 'string', 'max:255'],
                    'batch' => ['required', 'string', 'max:100'],
                    'order_date' => ['nullable', 'date_format:Y-m-d'],
                    'mfg_date' => ['nullable', 'date_format:Y-m-d'],
                    'exp_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:mfg_date'],
                    'pack_spec' => ['required', 'string', 'max:64'],
                    'quantity' => ['required', 'string', 'max:64'],
                    'label_type' => ['nullable', 'in:'.implode(',', array_keys(ManualLabelController::LABEL_TYPES))],
                    'traditional_medicine_name' => ['nullable', 'boolean'],
                    'qr_content' => ['nullable', 'string', 'max:1000'],
                    'storage' => ['nullable', 'string', 'max:255'],
                ], ['date_format' => ':attribute phải là ngày hợp lệ theo định dạng dd/mm/yyyy.']);
                if ($validator->fails()) {
                    $this->errors[] = ['row' => $index + 2, 'messages' => $validator->errors()->all()];
                    continue;
                }

                $data = ManualLabelOrders::normalizeWeightInputs($data);
                try {
                    $count = ManualLabelOrders::calculate($data['quantity'], $data['pack_spec'])['count'];
                } catch (ValidationException $exception) {
                    $this->errors[] = ['row' => $index + 2, 'messages' => [$exception->getMessage()]];
                    continue;
                }

                $duplicate = ManualLabelProductionOrder::where('production_order', $data['production_order'])
                    ->where('batch', $data['batch'])->get(['name'])
                    ->contains(fn ($order) => ManualLabelProduct::key($order->name) === ManualLabelProduct::key($data['name']));
                if ($duplicate) {
                    $this->duplicates++;
                    continue;
                }

                $data['traditional_medicine_name'] = $data['traditional_medicine_name'] === null
                    ? null
                    : ($data['label_type'] === 'vt' && $data['traditional_medicine_name'] === '1');
                $data['storage'] ??= ManualLabelProductionOrder::DEFAULT_STORAGE;
                ManualLabelProductionOrder::create($data + [
                    'label_code' => ManualLabelOrders::nextCode(),
                    'label_count' => $count,
                ]);
                $this->imported++;
            }
        });
    }
}
