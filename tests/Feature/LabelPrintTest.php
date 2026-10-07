<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SalesOrderLabelPrint;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Models\TraceabilityLotCode;
use App\Models\TraceabilitySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LabelPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_print_uses_excel_template_tracks_required_and_additional_copies(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'LABEL-ORDER-CUST', 'name' => 'Label Order Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Label test herb',
            'slug' => 'label-test-herb',
            'part_used' => 'Rễ',
            'origin' => 'Việt Nam',
            'unit' => 'kg',
            'classification' => 'DL',
            'sku' => 'LABEL-TEST-1',
            'scientific_name' => 'Radix testii',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'LABEL-LOT-001',
            'initial_quantity' => 12,
            'current_quantity' => 12,
            'mfg_date' => today()->subMonth(),
            'exp_date' => today()->addYear(),
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-LABEL-PRINT-001',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'pending_planning',
            'qa_confirmed_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'packaging_spec' => '1',
            'finished_quantity' => 3,
        ]);
        $allocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 3,
            'status' => 'reserved',
        ]);
        TraceabilitySetting::query()->create(['base_url' => 'https://trace.example.test/code=']);
        TraceabilityLotCode::query()->create([
            'supplier_batch_id' => $batch->id,
            'trace_code' => 'LOT-QR-001',
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('labels.index'))
            ->assertOk()
            ->assertSee($order->order_code)
            ->assertSee('3');

        $this->actingAs($warehouseUser)
            ->get(route('labels.orders.show', $order))
            ->assertOk()
            ->assertSee('LABEL-LOT-001')
            ->assertSee('Tải mẫu Excel để in')
            ->assertSee('Dược liệu trong nước')
            ->assertSee('QCĐG 1');

        $excelResponse = $this->actingAs($warehouseUser)
            ->post(route('labels.orders.print', $order), [
                'additional' => [$allocation->id => 0],
            ]);
        $excelResponse->assertDownload('Nhan-SO-LABEL-PRINT-001.xlsx');

        $this->assertSame(3, (int) SalesOrderLabelPrint::sum('copies_count'));
        $this->assertSame('required', SalesOrderLabelPrint::sole()->print_kind);
        $workbook = IOFactory::load($excelResponse->baseResponse->getFile()->getPathname());
        $printSetup = $workbook->getSheetByName('NhanDL_N')->getPageSetup();
        $this->assertSame('A1:AS48', $printSetup->getPrintArea());
        $this->assertTrue($printSetup->getFitToPage());
        $this->assertSame(1, $printSetup->getFitToWidth());
        $this->assertSame(1, $printSetup->getFitToHeight());
        $this->assertTrue($printSetup->getHorizontalCentered());
        $qrCoordinates = collect($workbook->getSheetByName('NhanDL_N')->getDrawingCollection())
            ->map(fn ($drawing) => $drawing->getCoordinates())
            ->filter(fn ($coordinate) => in_array($coordinate, ['Q18', 'AN18', 'Q42', 'AN42'], true));
        $this->assertCount(3, $qrCoordinates);

        $additionalExcelResponse = $this->actingAs($warehouseUser)
            ->post(route('labels.orders.print', $order), [
                'additional' => [$allocation->id => 1],
                'additional_reason' => 'In bổ sung theo mẫu Excel',
            ]);
        $additionalExcelResponse->assertDownload('Nhan-SO-LABEL-PRINT-001.xlsx');
        $additionalWorkbook = IOFactory::load($additionalExcelResponse->baseResponse->getFile()->getPathname());
        $additionalSheet = $additionalWorkbook->getSheetByName('NhanDL_N');
        $templateSheet = IOFactory::load(base_path('Nhan.xlsx'))->getSheetByName('NhanDL_N');
        $this->assertSame('Label test herb', $additionalSheet->getCell('H5')->getValue());
        $this->assertSame($templateSheet->getStyle('H5')->getFont()->getName(), $additionalSheet->getStyle('H5')->getFont()->getName());
        $this->assertSame($templateSheet->getStyle('H5')->getFont()->getSize(), $additionalSheet->getStyle('H5')->getFont()->getSize());
        $this->assertGreaterThan(0, $additionalSheet->getDrawingCollection()->count());
        $this->assertNotNull(collect($additionalSheet->getDrawingCollection())->first(
            fn ($drawing) => $drawing->getCoordinates() === 'Q18'
        ));
        $this->assertSame('LOT-QR-001', TraceabilityLotCode::query()->where('supplier_batch_id', $batch->id)->value('trace_code'));

        $this->actingAs($warehouseUser)
            ->post(route('labels.orders.print', $order), [
                'additional' => [$allocation->id => 2],
                'additional_reason' => 'Hai tem bị hỏng khi dán',
            ])
            ->assertDownload('Nhan-SO-LABEL-PRINT-001.xlsx');

        $this->assertSame(6, (int) SalesOrderLabelPrint::sum('copies_count'));
        $this->assertSame(3, (int) SalesOrderLabelPrint::where('print_kind', 'additional')->sum('copies_count'));
        $this->assertSame('Hai tem bị hỏng khi dán', SalesOrderLabelPrint::where('reason', 'Hai tem bị hỏng khi dán')->sole()->reason);

        $htmlResponse = $this->actingAs($warehouseUser)
            ->post(route('labels.orders.print-direct', $order), [
                'preview' => 1,
                'copies' => [$allocation->id => 2],
                'additional_reason' => 'Xem trước',
            ]);
        $htmlResponse->assertOk()->assertSee('LABEL TEST HERB');
        $this->assertSame(2, substr_count($htmlResponse->getContent(), 'class="label"'));
        $this->assertSame(6, (int) SalesOrderLabelPrint::sum('copies_count'));

        $htmlResponse = $this->actingAs($warehouseUser)
            ->post(route('labels.orders.print-direct', $order), [
                'additional' => [$allocation->id => 1],
                'additional_reason' => 'In trực tiếp ra máy in',
                'standard' => [$allocation->id => 'TCCS-TEST'],
                'storage' => [$allocation->id => 'Noi kho rao'],
            ]);
        $htmlResponse->assertOk()->assertSee('NGUYÊN LIỆU LÀM THUỐC - DƯỢC LIỆU TRONG NƯỚC')->assertSee('LABEL TEST HERB')->assertSee('LABEL-LOT-001')->assertSee('Radix testii')->assertSee('QUÉT QR TRUY XUẤT')->assertSee('HỒ SƠ')->assertSee('TCCS-TEST')->assertSee('Noi kho rao')->assertSee('data-qr-value="https://trace.example.test/code=LOT-QR-001"', false);
        $this->assertSame(7, (int) SalesOrderLabelPrint::sum('copies_count'));

        $this->actingAs($warehouseUser)
            ->postJson(route('labels.orders.print-direct', $order), ['additional' => [$allocation->id => 1]])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cần ghi lý do khi yêu cầu in bổ sung nhãn.');
        $this->assertSame(7, (int) SalesOrderLabelPrint::sum('copies_count'));

        $this->actingAs($warehouseUser)
            ->get(route('labels.orders.show', $order))
            ->assertOk()
            ->assertSee('7')
            ->assertSee('In bổ sung theo mẫu Excel')
            ->assertSee('Hai tem bị hỏng khi dán')
            ->assertSee('In bổ sung');

        $this->actingAs(User::factory()->create(['role' => 'it']))
            ->delete(route('order-items.destroy', $item))
            ->assertRedirect(route('orders.show', $order));
        $this->assertNotNull($item->fresh());
        $this->assertSame(7, (int) SalesOrderLabelPrint::sum('copies_count'));

        $this->assertEquals(12, $batch->fresh()->current_quantity);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_standard_labels_cannot_be_printed_from_a_lot_pending_qa(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'LABEL-PENDING-CUST', 'name' => 'Pending Label Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Pending label herb',
            'slug' => 'pending-label-herb',
            'unit' => 'kg',
            'classification' => 'DL',
            'sku' => 'LABEL-PENDING-1',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'LABEL-PENDING-LOT',
            'initial_quantity' => 5,
            'current_quantity' => 5,
            'status' => 'pending_qa',
        ]);
        $order = Order::create([
            'order_code' => 'SO-LABEL-PENDING-001',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'packaging_spec' => '1',
            'finished_quantity' => 5,
        ]);
        $allocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 5,
            'status' => 'reserved',
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('labels.orders.show', $order))
            ->assertOk()
            ->assertSee('LABEL-PENDING-LOT')
            ->assertSee('Chờ QA');

        $this->actingAs($warehouseUser)
            ->post(route('labels.orders.print', $order), [
                'additional' => [$allocation->id => 0],
            ])
            ->assertRedirect();

        $this->assertSame('pending_qa', $batch->fresh()->status);
        $this->assertSame(0, SalesOrderLabelPrint::count());
    }

    public function test_assigned_lot_can_be_printed_before_full_allocation_or_production_completion(): void
    {
        $user = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'LABEL-PARTIAL-CUST', 'name' => 'Partial Label Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Partially allocated herb',
            'slug' => 'partially-allocated-herb',
            'origin' => 'Việt Nam',
            'unit' => 'kg',
            'classification' => 'DL',
            'sku' => 'LABEL-PARTIAL-1',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'LABEL-PARTIAL-LOT',
            'initial_quantity' => 10,
            'current_quantity' => 10,
            'mfg_date' => today()->subMonth(),
            'exp_date' => today()->addYear(),
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-LABEL-PARTIAL-001',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'processing',
            'qa_confirmed_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'packaging_spec' => '1',
            'finished_quantity' => 0,
        ]);
        $allocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 2,
            'status' => 'reserved',
        ]);

        $this->actingAs($user)
            ->get(route('labels.orders.show', $order))
            ->assertOk()
            ->assertSee('LABEL-PARTIAL-LOT')
            ->assertSee('value="0"', false);

        $this->actingAs($user)
            ->post(route('labels.orders.print-direct', $order), [
                'preview' => 1,
                'copies' => [$allocation->id => 2],
            ])
            ->assertOk()
            ->assertSee('PARTIALLY ALLOCATED HERB');

        $this->assertSame(0, SalesOrderLabelPrint::count());

        $this->actingAs($user)
            ->post(route('labels.orders.print-direct', $order), [
                'copies' => [$allocation->id => 2],
            ])
            ->assertOk()
            ->assertSee('PARTIALLY ALLOCATED HERB');

        $this->assertSame(2, (int) SalesOrderLabelPrint::sum('copies_count'));
    }

    public function test_order_products_select_vt_domestic_and_china_label_sheets_from_product_data(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'production']);
        $customer = Customer::create(['code' => 'LABEL-TYPES-CUST', 'name' => 'Label Types Customer', 'type' => 'Retail']);
        $order = Order::create([
            'order_code' => 'SO-LABEL-TYPES-001',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);

        $products = [
            ['name' => 'Domestic D', 'classification' => 'D', 'origin' => 'Việt Nam'],
            ['name' => 'Domestic DL', 'classification' => 'DL', 'origin' => 'Việt Nam'],
            ['name' => 'China DL', 'classification' => 'DL', 'origin' => 'Trung Quốc'],
            ['name' => 'Traditional VT', 'classification' => 'VT', 'origin' => 'Việt Nam'],
            ['name' => 'China DL GPNK fallback', 'classification' => 'DL', 'origin' => 'Trung Quốc'],
        ];
        $allocations = [];
        foreach ($products as $index => $data) {
            $product = Product::create($data + [
                'slug' => 'label-type-' . $index,
                'unit' => 'kg',
                'sku' => 'LABEL-TYPE-' . $index,
            ]);
            $batch = SupplierBatch::create([
                'goods_receipt_item_id' => 1,
                'product_id' => $product->id,
                'batch_number' => 'LABEL-TYPE-LOT-' . $index,
                'initial_quantity' => 1,
                'current_quantity' => 1,
                'mfg_date' => today()->subMonth(),
                'exp_date' => today()->addYear(),
                'status' => 'active',
            ]);
            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'packaging_spec' => '1',
                'finished_quantity' => 1,
            ]);
            $allocation = SalesOrderLotAllocation::create([
                'order_item_id' => $item->id,
                'supplier_batch_id' => $batch->id,
                'reserved_quantity' => 1,
                'status' => 'reserved',
            ]);
            $allocations[] = $allocation->id;
        }

        $chinaProduct = Product::where('name', 'China DL')->firstOrFail();
        $chinaProduct->regulatoryDocuments()->create([
            'document_type' => 'gpnk',
            'document_number' => 'GPNK-LABEL-001',
            'document_date' => '2026-01-10',
            'document_form' => 'Giấy phép nhập khẩu',
        ]);
        $chinaProduct->regulatoryDocuments()->create([
            'document_type' => 'cong_bo',
            'document_number' => 'SCB-LABEL-001',
            'document_date' => '2026-01-09',
            'document_form' => 'Tự công bố',
        ]);
        $gpnkFallbackProduct = Product::where('name', 'China DL GPNK fallback')->firstOrFail();
        $gpnkFallbackProduct->regulatoryDocuments()->create([
            'document_type' => 'gpnk',
            'document_number' => 'GPNK-LABEL-FALLBACK',
            'document_date' => '2026-01-10',
            'document_form' => 'Giấy phép nhập khẩu',
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('labels.orders.show', $order))
            ->assertOk()
            ->assertSee('Domestic D')
            ->assertSee('Dược liệu trong nước')
            ->assertSee('Dược liệu Trung Quốc')
            ->assertSee('Vị thuốc cổ truyền');

        $response = $this->actingAs($warehouseUser)
            ->post(route('labels.orders.print', $order), [
                'additional' => array_fill_keys($allocations, 0),
            ]);
        $response->assertDownload('Nhan-SO-LABEL-TYPES-001.xlsx');

        $this->assertSame(5, (int) SalesOrderLabelPrint::sum('copies_count'));

        $downloadPath = $response->baseResponse->getFile()->getPathname();
        try {
            $workbook = IOFactory::load($downloadPath);
            $this->assertSame(['NhanVT', 'NhanDL_N', 'NhanDL_B'], $workbook->getSheetNames());
            $this->assertSame('Traditional VT', $workbook->getSheetByName('NhanVT')->getCell('H5')->getValue());
            $this->assertSame('Domestic D', $workbook->getSheetByName('NhanDL_N')->getCell('H5')->getValue());
            $this->assertSame('China DL', $workbook->getSheetByName('NhanDL_B')->getCell('H5')->getValue());
            $this->assertSame('SCB-LABEL-001', $workbook->getSheetByName('NhanDL_B')->getCell('H13')->getValue());
            $this->assertSame('GPNK-LABEL-FALLBACK', $workbook->getSheetByName('NhanDL_B')->getCell('AE13')->getValue());
        } finally {
            @unlink($downloadPath);
        }
    }
}