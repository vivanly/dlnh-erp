<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

class SalesOrderLabelWorkbook
{
    private array $temporaryQrFiles = [];

    private const SHEETS = [
        'vt' => ['NhanVT', 'NhanVT(New)'],
        'dl_n' => ['NhanDL_N'],
        'dl_b' => ['NhanDL_B'],
    ];

    private const SLOTS = [
        [
            'range' => 'C3:V23', 'drawings' => ['A3', 'Q18'],
            'product' => 'H5', 'batch' => 'R6', 'scientific' => 'H7', 'mfg' => 'R8',
            'part' => 'H9', 'exp' => 'R10', 'origin' => 'H11', 'weight' => 'U12',
            'registration' => 'H13', 'standard' => 'P14',
        ],
        [
            'range' => 'Z3:AS23', 'drawings' => ['X3', 'AN18'],
            'product' => 'AE5', 'batch' => 'AO6', 'scientific' => 'AE7', 'mfg' => 'AO8',
            'part' => 'AE9', 'exp' => 'AO10', 'origin' => 'AE11', 'weight' => 'AR12',
            'registration' => 'AE13', 'standard' => 'AM14',
        ],
        [
            'range' => 'C27:V47', 'drawings' => ['A27', 'Q42'],
            'product' => 'H29', 'batch' => 'R30', 'scientific' => 'H31', 'mfg' => 'R32',
            'part' => 'H33', 'exp' => 'R34', 'origin' => 'H35', 'weight' => 'U36',
            'registration' => 'H37', 'standard' => 'P38',
        ],
        [
            'range' => 'Z27:AS47', 'drawings' => ['X27', 'AN42'],
            'product' => 'AE29', 'batch' => 'AO30', 'scientific' => 'AE31', 'mfg' => 'AO32',
            'part' => 'AE33', 'exp' => 'AO34', 'origin' => 'AE35', 'weight' => 'AR36',
            'registration' => 'AE37', 'standard' => 'AM38',
        ],
    ];

    public function create(array $labels): string
    {
        $path = null;
        try {
            $workbook = $this->prepare($labels);
            $path = tempnam(sys_get_temp_dir(), 'sales-labels-');
            if ($path === false) {
                throw new RuntimeException('Không tạo được file Excel tạm để xuất nhãn.');
            }
            (new Xlsx($workbook))->save($path);
        } catch (\Throwable $exception) {
            if ($path && is_file($path)) {
                @unlink($path);
            }
            throw $exception;
        } finally {
            foreach ($this->temporaryQrFiles as $qrFile) {
                if (is_file($qrFile)) {
                    @unlink($qrFile);
                }
            }
            $this->temporaryQrFiles = [];
        }

        return $path;
    }

    private function prepare(array $labels)
    {
        if ($labels === []) {
            throw new RuntimeException('Không có nhãn nào để xuất.');
        }

        $templatePath = base_path('Nhan.xlsx');
        if (!is_file($templatePath)) {
            throw new RuntimeException('Không tìm thấy mẫu nhãn Nhan.xlsx.');
        }

        $workbook = IOFactory::load($templatePath);
        $groups = collect($labels)->groupBy('label_type');
        $usedSheets = [];

        foreach (self::SHEETS as $labelType => $sheetNames) {
            $records = $groups->get($labelType, collect())->values();
            if ($records->isEmpty()) {
                continue;
            }

            $sheetName = null;
            $source = null;
            foreach ($sheetNames as $candidate) {
                $source = $workbook->getSheetByName($candidate);
                if ($source) {
                    $sheetName = $candidate;
                    break;
                }
            }
            if (!$source) {
                throw new RuntimeException('Mẫu Excel thiếu sheet ' . implode(' hoặc ', $sheetNames) . '.');
            }
            $source->setShowGridlines(false);
            $template = clone $source;

            foreach ($records->chunk(4)->values() as $pageIndex => $pageLabels) {
                $pageLabels = $pageLabels->values();
                if ($pageIndex === 0) {
                    $sheet = $source;
                } else {
                    $sheet = clone $template;
                    $sheet->setTitle(substr($sheetName . ' ' . ($pageIndex + 1), 0, 31));
                    $workbook->addSheet($sheet);
                }

                $sheet->getPageSetup()->setPrintArea('A1:AS48');
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(1)
                    ->setHorizontalCentered(true);
                foreach (self::SLOTS as $slotIndex => $slot) {
                    $label = $pageLabels->get($slotIndex);
                    if ($label) {
                        $this->fillSlot($sheet, $slot, $label);
                    } else {
                        $this->clearSlot($sheet, $slot);
                    }
                }
            }

            $usedSheets[$sheetName] = true;
        }

        for ($index = $workbook->getSheetCount() - 1; $index >= 0; $index--) {
            $sheetName = $workbook->getSheet($index)->getTitle();
            $isTemplateSheet = in_array($sheetName, array_merge(...array_values(self::SHEETS)), true);
            if ($isTemplateSheet && !isset($usedSheets[$sheetName])) {
                $workbook->removeSheetByIndex($index);
            }
        }

        $workbook->setActiveSheetIndex(0);

        return $workbook;
    }

