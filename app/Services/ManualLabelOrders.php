<?php

namespace App\Services;

use App\Models\ManualLabelPrint;
use App\Models\ManualLabelProduct;
use App\Models\ManualLabelProductionOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ManualLabelOrders
{
    public static function dateToIso(mixed $value): mixed
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (\InvalidArgumentException $exception) {
                // Try the other supported format.
            }
        }

        return $value;
    }

    public static function dateForDisplay(?string $value): ?string
    {
        $iso = self::dateToIso($value);

        return $iso && preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso)
            ? substr($iso, 8, 2).'/'.substr($iso, 5, 2).'/'.substr($iso, 0, 4)
            : $iso;
    }

    public static function ensureReady(): void
    {
        if (! Schema::hasTable('manual_label_sequences')
            || ! Schema::hasColumn('manual_label_production_orders', 'label_type')
            || ! Schema::hasColumn('manual_label_production_orders', 'traditional_medicine_name')
            || ! Schema::hasColumn('manual_label_production_orders', 'qr_content')
            || ! Schema::hasColumn('manual_label_production_orders', 'storage')) {
            throw ValidationException::withMessages(['label_code' => 'Cơ sở dữ liệu nhãn tay chưa được nâng cấp. Liên hệ quản trị viên.']);
        }
    }

    public static function normalizeWeight(?string $value): ?string
    {
        if (! preg_match('/^\s*(\d{1,8})(?:[.,](\d{1,6}))?\s*(kg|g)?\s*$/iu', (string) $value, $matches)) {
            return null;
        }

        $fraction = rtrim($matches[2] ?? '', '0');
        $amount = $matches[1] . ($fraction !== '' ? '.' . $fraction : '');
        $unit = mb_strtoupper($matches[3] ?? 'kg');

        return $amount . ' ' . $unit;
    }

    public static function normalizeWeightInputs(array $data): array
    {
        foreach (['quantity', 'pack_spec'] as $field) {
            $data[$field] = self::normalizeWeight($data[$field] ?? null);
        }

        return $data;
    }

    public static function nextCode(): string
    {
        self::ensureReady();
        $sequence = DB::table('manual_label_sequences')->where('id', 1)->lockForUpdate()->first();
        if (! $sequence) {
            throw ValidationException::withMessages(['label_code' => 'Thiếu bộ đếm mã nhãn. Liên hệ quản trị viên.']);
        }
        $number = $sequence->last_number + 1;
        if ($number > 999999) {
            throw ValidationException::withMessages(['label_code' => 'Đã hết mã nhãn 6 chữ số.']);
        }
        DB::table('manual_label_sequences')->where('id', 1)->update(['last_number' => $number]);

        return sprintf('NH%06d', $number);
    }

    public static function calculate(?string $quantity, ?string $packSpec): array
    {
        $amount = self::weight($quantity);
        $pack = self::weight($packSpec);
        if ($amount === null || $pack === null || $amount <= 0 || $pack <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'SL và QCĐG phải là khối lượng dương có đơn vị kg hoặc g (ví dụ 10 kg, 500 g).',
            ]);
        }

        return ['count' => intdiv($amount, $pack), 'remainder_g' => ($amount % $pack) / 1000000];
    }

    public static function weight(?string $value): ?int
    {
        if (! preg_match('/^\s*(\d{1,8})(?:[.,](\d{1,6}))?\s*(kg|g)?\s*$/iu', (string) $value, $matches)) {
            return null;
        }
        $micros = (int) $matches[1] * 1000000 + (int) str_pad($matches[2] ?? '', 6, '0');
        if (mb_strtolower($matches[3] ?? 'kg') === 'kg') {
            $micros *= 1000;
        }

        return $micros;
    }

    public static function printed(ManualLabelProductionOrder $order): int
    {
        return (int) ManualLabelPrint::where('label_code', $order->label_code)->sum('copies_count');
    }

    public static function printProblem(ManualLabelProductionOrder $order, ?ManualLabelProduct $product): ?string
    {
        if (! $order->batch || ! $order->mfg_date || ! $order->exp_date) {
            return 'Thiếu số lô, NSX hoặc HSD.';
        }
        if ($order->exp_date->lt($order->mfg_date)) {
            return 'HSD không được trước NSX.';
        }
        if (! $product) {
            return 'Tên hàng chưa có trong danh mục mã hàng.';
        }
        if ($order->label_type) {
            if (! array_key_exists($order->label_type, \App\Http\Controllers\ManualLabelController::LABEL_TYPES)) {
                return 'Mẫu nhãn không hợp lệ.';
            }
            return null;
        }
        $category = trim((string) $product->category);
        $isMedicine = mb_stripos($category, 'vị thuốc') !== false;
        if (! $isMedicine && mb_stripos($category, 'dược liệu') === false) {
            return 'Chủng loại phải xác định là vị thuốc hoặc dược liệu.';
        }
        if (! $isMedicine && ! trim((string) $product->origin)) {
            return 'Thiếu nguồn gốc để chọn loại nhãn dược liệu.';
        }

        return null;
    }
}
