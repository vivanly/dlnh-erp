<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelProduct;
use App\Models\ManualLabelProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ManualLabelSourceController extends Controller
{
    private const ORDERS = 'DM lệnh SX';

    private const PRODUCTS = 'MÃ HÀNG';

    private function authorizePrinting(): void
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
    }

    public function index()
    {
        $this->authorizePrinting();

        return view('manual-labels.import', [
            'orderCount' => ManualLabelProductionOrder::count(),
            'productCount' => ManualLabelProduct::count(),
            'lastImport' => ManualLabelProductionOrder::max('updated_at'),
        ]);
    }

    public function search(Request $request)
    {
        $this->authorizePrinting();
        $q = trim((string) $request->query('q'));
        if ($q === '') {
            return response()->json([]);
        }

        return response()->json(
            ManualLabelOrderController::searchQuery($q)->orderByDesc('order_date')->orderByDesc('id')->limit(20)->get()
                ->map(fn ($o) => $o->toFormArray())->values()
        );
    }

    public function import(Request $request)
    {
        $this->authorizePrinting();
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xlsm,xls', 'max:30720']]);
        @ini_set('memory_limit', '1G');
        @set_time_limit(300);

        $path = $request->file('file')->getRealPath();
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $names = $reader->listWorksheetNames($path);
        if (! in_array(self::ORDERS, $names, true)) {
            return back()->with('error', 'Không tìm thấy sheet "' . self::ORDERS . '" trong file Excel.');
        }
        $reader->setLoadSheetsOnly(array_values(array_intersect([self::ORDERS, self::PRODUCTS], $names)));
        $book = $reader->load($path);

        $now = now();
        $products = $this->readProducts($book->getSheetByName(self::PRODUCTS), $now);
        $orders = $this->readOrders($book->getSheetByName(self::ORDERS), $now);

        foreach (array_chunk($products, 500) as $chunk) {
            ManualLabelProduct::upsert($chunk, ['name_key'], ['name', 'category', 'scientific_name', 'standard', 'origin', 'part_used', 'registration', 'updated_at']);
        }
        foreach (array_chunk($orders, 500) as $chunk) {
            ManualLabelProductionOrder::upsert($chunk, ['label_code'], ['order_date', 'production_order', 'name', 'batch', 'mfg_date', 'exp_date', 'pack_spec', 'quantity', 'label_count', 'updated_at']);
        }

        $known = array_flip(array_column($products, 'name_key'));
        $unmatched = count(array_filter($orders, fn ($o) => ! isset($known[ManualLabelProduct::key($o['name'])])));
        $msg = 'Đã đọc ' . count($orders) . ' lệnh sản xuất và ' . count($products) . ' mã hàng.';
        if ($unmatched) {
            $msg .= " Có {$unmatched} lệnh có tên hàng chưa khớp sheet " . self::PRODUCTS . ' (thiếu tên khoa học, SĐK…).';
        }

        return redirect()->route('manual-labels.import.form')->with('success', $msg);
    }

    public function destroyAll()
    {
        $this->authorizePrinting();
        ManualLabelProductionOrder::query()->delete();
        ManualLabelProduct::query()->delete();

        return redirect()->route('manual-labels.import.form')->with('success', 'Đã xóa toàn bộ dữ liệu đã tải lên.');
    }

    private function readProducts($sheet, $now): array
    {
        $rows = [];
        if (! $sheet) {
            return $rows;
        }
        $text = fn ($c) => trim((string) $sheet->getCell($c)->getValue()) ?: null;
        for ($i = 3, $max = $sheet->getHighestDataRow(); $i <= $max; $i++) {
            $name = $text("B$i");
            if (! $name) {
                continue;
            }
            $rows[ManualLabelProduct::key($name)] = [
                'name_key' => ManualLabelProduct::key($name),
                'name' => $name,
                'category' => $text("C$i"),
                'scientific_name' => $text("D$i"),
                'standard' => $text("E$i"),
                'origin' => $text("F$i"),
                'part_used' => $text("G$i"),
                'registration' => $text("H$i"),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return array_values($rows);
    }

    private function readOrders($sheet, $now): array
    {
        $rows = [];
        $text = fn ($c) => trim((string) $sheet->getCell($c)->getValue()) ?: null;
        for ($i = 4, $max = $sheet->getHighestDataRow(); $i <= $max; $i++) {
            $code = $text("A$i");
            $name = $text("D$i");
            if (! $code || ! $name) {
                continue;
            }
            $batch = $text("E$i");
            [$mfg, $exp] = $this->lotDates($batch);
            $count = $sheet->getCell("J$i")->getValue();
            $rows[$code] = [
                'label_code' => $code,
                'order_date' => $this->date($sheet->getCell("B$i")->getValue()),
                'production_order' => $text("C$i"),
                'name' => $name,
                'batch' => $batch,
                'mfg_date' => $this->date($sheet->getCell("F$i")->getValue()) ?? $mfg,
                'exp_date' => $this->date($sheet->getCell("G$i")->getValue()) ?? $exp,
                'pack_spec' => $text("H$i"),
                'quantity' => $text("I$i"),
                'label_count' => is_numeric($count) ? (int) ceil($count) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return array_values($rows);
    }

    // Số lô dạng DDMMYY + mã; HSD = NSX + 2 năm - 1 ngày (theo công thức trong Excel)
    private function lotDates(?string $batch): array
    {
        if (! $batch || ! preg_match('/^(\d{2})(\d{2})(\d{2})/', $batch, $m) || ! checkdate((int) $m[2], (int) $m[1], 2000 + (int) $m[3])) {
            return [null, null];
        }
        $mfg = Carbon::create(2000 + (int) $m[3], (int) $m[2], (int) $m[1]);

        return [$mfg->format('Y-m-d'), $mfg->copy()->addYearsNoOverflow(2)->subDay()->format('Y-m-d')];
    }

    private function date($value): ?string
    {
        if (is_numeric($value) && $value > 20000) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        if (is_string($value) && preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', trim($value))) {
            try {
                return Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