    private function fillSlot(Worksheet $sheet, array $slot, array $label): void
    {
        foreach (['product', 'batch', 'scientific', 'part', 'origin', 'weight', 'registration', 'standard'] as $field) {
            $sheet->setCellValue($slot[$field], $label[$field] ?? '');
        }

        $sheet->setCellValue($slot['mfg'], $label['mfg'] ?? '');
        $sheet->setCellValue($slot['exp'], $label['exp'] ?? '');

        if (!empty($label['qr_url'])) {
            $qrPath = tempnam(sys_get_temp_dir(), 'sales-label-qr-');
            if ($qrPath === false) {
                throw new RuntimeException('Không tạo được ảnh QR tạm cho nhãn.');
            }
            $this->temporaryQrFiles[] = $qrPath;
            $options = new QROptions;
            $options->outputType = QRCode::OUTPUT_IMAGE_PNG;
            $options->scale = 5;
            $options->imageTransparent = false;
            (new QRCode($options))->render($label['qr_url'], $qrPath);

            foreach ($sheet->getDrawingCollection() as $drawing) {
                if ($drawing->getCoordinates() === $slot['drawings'][1]) {
                    $drawing->setCoordinates('A100');
                }
            }

            $drawing = new Drawing();
            $drawing->setPath($qrPath);
            $drawing->setCoordinates($slot['drawings'][1]);
            $drawing->setHeight(68);
            $drawing->setWorksheet($sheet);
        }
    }

    private function clearSlot(Worksheet $sheet, array $slot): void
    {
        [$start, $end] = explode(':', $slot['range']);
        [$startColumn, $startRow] = Coordinate::coordinateFromString($start);
        [$endColumn, $endRow] = Coordinate::coordinateFromString($end);
        $startColumnIndex = Coordinate::columnIndexFromString($startColumn);
        $endColumnIndex = Coordinate::columnIndexFromString($endColumn);

        foreach ($sheet->getMergeCells() as $mergedRange) {
            [$mergeStart, $mergeEnd] = explode(':', $mergedRange);
            [$mergeStartColumn, $mergeStartRow] = Coordinate::coordinateFromString($mergeStart);
            [$mergeEndColumn, $mergeEndRow] = Coordinate::coordinateFromString($mergeEnd);
            $insideSlot = Coordinate::columnIndexFromString($mergeStartColumn) >= $startColumnIndex
                && Coordinate::columnIndexFromString($mergeEndColumn) <= $endColumnIndex
                && $mergeStartRow >= $startRow
                && $mergeEndRow <= $endRow;
            if ($insideSlot) {
                $sheet->unmergeCells($mergedRange);
            }
        }

        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($column = $startColumnIndex; $column <= $endColumnIndex; $column++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column) . $row, null);
            }
        }

        $sheet->getStyle($slot['range'])->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_NONE],
                'outline' => ['borderStyle' => Border::BORDER_NONE],
                'inside' => ['borderStyle' => Border::BORDER_NONE],
            ],
        ]);

        foreach ($sheet->getDrawingCollection() as $drawing) {
            if (in_array($drawing->getCoordinates(), $slot['drawings'], true)) {
                $drawing->setCoordinates('A100');
            }
        }
    }
}