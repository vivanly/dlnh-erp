<?php

namespace Tests\Feature;

use App\Services\SalesOrderLabelWorkbook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class SalesOrderLabelWorkbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_labels_fill_three_slots_in_the_original_excel_template(): void
    {
        $labels = collect(['A', 'B', 'C'])->map(fn ($suffix) => [
            'label_type' => 'dl_n',
            'product' => 'Herb ' . $suffix,
            'batch' => 'LOT-' . $suffix,
            'scientific' => 'Radix ' . $suffix,
            'mfg' => '01/01/2026',
            'part' => 'Root',
            'exp' => '01/01/2028',
            'origin' => 'Viet Nam',
            'weight' => '1Kg',
            'registration' => '',
            'standard' => '',
        ])->all();

        $path = app(SalesOrderLabelWorkbook::class)->create($labels);

        try {
            $workbook = IOFactory::load($path);
            $sheet = $workbook->getSheetByName('NhanDL_N');

            $this->assertNotNull($sheet);
            $this->assertSame(1, $workbook->getSheetCount());
            $this->assertSame('Herb A', $sheet->getCell('H5')->getValue());
            $this->assertSame('Herb B', $sheet->getCell('AE5')->getValue());
            $this->assertSame('Herb C', $sheet->getCell('H29')->getValue());
            $this->assertNull($sheet->getCell('AE29')->getValue());
            $this->assertContains('E29:G29', $sheet->getMergeCells());
            $this->assertSame('LOT-C', $sheet->getCell('R30')->getValue());
        } finally {
            @unlink($path);
        }
    }

    public function test_more_than_four_labels_continue_on_another_a4_landscape_sheet(): void
    {
        $labels = collect(range(1, 5))->map(fn ($number) => [
            'label_type' => 'vt',
            'product' => 'Herb ' . $number,
            'batch' => 'LOT-' . $number,
            'scientific' => 'Radix ' . $number,
            'mfg' => '01/01/2026',
            'part' => 'Root',
            'exp' => '01/01/2028',
            'origin' => 'Viet Nam',
            'weight' => '1Kg',
            'registration' => '',
            'standard' => '',
        ])->all();

        $path = app(SalesOrderLabelWorkbook::class)->create($labels);

        try {
            $workbook = IOFactory::load($path);
            $this->assertSame(2, $workbook->getSheetCount());
            $secondPage = $workbook->getSheetByName('NhanVT 2');
            $this->assertNotNull($secondPage);
            $this->assertSame('Herb 5', $secondPage->getCell('H5')->getValue());
            $this->assertNull($secondPage->getCell('AE5')->getValue());
            $this->assertNull($secondPage->getCell('H29')->getValue());
            $this->assertNull($secondPage->getCell('AE29')->getValue());
            $this->assertSame('landscape', $secondPage->getPageSetup()->getOrientation());
            $this->assertSame(9, $secondPage->getPageSetup()->getPaperSize());
            $this->assertSame('A1:AS48', $secondPage->getPageSetup()->getPrintArea());
            $this->assertSame('Times New Roman', $secondPage->getStyle('H5')->getFont()->getName());
        } finally {
            @unlink($path);
        }
    }

    public function test_workbook_uses_original_excel_templates_for_all_three_label_designs(): void
    {
        $labels = [
            ['label_type' => 'vt', 'product' => 'Vị thuốc A', 'batch' => 'LOT-VT-A', 'scientific' => 'Radix A', 'mfg' => '01/01/2026', 'part' => 'Rễ', 'exp' => '01/01/2028', 'origin' => 'Việt Nam', 'weight' => '1Kg', 'registration' => 'VT-001', 'standard' => ''],
            ['label_type' => 'vt', 'product' => 'Vị thuốc B', 'batch' => 'LOT-VT-B', 'scientific' => 'Radix B', 'mfg' => '01/01/2026', 'part' => 'Thân', 'exp' => '01/01/2028', 'origin' => 'Việt Nam', 'weight' => '2Kg', 'registration' => 'VT-002', 'standard' => ''],
            ['label_type' => 'vt', 'product' => 'Vị thuốc C', 'batch' => 'LOT-VT-C', 'scientific' => 'Radix C', 'mfg' => '01/01/2026', 'part' => 'Lá', 'exp' => '01/01/2028', 'origin' => 'Việt Nam', 'weight' => '3Kg', 'registration' => 'VT-003', 'standard' => ''],
            ['label_type' => 'vt', 'product' => 'Vị thuốc D', 'batch' => 'LOT-VT-D', 'scientific' => 'Radix D', 'mfg' => '01/01/2026', 'part' => 'Vỏ', 'exp' => '01/01/2028', 'origin' => 'Việt Nam', 'weight' => '4Kg', 'registration' => 'VT-004', 'standard' => ''],
            ['label_type' => 'vt', 'product' => 'Vị thuốc E', 'batch' => 'LOT-VT-E', 'scientific' => 'Radix E', 'mfg' => '01/01/2026', 'part' => 'Rễ', 'exp' => '01/01/2028', 'origin' => 'Việt Nam', 'weight' => '5Kg', 'registration' => 'VT-005', 'standard' => ''],
            ['label_type' => 'dl_n', 'product' => 'Dược liệu nội địa', 'batch' => 'LOT-DLN', 'scientific' => 'Herba domestica', 'mfg' => '01/01/2026', 'part' => 'Lá', 'exp' => '01/01/2028', 'origin' => 'Việt Nam', 'weight' => '1Kg', 'registration' => 'DLN-001', 'standard' => ''],
            ['label_type' => 'dl_b', 'product' => 'Dược liệu nhập khẩu', 'batch' => 'LOT-DLB', 'scientific' => 'Herba importata', 'mfg' => '01/01/2026', 'part' => 'Rễ', 'exp' => '01/01/2028', 'origin' => 'Trung Quốc', 'weight' => '1Kg', 'registration' => 'GPNK-001', 'standard' => ''],
        ];

        $path = app(SalesOrderLabelWorkbook::class)->create($labels);

        try {
            $workbook = IOFactory::load($path);
            $template = IOFactory::load(base_path('Nhan.xlsx'));
            $this->assertSame(['NhanVT', 'NhanDL_N', 'NhanDL_B', 'NhanVT 2'], $workbook->getSheetNames());
            $this->assertSame('Vị thuốc A', $workbook->getSheetByName('NhanVT')->getCell('H5')->getValue());
            $this->assertSame('Vị thuốc E', $workbook->getSheetByName('NhanVT 2')->getCell('H5')->getValue());
            $this->assertSame('Dược liệu nội địa', $workbook->getSheetByName('NhanDL_N')->getCell('H5')->getValue());
            $this->assertSame('Dược liệu nhập khẩu', $workbook->getSheetByName('NhanDL_B')->getCell('H5')->getValue());

            foreach ($workbook->getAllSheets() as $sheet) {
                $this->assertSame('A1:AS48', $sheet->getPageSetup()->getPrintArea());
                $this->assertSame('landscape', $sheet->getPageSetup()->getOrientation());
                $this->assertSame(9, $sheet->getPageSetup()->getPaperSize());
                $this->assertSame(1, $sheet->getPageSetup()->getFitToWidth());
                $this->assertSame(1, $sheet->getPageSetup()->getFitToHeight());
                $templateName = str_starts_with($sheet->getTitle(), 'NhanVT') ? 'NhanVT' : $sheet->getTitle();
                $this->assertSame(
                    $template->getSheetByName($templateName)->getStyle('H5')->getFont()->getName(),
                    $sheet->getStyle('H5')->getFont()->getName(),
                );
                $this->assertGreaterThan(0, $sheet->getDrawingCollection()->count());
            }
        } finally {
            @unlink($path);
        }
    }
}