<?php

namespace App\Http\Controllers;

use App\Models\ManualLabelCatalog;
use App\Models\ManualLabelOrderLine;
use App\Models\ManualLabelSource;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ManualLabelSourceController extends Controller
{
    private const SHEET = 'PKN';

    private const CATALOG = 'TT';

    private const ORDERS = 'DataChitietDH';

    private function authorizePrinting(): void
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
    }

    public function index(Request $request)
    {
        $this->authorizePrinting();
        $q = trim((string) $request->query('q'));

        return view('manual-labels.import', [
            'sources' => ManualLabelSource::query()
                ->when($q !== '', function ($w) use ($q) {
                    $like = '%' . addcslashes($q, '%_\\') . '%';
                    $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('batch', 'like', $like));
                })
                ->orderBy('name')->orderBy('batch')->paginate($this->perPage($request))->withQueryString(),
            'total' => ManualLabelSource::count(),
        ]);
    }

    public function search(Request $request)
    {
        $this->authorizePrinting();
        $q = trim((string) $request->query('q'));
        if ($q === '') {
            return response()->json([]);
        }
        $like = '%' . addcslashes($q, '%_\\') . '%';

        return response()->json(
            ManualLabelSource::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('batch', 'like', $like))
                ->orderByDesc('mfg_date')->limit(20)->get()
                ->map->toFormArray()->values()
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
        if (! in_array(self::SHEET, $names, true)) {
            return back()->with('error', 'Không tìm thấy sheet "' . self::SHEET . '" trong file Excel.');
        }
        $reader->setLoadSheetsOnly(array_values(array_intersect([self::SHEET, self::CATALOG, self::ORDERS], $names)));
        $book = $reader->load($path);
        $sheet = $book->getSheetByName(self::SHEET);
        $catalog = $this->catalog($book->getSheetByName(self::CATALOG));

        $now = now();
        $rows = [];
        foreach ($sheet->getRowIterator(3) as $row) {
            $i = $row->getRowIndex();
            $name = trim((string) $sheet->getCell("H$i")->getValue());
            $batch = trim((string) $sheet->getCell("K$i")->getValue());
            if ($name === '' || $batch === '') {
                continue;
            }
            $info = $catalog[ManualLabelCatalog::key($name)] ?? [];
            $rows[$batch . '|' . $name] = [
                'kind' => strtoupper(trim((string) $sheet->getCell("F$i")->getValue())) ?: null,
                'item_code' => trim((string) $sheet->getCell("G$i")->getValue()) ?: null,
                'name' => $name,
                'scientific_name' => $info['scientific_name'] ?? null,
                'part_used' => $info['part_used'] ?? null,
                'origin_code' => trim((string) $sheet->getCell("I$i")->getValue()) ?: null,
                'origin' => $info['origin'] ?? null,
                'registration' => trim((string) $sheet->getCell("R$i")->getValue()) ?: null,
                'batch' => $batch,
                'mfg_date' => $this->date($sheet->getCell("L$i")->getValue()),
                'exp_date' => $this->date($sheet->getCell("M$i")->getValue()),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            ManualLabelSource::upsert($chunk, ['batch', 'name'], ['kind', 'item_code', 'scientific_name', 'part_used', 'origin_code', 'origin', 'registration', 'mfg_date', 'exp_date', 'updated_at']);
        }

        $this->saveCatalog($catalog, $now);
        $orderLines = $this->saveOrders($book->getSheetByName(self::ORDERS), $now, $rows);

        $matched = count(array_filter($rows, fn ($r) => $r['scientific_name'] || $r['origin']));

        return redirect()->route('manual-labels.import.form')->with('success', 'Đã đọc ' . count($rows) . ' dòng từ sheet ' . self::SHEET . ' (' . $matched . ' dòng khớp danh mục sheet ' . self::CATALOG . ') và ' . $orderLines . ' dòng đơn hàng từ sheet ' . self::ORDERS . '.');
    }

    private function catalog($sheet): array
    {
        $map = [];
        if (! $sheet) {
            return $map;
        }
        foreach ($sheet->getRowIterator(4) as $row) {
            $i = $row->getRowIndex();
            $name = trim((string) $sheet->getCell("C$i")->getValue());
            if ($name === '') {
                continue;
            }
            $map[ManualLabelCatalog::key($name)] ??= [
                'name' => $name,
                'scientific_name' => trim((string) $sheet->getCell("D$i")->getValue()) ?: null,
                'part_used' => trim((string) $sheet->getCell("F$i")->getValue()) ?: null,
                'origin' => trim((string) $sheet->getCell("G$i")->getValue()) ?: null,
            ];
        }

        return $map;
    }

    private function saveCatalog(array $catalog, $now): void
    {
        $rows = [];
        foreach ($catalog as $key => $info) {
            $rows[] = ['name_key' => $key, 'name' => $info['name'], 'scientific_name' => $info['scientific_name'], 'part_used' => $info['part_used'], 'origin' => $info['origin'], 'created_at' => $now, 'updated_at' => $now];
        }
        foreach (array_chunk($rows, 500) as $chunk) {
            ManualLabelCatalog::upsert($chunk, ['name_key'], ['name', 'scientific_name', 'part_used', 'origin', 'updated_at']);
        }
    }

    private function saveOrders($sheet, $now, array $pknRows = []): int
    {
        if (! $sheet) {
            return 0;
        }
        $dates = [];
        foreach ($pknRows as $r) {
            $dates[$r['batch']] ??= [$r['mfg_date'], $r['exp_date']];
        }
        $num = fn ($v) => is_numeric($v) ? (float) $v : null;
        $text = fn ($c) => trim((string) $sheet->getCell($c)->getValue()) ?: null;
        $rows = [];
        foreach ($sheet->getRowIterator(4) as $row) {
            $i = $row->getRowIndex();
            $code = $text("J$i");
            $name = $text("B$i");
            if (! $code || ! $name) {
                continue;
            }
            $delivery = $sheet->getCell("T$i")->getValue();
            $rows[] = [
                'order_code' => $code,
                'order_type' => strtoupper((string) $text("N$i")) ?: strtoupper(substr($code, 0, 2)),
                'customer' => $text("K$i"),
                'customer_type' => $text("S$i"),
                'received_date' => $this->date($sheet->getCell("L$i")->getValue()),
                'delivery_date' => is_numeric($delivery) ? ($this->date($delivery) ? \Carbon\Carbon::parse($this->date($delivery))->format('d/m/Y') : null) : (trim((string) $delivery) ?: null),
                'line_no' => is_numeric($sheet->getCell("A$i")->getValue()) ? (int) $sheet->getCell("A$i")->getValue() : null,
                'name' => $name,
                'unit' => $text("C$i"),
                'origin_code' => $text("D$i"),
                'quantity' => $num($sheet->getCell("E$i")->getValue()),
                'pack_weight' => $num($sheet->getCell("F$i")->getValue()),
                'pack_count' => ($n = $num($sheet->getCell("G$i")->getValue())) !== null ? (int) ceil($n) : null,
                'processing' => $text("H$i"),
                'note' => $text("I$i"),
                'batch' => $text("P$i"),
                'mfg_date' => $this->date($sheet->getCell("U$i")->getValue()) ?? ($dates[$text("P$i")][0] ?? null),
                'exp_date' => $this->date($sheet->getCell("V$i")->getValue()) ?? ($dates[$text("P$i")][1] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        ManualLabelOrderLine::query()->delete();
        foreach (array_chunk($rows, 500) as $chunk) {
            ManualLabelOrderLine::insert($chunk);
        }

        return count($rows);
    }

    public function destroyAll()
    {
        $this->authorizePrinting();
        ManualLabelSource::query()->delete();

        return redirect()->route('manual-labels.import.form')->with('success', 'Đã xóa toàn bộ dữ liệu đã tải lên.');
    }

    private function date($value): ?string
    {
        if (is_numeric($value) && $value > 20000) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        if (is_string($value) && trim($value) !== '') {
            try {
                return \Carbon\Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
