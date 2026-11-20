<?php

namespace Tests\Feature;

use App\Exports\ManualLabelOrdersExport;
use App\Models\ManualLabelProductionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ManualLabelOrderExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_existing_and_file_duplicates_and_reports_invalid_rows(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $file = UploadedFile::fake()->createWithContent('orders.csv',
            "production_order,order_date,name,batch,mfg_date,exp_date,pack_spec,quantity,label_type,traditional_medicine_name\n".
            "SX01,19/11/2026,Atiso,LOT1,01/11/2026,01/11/2028,2,10,vt,1\n".
            "SX01,19/11/2026,atiso,LOT1,01/11/2026,01/11/2028,2,20,vt,1\n".
            "SX01,19/11/2026,Atiso,LOT2,31/02/2026,01/11/2028,2,10,vt,1\n".
            "SX01,19/11/2026,Atiso,LOT3,01/11/2026,01/11/2028,2,10,vt,1\n"
        );
        $response = $this->actingAs($user)->post(route('manual-labels.orders.import'), ['file' => $file]);
        $response->assertRedirect(route('manual-labels.orders.import.form'))
            ->assertSessionHas('warning', 'Đã import 2 dòng; bỏ qua 1 dòng trùng và 1 dòng không hợp lệ.');
        $this->assertSame(4, session('import_errors')[0]['row']);
        $this->assertDatabaseCount('manual_label_production_orders', 2);
        $order = ManualLabelProductionOrder::where('batch', 'LOT1')->firstOrFail();
        $this->assertSame('2026-11-19', $order->order_date->toDateString());
        $this->assertSame('2026-11-01', $order->mfg_date->toDateString());
        $this->assertSame('10 KG', $order->quantity);
        $this->assertSame(5, $order->label_count);

        $again = UploadedFile::fake()->createWithContent('orders.csv',
            "production_order,name,batch,pack_spec,quantity,label_type\nSX01,ATISO,LOT1,2,40,vt\n");
        $this->actingAs($user)->post(route('manual-labels.orders.import'), ['file' => $again])
            ->assertSessionHas('success', 'Đã import 0 dòng; bỏ qua 1 dòng trùng và 0 dòng không hợp lệ.');
        $this->assertSame('10 KG', $order->fresh()->quantity);
    }

    public function test_import_rejects_missing_headers_and_requires_permission(): void
    {
        $file = UploadedFile::fake()->createWithContent('orders.csv', "name\nAtiso\n");
        $blocked = User::factory()->create(['role' => 'production', 'can_print_labels' => false]);
        $this->actingAs($blocked)->get(route('manual-labels.orders.import.form'))->assertForbidden();
        $this->actingAs($blocked)->post(route('manual-labels.orders.import'), ['file' => $file])->assertForbidden();
        $this->actingAs($blocked)->get(route('manual-labels.orders.export', ['period' => 'day', 'start' => '2026-11-19']))->assertForbidden();
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $this->actingAs($user)->post(route('manual-labels.orders.import'), ['file' => $file])->assertSessionHasErrors('file');
    }

    public function test_export_filters_each_period_by_order_date(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        foreach (['2026-10-31', '2026-11-15', '2026-11-16', '2026-11-19', '2026-11-22', '2026-11-23', '2026-12-01', null] as $i => $date) {
            ManualLabelProductionOrder::create(['label_code' => sprintf('NH%06d', $i + 1), 'order_date' => $date, 'production_order' => 'SX', 'name' => 'Atiso', 'batch' => 'LOT'.$i]);
        }
        foreach ([
            [['period' => 'day', 'start' => '2026-11-19'], ['2026-11-19']],
            [['period' => 'week', 'start' => '2026-11-19'], ['2026-11-16', '2026-11-19', '2026-11-22']],
            [['period' => 'month', 'start' => '2026-11-19'], ['2026-11-15', '2026-11-16', '2026-11-19', '2026-11-22', '2026-11-23']],
            [['period' => 'range', 'start' => '2026-11-19', 'end' => '2026-11-23'], ['2026-11-19', '2026-11-22', '2026-11-23']],
        ] as [$query, $dates]) {
            Excel::fake();
            $this->actingAs($user)->get(route('manual-labels.orders.export', $query))->assertOk();
            Excel::assertDownloaded('lenh-san-xuat.xlsx', function (ManualLabelOrdersExport $export) use ($dates) {
                $this->assertSame($dates, $export->query()->get()->map(fn ($order) => $order->order_date->toDateString())->all());

                return true;
            });
        }
        $this->actingAs($user)->get(route('manual-labels.orders.export', ['period' => 'range', 'start' => '2026-11-19', 'end' => '2026-11-18']))
            ->assertSessionHasErrors('end');
    }

    public function test_browser_dates_are_displayed_and_saved_in_day_month_year_order(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $line = ['name' => 'Atiso', 'batch' => 'LOT1', 'mfg_date' => '03/04/2026', 'exp_date' => '04/05/2027', 'pack_spec' => '1', 'quantity' => '5', 'label_type' => 'vt'];
        $this->actingAs($user)->get(route('manual-labels.orders.create'))->assertSee('placeholder="dd/mm/yyyy"', false);
        $this->actingAs($user)->post(route('manual-labels.orders.store'), ['production_order' => 'SX01', 'order_date' => '19/11/2026', 'lines' => [$line]])
            ->assertRedirect(route('manual-labels.orders'))->assertSessionHasNoErrors();
        $order = ManualLabelProductionOrder::firstOrFail();
        $this->assertSame('2026-04-03', $order->mfg_date->toDateString());
        $this->actingAs($user)->get(route('manual-labels.orders.edit', $order))->assertSee('value="03/04/2026"', false);
        $this->actingAs($user)->put(route('manual-labels.orders.update', $order), $line + ['production_order' => 'SX01', 'order_date' => '20/11/2026'])
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-11-20', $order->fresh()->order_date->toDateString());
        $this->actingAs($user)->post(route('manual-labels.orders.store'), ['production_order' => 'SX02', 'order_date' => '31/02/2026', 'lines' => [$line]])
            ->assertSessionHasErrors('order_date');
        $this->actingAs($user)->post(route('manual-labels.orders.store'), ['production_order' => 'SX02', 'lines' => [array_merge($line, ['exp_date' => '02/04/2026'])]])
            ->assertSessionHasErrors('lines.0.exp_date');
    }

    public function test_excel_export_formats_dates_and_can_be_imported_again(): void
    {
        $user = User::factory()->create(['role' => 'production', 'can_print_labels' => true]);
        $order = ManualLabelProductionOrder::create([
            'label_code' => 'NH000001', 'production_order' => '000123', 'name' => 'Atiso', 'batch' => '001',
            'order_date' => '2026-11-19', 'mfg_date' => '2026-04-03', 'exp_date' => '2027-05-04',
            'pack_spec' => '1 KG', 'quantity' => '5 KG', 'label_count' => 5, 'label_type' => 'vt',
        ]);
        $contents = Excel::raw(new ManualLabelOrdersExport('2026-11-01', '2026-11-30'), \Maatwebsite\Excel\Excel::XLSX);
        $this->actingAs($user)->get(route('manual-labels.orders.export', ['period' => 'day', 'start' => '19/11/2026']))
            ->assertOk()->assertDownload('lenh-san-xuat.xlsx');
        $path = tempnam(sys_get_temp_dir(), 'label');
        try {
            file_put_contents($path, $contents);
            $sheet = IOFactory::createReader('Xlsx')->load($path)->getActiveSheet();
            $this->assertSame('19/11/2026', $sheet->getCell('C2')->getFormattedValue());
            $this->assertSame('03/04/2026', $sheet->getCell('F2')->getFormattedValue());
            $this->assertSame('04/05/2027', $sheet->getCell('G2')->getFormattedValue());
            $this->assertSame('000123', $sheet->getCell('B2')->getValue());
        } finally {
            unlink($path);
        }
        $order->delete();
        $file = UploadedFile::fake()->createWithContent('orders.xlsx', $contents);
        $this->actingAs($user)->post(route('manual-labels.orders.import'), ['file' => $file])
            ->assertSessionHas('success', 'Đã import 1 dòng; bỏ qua 0 dòng trùng và 0 dòng không hợp lệ.');
        $imported = ManualLabelProductionOrder::firstOrFail();
        $this->assertSame('000123', $imported->production_order);
        $this->assertSame('001', $imported->batch);
        $this->assertSame('2026-04-03', $imported->mfg_date->toDateString());
    }
}
