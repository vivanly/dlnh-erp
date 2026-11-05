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

    public function test_import_excel_reads_production_orders_and_prefills_print_form(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $blocked = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);

        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $dm = $book->getActiveSheet()->setTitle('DM lệnh SX');
        $dm->fromArray(['Mã nhãn', 'Ngày/tháng', 'Lệnh sản xuất', 'Tên hàng', 'Lô', 'NSX', 'HD', 'Quy cách ĐG', 'Số Lượng', 'Số nhãn cần in', 'Số tờ'], null, 'A3');
        $dm->fromArray(['NH001', 46261, '26080001', 'TRẦN BÌ SAO VÀNG', '110826TRBIN-SV ', '=DATE(2000+MID(E4,5,2),MID(E4,3,2),LEFT(E4,2))', '=DATE(YEAR(F4)+2,MONTH(F4),DAY(F4)-1)', '2kg', '160kg', 80, '=J4/4'], null, 'A4');
        $dm->fromArray(['NH002', 46262, '26080002', 'HÀNG LẠ', '050126ABC', '', '', '1kg', '10kg', 10, ''], null, 'A5');
        $mh = $book->createSheet()->setTitle('MÃ HÀNG');
        $mh->fromArray(['STT', 'TÊN HÀNG', 'CHỦNG LOẠI', 'TÊN KHOA HỌC', 'TC ÁP DỤNG', 'NGUỒN GỐC', 'BỘ PHẬN DÙNG', 'SỐ ĐĂNG KÝ'], null, 'A2');
        $mh->fromArray([1, 'TRẦN BÌ  SAO VÀNG', 'Vị thuốc', 'Pericarpium Citri reticulatae perenne Praeparata', 'TCCS', 'Việt Nam', 'Vỏ quả', 'VCT-00716-26'], null, 'A3');
        $path = tempnam(sys_get_temp_dir(), 'dm') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
        $file = new \Illuminate\Http\UploadedFile($path, 'IN NHAN DU TRU.xlsx', null, null, true);

        $this->actingAs($blocked)->post(route('manual-labels.import'), ['file' => $file])->assertForbidden();
        $this->actingAs($user)->post(route('manual-labels.import'), ['file' => $file])->assertRedirect(route('manual-labels.import.form'));

        $this->assertDatabaseCount('manual_label_production_orders', 2);
        $this->assertDatabaseHas('manual_label_production_orders', ['label_code' => 'NH001', 'batch' => '110826TRBIN-SV', 'pack_spec' => '2kg', 'label_count' => 80]);
        $order = \App\Models\ManualLabelProductionOrder::where('label_code', 'NH001')->first();
        $this->assertSame('2026-08-11', $order->mfg_date->format('Y-m-d'));
        $this->assertSame('2028-08-10', $order->exp_date->format('Y-m-d'));

        $this->actingAs($user)->get(route('manual-labels.import.form'))->assertOk()->assertSee('2 lệnh sản xuất', false);
        $this->actingAs($user)->get(route('manual-labels.orders', ['q' => 'nh001']))->assertOk()->assertSee('TRẦN BÌ SAO VÀNG')->assertSee('Chưa in');
        $this->actingAs($user)->get(route('manual-labels.orders', ['q' => 'NH002']))->assertOk()->assertSee('chưa có mã hàng');
        $this->actingAs($blocked)->get(route('manual-labels.orders'))->assertForbidden();

        $this->actingAs($user)->get(route('manual-labels.create', ['order' => $order->id]))->assertOk()
            ->assertSee('value="110826TRBIN-SV"', false)->assertSee('value="VCT-00716-26"', false)
            ->assertSee('value="2 kg"', false)->assertSee('value="80"', false)->assertSee('value="TCCS"', false)
            ->assertSee('value="11/08/2026"', false)->assertSee('value="10/08/2028"', false)->assertSee('value="NH001"', false);
        $this->actingAs($blocked)->get(route('manual-labels.create', ['order' => $order->id]))->assertForbidden();

        $this->actingAs($user)->getJson(route('manual-labels.sources.search', ['q' => 'NH001']))
            ->assertOk()->assertJsonPath('0.label_type', 'vt')->assertJsonPath('0.registration', 'VCT-00716-26')->assertJsonPath('0.origin', 'Việt Nam');

        $this->actingAs($user)->post(route('manual-labels.store'), $this->payload(['label_code' => 'NH001', 'copies_count' => 30]))->assertOk();
        $this->actingAs($user)->get(route('manual-labels.orders', ['q' => 'NH001']))->assertSee('30 / 80')->assertSee('In thiếu 50');
    }
    public function test_manage_orders_and_products_crud(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $blocked = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);

        $this->actingAs($blocked)->get(route('manual-labels.products'))->assertForbidden();
        $this->actingAs($blocked)->post(route('manual-labels.orders.store'), [])->assertForbidden();

        $this->actingAs($user)->get(route('manual-labels.products.create'))->assertOk();
        $this->actingAs($user)->post(route('manual-labels.products.store'), ['name' => 'Bạch  Thược', 'origin' => 'Việt Nam'])->assertRedirect(route('manual-labels.products'));
        $this->actingAs($user)->post(route('manual-labels.products.store'), ['name' => 'bạch thược'])->assertSessionHasErrors();
        $product = \App\Models\ManualLabelProduct::firstOrFail();
        $this->actingAs($user)->get(route('manual-labels.products.edit', $product))->assertOk()->assertSee('Việt Nam');
        $this->actingAs($user)->put(route('manual-labels.products.update', $product), ['name' => 'Bạch Thược', 'registration' => 'VD-1'])->assertRedirect();
        $this->assertDatabaseHas('manual_label_products', ['id' => $product->id, 'registration' => 'VD-1']);
        $this->actingAs($user)->get(route('manual-labels.products', ['q' => 'VD-1']))->assertOk()->assertSee('Bạch Thược');

        $this->actingAs($user)->get(route('manual-labels.orders.create'))->assertOk();
        $this->actingAs($user)->post(route('manual-labels.orders.store'), ['label_code' => 'NH900', 'name' => 'Bạch Thược', 'batch' => '010126X', 'label_count' => 8, 'mfg_date' => '2026-01-01'])->assertRedirect(route('manual-labels.orders'));
        $this->actingAs($user)->post(route('manual-labels.orders.store'), ['label_code' => 'NH900', 'name' => 'X', 'label_count' => 1])->assertSessionHasErrors('label_code');
        $order = \App\Models\ManualLabelProductionOrder::firstOrFail();
        $this->actingAs($user)->get(route('manual-labels.orders.edit', $order))->assertOk()->assertSee('NH900');
        $this->actingAs($user)->put(route('manual-labels.orders.update', $order), ['label_code' => 'NH900', 'name' => 'Bạch Thược', 'label_count' => 12])->assertRedirect(route('manual-labels.orders'));
        $this->assertDatabaseHas('manual_label_production_orders', ['id' => $order->id, 'label_count' => 12]);
        $this->actingAs($user)->delete(route('manual-labels.orders.destroy', $order))->assertRedirect();
        $this->actingAs($user)->delete(route('manual-labels.products.destroy', $product))->assertRedirect();
        $this->assertDatabaseCount('manual_label_production_orders', 0);
        $this->assertDatabaseCount('manual_label_products', 0);
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

        $this->actingAs($user)->get(route('manual-labels.history'))->assertOk()->assertSee('Quản lý nhãn')->assertSee('Thực xuất')->assertSee('VT26010001');
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['kind' => 'return', 'quantity' => 5])->assertSessionHasErrors('quantity');
        $this->actingAs($user)->post(route('manual-labels.deliver', $print), ['kind' => 'return', 'quantity' => 1, 'customer' => 'Công ty A'])->assertRedirect();
        $print->refresh();
        $this->assertSame(1, $print->returned_count);
        $this->assertSame('Công ty A', $print->customer);
        $this->assertSame(1, $print->remaining_count);
        $this->actingAs($user)->get(route('manual-labels.history', ['q' => 'Công ty A', 'status' => 'stock']))->assertOk()->assertSee('VT26010001');
        $this->actingAs($user)->post(route('manual-labels.reprint', $print))->assertOk();

        $this->actingAs($user)->delete(route('manual-labels.destroy', $print))->assertRedirect();
        $this->assertDatabaseMissing('manual_label_prints', ['id' => $print->id]);
        $this->assertDatabaseCount('manual_label_deliveries', 0);
    }
}
