<?php

namespace Tests\Feature;

use App\Models\ManualLabelPrint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualLabelPrintTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'label_type' => 'dl_n',
            'product_name' => 'Bạch truật',
            'batch' => 'LOT-01',
            'copies_count' => 3,
            'qr_content' => 'https://example.com/lot-01',
            'order_code' => 'VT26010001',
        ], $overrides);
    }

    public function test_unauthorized_user_cannot_access(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);

        $this->actingAs($user)->get(route('manual-labels.create'))->assertForbidden();
        $this->actingAs($user)->post(route('manual-labels.store'), $this->payload())->assertForbidden();
        $this->actingAs($user)->get(route('manual-labels.history'))->assertForbidden();
    }

    public function test_import_excel_reads_pkn_sheet_and_search_returns_form_data(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $blocked = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);

        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('PKN');
        $sheet->fromArray(['Ngày nhận', 'VT/DL', 'Mã', 'Tên', 'Nguồn gốc', 'PP', 'Số lô', 'Ngày sản xuất', 'Hạn dùng'], null, 'E2');
        $sheet->fromArray(['', 'DL', 'DQ', 'Đương quy', 'B', '', 'LOT-DQ-1', 45506, 46600], null, 'E3');
        $sheet->fromArray(['', 'VT', 'BT', 'Bạch truật', 'N', '', 'LOT-BT-1', 45505, 46599], null, 'E4');
        $tt = $book->createSheet()->setTitle('TT');
        $tt->fromArray(['STT', 'TT05', 'Tên vị thuốc', 'Tên khoa học', 'Cây', 'Bộ phận dùng', 'Nguồn gốc'], null, 'A3');
        $tt->fromArray(['', 1, 'Đương quy', 'Radix Angelicae sinensis', '', 'Rễ', 'Trung Quốc'], null, 'A4');
        $dh = $book->createSheet()->setTitle('DataChitietDH');
        $dh->fromArray(['Stt', 'Tên', 'Đvt', 'Nguồn gốc', 'SL', 'QCĐG', 'Gói TP', 'YCBC', 'Ghi chú', 'Mã ĐH', 'Khách hàng', 'Ngày nhận', 'NTT', 'Loại ĐH', 'Địa chỉ', 'Số lô', 'PP', 'Mã', 'Lẻ/Thầu', 'Ngày giao', 'Ngày SX', 'Hạn dùng'], null, 'A3');
        $dh->fromArray([1, 'Đương quy', 'Kg', 'B', 10, 2, 5, '', 'F', 'VT26010001', 'Chị Lan', 46024, '', 'VT', '', 'LOT-DQ-1', '', 'DQ', 'Khách lẻ', 46030, 45506, 46600], null, 'A4');
        $path = tempnam(sys_get_temp_dir(), 'pkn') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
        $file = new \Illuminate\Http\UploadedFile($path, 'IN NHAN.xlsx', null, null, true);

        $this->actingAs($blocked)->post(route('manual-labels.import'), ['file' => $file])->assertForbidden();
        $this->actingAs($user)->post(route('manual-labels.import'), ['file' => $file])->assertRedirect(route('manual-labels.import.form'));

        $this->assertDatabaseCount('manual_label_sources', 2);
        $this->actingAs($user)->get(route('manual-labels.import.form'))->assertOk()->assertSee('LOT-DQ-1');
        $this->actingAs($user)->get(route('manual-labels.create'))->assertOk()->assertSee('Tìm từ dữ liệu Excel');
        $this->assertDatabaseCount('manual_label_order_lines', 1);
        $line = \App\Models\ManualLabelOrderLine::first();
        $this->actingAs($user)->get(route('manual-labels.orders', ['code' => 'vt26010001']))->assertOk()
            ->assertSee('Chị Lan')->assertSee('Radix Angelicae sinensis')->assertSee('LOT-DQ-1');
        $this->actingAs($blocked)->get(route('manual-labels.orders'))->assertForbidden();
        $this->actingAs($user)->get(route('manual-labels.create', ['line' => $line->id]))->assertOk()
            ->assertSee('value="LOT-DQ-1"', false)->assertSee('value="Radix Angelicae sinensis"', false)
            ->assertSee('value="2 kg"', false)->assertSee('value="5"', false)->assertSee('01/08/2027');
        $this->actingAs($blocked)->get(route('manual-labels.create', ['line' => $line->id]))->assertForbidden();
        $this->actingAs($user)->getJson(route('manual-labels.sources.search', ['q' => 'duong']))->assertOk();
        $this->actingAs($user)->getJson(route('manual-labels.sources.search', ['q' => 'LOT-DQ']))
            ->assertOk()->assertJsonPath('0.label_type', 'dl_b')->assertJsonPath('0.product_name', 'Đương quy')
            ->assertJsonPath('0.mfg_date', '02/08/2024')->assertJsonPath('0.exp_date', '01/08/2027');
    }

    public function test_authorized_user_prints_and_delivers_labels(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);

        $this->actingAs($user)->get(route('manual-labels.create'))->assertOk();
        $this->actingAs($user)->post(route('manual-labels.store'), $this->payload())
            ->assertOk()->assertSee('BẠCH TRUẬT')->assertSee('https://example.com/lot-01', false);

        $print = ManualLabelPrint::firstOrFail();
        $this->assertSame(3, $print->copies_count);

        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['quantity' => 4])->assertSessionHasErrors('quantity');
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['quantity' => 2])->assertRedirect();
        $this->assertSame(1, $print->fresh()->remaining_count);
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['quantity' => 1]);
        $this->assertSame(0, $print->fresh()->remaining_count);

        $this->actingAs($user)->get(route('manual-labels.history'))->assertOk()->assertSee('Đã giao hết')->assertSee('VT26010001');
        $this->actingAs($user)->post(route('manual-labels.reprint', $print))->assertOk();

        $this->actingAs($user)->delete(route('manual-labels.destroy', $print))->assertRedirect();
        $this->assertDatabaseMissing('manual_label_prints', ['id' => $print->id]);
        $this->assertDatabaseCount('manual_label_deliveries', 0);
    }
}
