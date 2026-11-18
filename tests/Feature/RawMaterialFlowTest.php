<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Accessory;
use App\Models\Department;
use App\Models\Excipient;
use App\Models\Ingredient;
use App\Models\MaterialLot;
use App\Models\MaterialReceipt;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PackagingMaterial;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawMaterialFlowTest extends TestCase
{
    use RefreshDatabase;

    private function itUser(): User
    {
        $department = Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);

        return User::factory()->create(['department_id' => $department->id, 'role' => 'it']);
    }

    private function qaThenQcApprove(User $user, MaterialLot $lot, string $batch): void
    {
        if ($lot->status === 'temporary_stored') {
            $this->actingAs($user)->post(route('material-lots.move-from-temporary', $lot))->assertSessionHas('success');
            $lot->refresh();
        }
        if ($lot->material_type === 'raw_material') {
            $this->uploadCoa($user, $lot, $batch);
            $this->actingAs($user)->post(route('material-lots.inspect', $lot), $this->qcInspectionPayload())
                ->assertSessionHas('success');
        } else {
            $this->actingAs($user)->post(route('material-lots.approve', $lot))->assertSessionHas('success');
        }
        $this->actingAs($user)->post(route('material-lots.stock-receipt.post', $lot))->assertSessionHas('success');
    }

    private function qcInspectionPayload(string $result = 'passed'): array
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        return [
            'result' => $result,
            'qc_test_report' => 'PKN-RAW-001',
            'qc_date' => today()->toDateString(),
            'qc_test_report_file' => \Illuminate\Http\UploadedFile::fake()->create('qc-report.pdf', 10, 'application/pdf'),
            'qc_note' => $result === 'failed' ? 'Không đạt chỉ tiêu' : 'Đạt yêu cầu',
        ];
    }

    private function uploadCoa(User $user, MaterialLot $lot, string $batch = ''): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $payload = ['coa_file' => \Illuminate\Http\UploadedFile::fake()->create('coa.pdf', 10, 'application/pdf')];
        if ($lot->material_type === 'raw_material') {
            $payload += ['batch_number' => $batch, 'mfg_date' => '2026-01-01', 'exp_date' => '2028-01-01'];
        }
        $this->actingAs($user)->patch(route('material-lots.update-coa', $lot), $payload)->assertSessionHas('success');
    }

    private function receiptPayload(array $items): array
    {
        return ['items' => $items];
    }

    public function test_mixed_purchase_order_receives_materials_into_stock_and_completes(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC9']);
        $raw = RawMaterial::create(['sku' => 'DLT100', 'name' => 'DLT', 'slug' => 'dlt-100', 'unit' => 'Kg']);

        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-NL-1', 'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(),
            'items' => [['item_type' => 'raw_material', 'item_id' => $raw->id, 'quantity' => 10, 'unit_price' => 5]],
        ])->assertSessionHasNoErrors();
        $po = PurchaseOrder::where('po_number', 'PO-NL-1')->firstOrFail();
        $po->update(['status' => 'delivered']);
        $itemId = $po->items()->firstOrFail()->id;

        $this->actingAs($user)->get(route('material-receipts.create', $po))
            ->assertOk()
            ->assertDontSee('preliminary_qa_by', false)
            ->assertDontSee('preliminary_qc_by', false);
        $receiptResponse = $this->actingAs($user)->post(route('material-receipts.store', $po), $this->receiptPayload([
            $itemId => ['received_quantity' => 8, 'returned_quantity' => 2],
        ]))->assertSessionHasNoErrors();
        $receipt = MaterialReceipt::firstOrFail();
        $receiptResponse->assertRedirect(route('material-receipts.show', $receipt));
        $this->actingAs($user)->get(route('material-receipts.show', $receipt))
            ->assertOk()
            ->assertSee($receipt->receipt_code);

        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $lot = MaterialLot::firstOrFail();
        $this->qaThenQcApprove($user, $lot, 'L1');
        $this->assertSame(8.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->assertSame('completed', $po->fresh()->status);
        $this->actingAs($user)->get(route('warehouse.material-stock'))->assertOk()->assertSee('DLT100');
        $this->actingAs($user)->get(route('purchase-orders.show', $po))->assertOk()->assertSee('8.00');
    }

    public function test_ingredients_accessories_excipients_and_packaging_have_separate_stock(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC materials', 'code' => 'NCC-MAT']);
        $ingredient = Ingredient::create(['sku' => 'MAT-ING', 'name' => 'Nguyen lieu', 'slug' => 'mat-ing', 'unit' => 'Kg']);
        $accessory = Accessory::create(['sku' => 'MAT-ACC', 'name' => 'Phu lieu', 'slug' => 'mat-acc', 'unit' => 'Kg']);
        $excipient = Excipient::create(['sku' => 'MAT-EXC', 'name' => 'Ta duoc', 'slug' => 'mat-exc', 'unit' => 'Kg']);
        $packaging = PackagingMaterial::create(['sku' => 'MAT-PKG', 'name' => 'Bao bi', 'slug' => 'mat-pkg', 'unit' => 'Cai']);

        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-MATERIAL-CATEGORIES',
            'supplier_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'items' => [
                ['item_type' => 'ingredient', 'item_id' => $ingredient->id, 'quantity' => 3, 'unit_price' => 10],
                ['item_type' => 'accessory', 'item_id' => $accessory->id, 'quantity' => 2, 'unit_price' => 10],
                ['item_type' => 'excipient', 'item_id' => $excipient->id, 'quantity' => 4, 'unit_price' => 10],
                ['item_type' => 'packaging', 'item_id' => $packaging->id, 'quantity' => 5, 'unit_price' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $purchaseOrder = PurchaseOrder::where('po_number', 'PO-MATERIAL-CATEGORIES')->firstOrFail();
        $purchaseOrder->update(['status' => 'delivered']);
        $purchaseItems = $purchaseOrder->items()->get();
        $this->actingAs($user)->get(route('material-receipts.create', $purchaseOrder))
            ->assertOk()
            ->assertSee('MAT-EXC')
            ->assertSee('MAT-PKG');
        $this->actingAs($user)->post(route('material-receipts.store', $purchaseOrder), $this->receiptPayload(
            $purchaseItems->mapWithKeys(fn ($item) => [$item->id => [
                'received_quantity' => $item->quantity,
                'returned_quantity' => 0,
            ]])->all(),
        ))->assertSessionHasNoErrors();

        $itemIds = $purchaseItems->modelKeys();
        foreach (MaterialLot::whereIn('purchase_order_item_id', $itemIds)->get() as $lot) {
            $this->qaThenQcApprove($user, $lot, '');
        }

        $this->assertSame(3.0, MaterialStockMovement::balance('ingredient', $ingredient->id));
        $this->assertSame(2.0, MaterialStockMovement::balance('accessory', $accessory->id));
        $this->assertSame(4.0, MaterialStockMovement::balance('excipient', $excipient->id));
        $this->assertSame(5.0, MaterialStockMovement::balance('packaging', $packaging->id));
        $this->actingAs($user)->get(route('warehouse.material-stock'))->assertOk()->assertSee('MAT-EXC')->assertSee('MAT-PKG');
    }

    public function test_raw_material_sales_order_flows_to_warehouse_issue(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'NL200', 'name' => 'NL2', 'slug' => 'nl-200', 'unit' => 'Kg']);
        MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => 5]);
        $customer = Customer::create(['code' => 'KH1', 'name' => 'KH', 'type' => 'Retail']);

        $orderCode = 'DLT' . today()->format('ym') . '0001';
        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => $orderCode, 'customer_id' => $customer->id, 'order_type' => 'DLT',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['item_id' => $raw->id, 'quantity' => 6]],
        ])->assertSessionHasNoErrors();
        $order = Order::where('order_code', $orderCode)->firstOrFail();
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'raw_material_id' => $raw->id, 'product_id' => null]);

        $this->actingAs($user)->post(route('orders.submit-sales', $order))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('orders.approve-sales', $order))->assertSessionHasNoErrors();
        $this->assertSame('pending_material_issue', $order->fresh()->status);
        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee('NL200');
        $this->actingAs($user)->get(route('warehouse.material-issues'))->assertOk()->assertSee($orderCode);

        $this->actingAs($user)->post(route('warehouse.material-issues.issue', $order))->assertSessionHas('error');
        $this->assertSame('pending_material_issue', $order->fresh()->status);

        MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => 5]);
        $this->actingAs($user)->post(route('warehouse.material-issues.issue', $order))->assertSessionHas('success');
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(4.0, MaterialStockMovement::balance('raw_material', $raw->id));
    }

    public function test_herbal_sales_order_still_uses_products_and_legacy_payload(): void
    {
        $user = $this->itUser();
        $product = Product::create(['name' => 'SP', 'slug' => 'sp-x', 'sku' => 'SPX', 'unit' => 'Kg']);
        $customer = Customer::create(['code' => 'KH2', 'name' => 'KH', 'type' => 'Retail']);

        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => 'DL' . today()->format('ym') . '0001', 'customer_id' => $customer->id, 'order_type' => 'DL',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'raw_material_id' => null]);
    }

    public function test_supplier_return_uses_rejected_material_lot_and_blocks_over_return(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC8']);
        $raw = RawMaterial::create(['sku' => 'NL300', 'name' => 'NL3', 'slug' => 'nl-300', 'unit' => 'Kg']);
        $po = PurchaseOrder::create(['po_number' => 'PO-RET-1', 'supplier_id' => $supplier->id, 'order_date' => today(), 'status' => 'delivered', 'user_id' => $user->id]);
        $item = $po->items()->create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'quantity' => 10, 'unit_price' => 1, 'total_price' => 10, 'unit' => 'Kg']);
        $lot = MaterialLot::create([
            'purchase_order_item_id' => $item->id,
            'material_type' => 'raw_material',
            'material_id' => $raw->id,
            'batch_number' => 'LOT-R1',
            'quantity' => 10,
            'unit' => 'Kg',
            'status' => 'rejected',
            'qc_note' => 'Kém chất lượng',
        ]);

        $this->actingAs($user)->get(route('supplier-returns.index'))->assertOk()->assertSee('NL300');

        $payload = ['material_lot_id' => $lot->id, 'qc_test_report' => 'PKN-1', 'qc_date' => today()->toDateString(), 'reason' => 'Kém chất lượng'];
        $this->actingAs($user)->post(route('supplier-returns.store', $item), $payload + ['quantity' => 11])->assertSessionHas('error');
        $this->actingAs($user)->post(route('supplier-returns.store', $item), $payload + ['quantity' => 4])->assertSessionHas('success');

        $returnOrder = \App\Models\SupplierReturnOrder::sole();
        $this->assertSame((int) $lot->id, (int) $returnOrder->material_lot_id);
        $this->assertSame('pending_dispatch', $returnOrder->status);
        $this->actingAs($user)->post(route('supplier-returns.store', $item), $payload + ['quantity' => 7])->assertSessionHas('error');
        $this->actingAs($user)->post(route('supplier-returns.dispatch', $returnOrder))->assertSessionHas('success');
        $this->assertSame('dispatched', $returnOrder->fresh()->status);
    }

    public function test_raw_material_lot_waits_for_qa_coa_and_qc_before_entering_stock(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC7']);
        $raw = RawMaterial::create(['sku' => 'NL400', 'name' => 'NL4', 'slug' => 'nl-400', 'unit' => 'Kg']);
        $acc = \App\Models\Accessory::create(['sku' => 'PL400', 'name' => 'PL4', 'slug' => 'pl-400', 'unit' => 'Cái']);
        $po = PurchaseOrder::create(['po_number' => 'PO-LOT-1', 'supplier_id' => $supplier->id, 'order_date' => today(), 'status' => 'delivered', 'user_id' => $user->id]);
        $rawItem = $po->items()->create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'quantity' => 5, 'unit_price' => 1, 'total_price' => 5, 'unit' => 'Kg']);
        $accItem = $po->items()->create(['material_type' => 'accessory', 'material_id' => $acc->id, 'quantity' => 5, 'unit_price' => 1, 'total_price' => 5, 'unit' => 'Cái']);

        $this->actingAs($user)->post(route('material-receipts.store', $po), $this->receiptPayload([
            $rawItem->id => ['received_quantity' => 5, 'returned_quantity' => 0],
            $accItem->id => ['received_quantity' => 5, 'returned_quantity' => 0],
        ]))->assertSessionHas('success');

        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->assertSame(0.0, MaterialStockMovement::balance('accessory', $acc->id));
        $accLot = MaterialLot::where('material_type', 'accessory')->firstOrFail();
        $this->qaThenQcApprove($user, $accLot, '');
        $this->assertSame(5.0, MaterialStockMovement::balance('accessory', $acc->id));
        $lot = MaterialLot::where('material_type', 'raw_material')->firstOrFail();
        $this->assertSame('temporary_stored', $lot->status);
        $this->actingAs($user)->post(route('material-lots.move-from-temporary', $lot))->assertSessionHas('success');
        $lot->refresh();
        $this->assertSame('pending_qa', $lot->status);
        $this->actingAs($user)->get(route('qa.material-lots.index'))->assertOk()->assertSee('NL400');

        $this->actingAs($user)->post(route('material-lots.approve', $lot))->assertSessionHas('error');
        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));

        $this->uploadCoa($user, $lot, 'NCC-LOT-9');
        $this->actingAs($user)->post(route('material-lots.inspect', $lot), $this->qcInspectionPayload())->assertSessionHas('success');
        $this->assertSame('qc_passed_pending_receipt', $lot->fresh()->status);
        $inspection = $lot->fresh()->inspections()->where('inspection_stage', 'qc_inspection')->sole();
        $this->assertSame('PKN-RAW-001', $inspection->report_number);
        $this->assertNotNull($inspection->report_file);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('local')->exists($inspection->report_file));
        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->actingAs($user)->get(route('warehouse.material-lots.ready'))->assertOk()->assertSee('NCC-LOT-9');
        $this->actingAs($user)->post(route('material-lots.stock-receipt.post', $lot))->assertSessionHas('success');
        $this->assertDatabaseHas('material_stock_movements', ['material_type' => 'raw_material', 'batch_number' => 'NCC-LOT-9']);
        $this->assertSame(5.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->actingAs($user)->get(route('warehouse.material-stock'))->assertOk()->assertSee('NCC-LOT-9');
    }

    public function test_qc_rejecting_raw_material_lot_keeps_it_out_of_stock(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC8']);
        $raw = RawMaterial::create(['sku' => 'NL500', 'name' => 'NL5', 'slug' => 'nl-500', 'unit' => 'Kg']);
        $po = PurchaseOrder::create(['po_number' => 'PO-LOT-2', 'supplier_id' => $supplier->id, 'order_date' => today(), 'status' => 'delivered', 'user_id' => $user->id]);
        $item = $po->items()->create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'quantity' => 4, 'unit_price' => 1, 'total_price' => 4, 'unit' => 'Kg']);
        $this->actingAs($user)->post(route('material-receipts.store', $po), $this->receiptPayload([$item->id => ['received_quantity' => 4, 'returned_quantity' => 0]]))->assertSessionHas('success');
        $lot = MaterialLot::firstOrFail();
        $this->actingAs($user)->post(route('material-lots.move-from-temporary', $lot))->assertSessionHas('success');
        $lot->refresh();
        $this->uploadCoa($user, $lot, 'NCC-REJECT');

        $this->actingAs($user)->post(route('material-lots.inspect', $lot), $this->qcInspectionPayload('failed'))->assertSessionHas('success');

        $this->assertSame('rejected', $lot->fresh()->status);
        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->assertSame(4.0, $item->fresh()->processedQuantity());
        $this->actingAs($user)->post(route('material-lots.approve', $lot))->assertSessionHas('error');
    }

    public function test_qc_lot_filters_keep_raw_material_separate_from_other_materials(): void
    {
        $user = $this->itUser();

        $this->actingAs($user)
            ->get(route('material-lots.index', ['type' => 'raw_material']))
            ->assertOk()
            ->assertSee('value="raw_material"', false)
            ->assertDontSee('value="ingredient"', false)
            ->assertDontSee('value="accessory"', false)
            ->assertDontSee('value="excipient"', false)
            ->assertDontSee('value="packaging"', false);

        $this->actingAs($user)
            ->get(route('material-lots.index', ['type' => 'accessory']))
            ->assertOk()
            ->assertDontSee('value="raw_material"', false)
            ->assertSee('value="ingredient"', false)
            ->assertSee('value="accessory"', false)
            ->assertSee('value="excipient"', false)
            ->assertSee('value="packaging"', false);
    }

    public function test_qc_can_reject_a_non_raw_material_lot_without_adding_stock(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC excipient', 'code' => 'NCC-EXC-QC']);
        $excipient = Excipient::create(['sku' => 'EXC-QC', 'name' => 'Tá dược QC', 'slug' => 'exc-qc', 'unit' => 'Kg']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-EXC-QC',
            'supplier_id' => $supplier->id,
            'order_date' => today(),
            'status' => 'delivered',
            'user_id' => $user->id,
        ]);
        $item = $purchaseOrder->items()->create([
            'material_type' => 'excipient',
            'material_id' => $excipient->id,
            'quantity' => 3,
            'unit_price' => 1,
            'total_price' => 3,
            'unit' => 'Kg',
        ]);

        $this->actingAs($user)
            ->post(route('material-receipts.store', $purchaseOrder), $this->receiptPayload([
                $item->id => ['received_quantity' => 3, 'returned_quantity' => 0],
            ]))
            ->assertSessionHas('success');

        $lot = MaterialLot::where('material_type', 'excipient')->sole();
        $this->actingAs($user)->post(route('material-lots.move-from-temporary', $lot))->assertSessionHas('success');
        $this->actingAs($user)
            ->post(route('material-lots.reject', $lot), ['qc_note' => 'Không đạt kiểm nghiệm'])
            ->assertSessionHas('success');

        $this->assertSame('rejected', $lot->fresh()->status);
        $this->assertSame('Không đạt kiểm nghiệm', $lot->fresh()->qc_note);
        $this->assertSame(0.0, MaterialStockMovement::balance('excipient', $excipient->id));
    }

    public function test_qa_and_qc_have_distinct_raw_material_queues_and_qc_requires_a_test_report(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'RAW-QA-QC', 'name' => 'Dược liệu kiểm thử', 'slug' => 'raw-qa-qc', 'unit' => 'Kg']);
        $supplier = Supplier::create(['name' => 'NCC QA QC', 'code' => 'NCC-QA-QC']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-QA-QC',
            'supplier_id' => $supplier->id,
            'order_date' => today(),
            'status' => 'delivered',
            'user_id' => $user->id,
        ]);
        $item = $purchaseOrder->items()->create([
            'material_type' => 'raw_material',
            'material_id' => $raw->id,
            'quantity' => 1,
            'unit_price' => 1,
            'total_price' => 1,
            'unit' => 'Kg',
        ]);
        $lot = MaterialLot::create([
            'purchase_order_item_id' => $item->id,
            'material_type' => 'raw_material',
            'material_id' => $raw->id,
            'quantity' => 1,
            'unit' => 'Kg',
            'status' => 'pending_qa',
        ]);

        $qaDepartment = Department::firstOrCreate(['code' => 'QA'], ['name' => 'QA']);
        $qcDepartment = Department::firstOrCreate(['code' => 'QC'], ['name' => 'QC']);
        $qaUser = User::factory()->create(['department_id' => $qaDepartment->id, 'role' => 'qa']);
        $qcUser = User::factory()->create(['department_id' => $qcDepartment->id, 'role' => 'qc']);

        $this->actingAs($qaUser)->get(route('qa.material-lots.index'))
            ->assertOk()
            ->assertSee('cập nhật COA')
            ->assertDontSee('name="qc_test_report"', false);
        $this->actingAs($qaUser)->get(route('qc.material-lots.index'))->assertForbidden();

        $this->uploadCoa($qaUser, $lot, 'QA-QC-LOT');
        $this->actingAs($qcUser)->post(route('material-lots.inspect', $lot), [
            'result' => 'passed',
        ])->assertSessionHasErrors(['qc_test_report', 'qc_date', 'qc_test_report_file']);

        $this->actingAs($qaUser)->get(route('qa.material-lots.index'))
            ->assertOk()
            ->assertSee('Không có dược liệu thô chờ cập nhật COA.');
        $this->actingAs($qcUser)->get(route('qc.material-lots.index', ['type' => 'raw_material']))
            ->assertOk()
            ->assertSee('Phiếu kiểm nghiệm')
            ->assertSee('name="qc_test_report"', false)
            ->assertDontSee('name="coa_file"', false);

        $this->actingAs($qcUser)->post(route('material-lots.inspect', $lot), $this->qcInspectionPayload())
            ->assertSessionHas('success');
        $this->assertSame('qc_passed_pending_receipt', $lot->fresh()->status);
    }

    public function test_issue_consumes_lots_in_expiry_order_and_return_is_per_lot(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'NL500', 'name' => 'NL5', 'slug' => 'nl-500', 'unit' => 'Kg']);
        $in = fn ($lot, $qty, $exp) => MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => $qty, 'batch_number' => $lot, 'exp_date' => $exp]);
        $in('LOT-LATE', 5, '2027-12-31');
        $in('LOT-EARLY', 5, '2027-01-01');
        $customer = Customer::create(['code' => 'KH5', 'name' => 'KH', 'type' => 'Retail']);
        $orderCode = 'DLT' . today()->format('ym') . '0001';
        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => $orderCode, 'customer_id' => $customer->id, 'order_type' => 'DLT',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['item_id' => $raw->id, 'quantity' => 7]],
        ])->assertSessionHasNoErrors();
        $order = Order::where('order_code', $orderCode)->firstOrFail();
        $this->actingAs($user)->post(route('orders.submit-sales', $order))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('orders.approve-sales', $order));
        $this->actingAs($user)->post(route('warehouse.material-issues.issue', $order))->assertSessionHas('success');

        $lots = MaterialStockMovement::lotBalances('raw_material', $raw->id)->keyBy('batch');
        $this->assertCount(1, $lots);
        $this->assertSame(3.0, $lots['LOT-LATE']->balance);
        $this->assertSame(0.0, MaterialStockMovement::lotBalance('raw_material', $raw->id, 'LOT-EARLY'));
        $this->actingAs($user)->get(route('warehouse.material-stock'))->assertOk()->assertSee('LOT-EARLY')->assertSee('LOT-LATE');
    }

    public function test_production_issue_picks_raw_material_lots_and_returns_unused_to_same_lot(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'NL600', 'name' => 'NL6', 'slug' => 'nl-600', 'unit' => 'Kg']);
        $acc = \App\Models\Accessory::create(['sku' => 'PL600', 'name' => 'PL6', 'slug' => 'pl-600', 'unit' => 'Cái']);
        $in = fn ($type, $id, $lot, $qty) => MaterialStockMovement::create(['material_type' => $type, 'material_id' => $id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => $qty, 'batch_number' => $lot]);
        $in('raw_material', $raw->id, 'NCC-A', 10);
        $in('accessory', $acc->id, null, 20);
        $product = Product::create(['name' => 'TP', 'slug' => 'tp-6', 'sku' => 'TP6', 'unit' => 'kg', 'classification' => 'VT']);
        $mo = \App\Models\ProductionOrder::create(['production_code' => 'MO-NL-6', 'product_id' => $product->id, 'planned_quantity' => 5, 'unit' => 'kg', 'yield_rate' => 1, 'status' => 'released']);
        $this->actingAs($user)->post(route('production-orders.assign-batch-number', $mo), [
            'batch_number' => 'LOT-MO-NL-6',
        ])->assertSessionHas('success');

        $this->actingAs($user)->get(route('warehouse.production-issues.show', $mo))->assertOk()->assertSee('NCC-A')->assertSee('PL6');

        $this->actingAs($user)->post(route('production-orders.issue-materials', $mo), ['material_lots' => [
            ['type' => 'raw_material', 'id' => $raw->id, 'batch' => 'NCC-A', 'quantity' => 11],
        ]])->assertSessionHas('error');
        $this->assertSame(10.0, MaterialStockMovement::lotBalance('raw_material', $raw->id, 'NCC-A'));

        $this->actingAs($user)->post(route('production-orders.issue-materials', $mo), ['material_lots' => [
            ['type' => 'raw_material', 'id' => $raw->id, 'batch' => 'NCC-A', 'quantity' => 6],
            ['type' => 'accessory', 'id' => $acc->id, 'batch' => '', 'quantity' => 4],
        ]])->assertSessionHas('success');
        $this->assertSame(4.0, MaterialStockMovement::lotBalance('raw_material', $raw->id, 'NCC-A'));
        $this->assertSame(16.0, MaterialStockMovement::lotBalance('accessory', $acc->id, ''));
        $this->assertSame('materials_issued', $mo->fresh()->status);

        $lot = \App\Models\ProductionMaterialLot::whereHas('material', fn ($q) => $q->where('material_type', 'raw_material'))->sole();
        $this->actingAs($user)->post(route('production-orders.start-production', $mo))->assertSessionHas('success');
        $this->actingAs($user)->post(route('production-orders.report-output', $mo), [
            'actual_quantity' => 5, 'reported_returned_materials' => [$lot->id => 2], 'mfg_date' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('production-orders.confirm-completion', $mo), [
            'returned_materials' => [$lot->id => 2],
        ])->assertSessionHasNoErrors();
        $this->assertSame(6.0, MaterialStockMovement::lotBalance('raw_material', $raw->id, 'NCC-A'));
    }

    public function test_sales_issue_uses_manually_chosen_lots_and_supplier_return_targets_rejected_accessory_lot(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'NL700', 'name' => 'NL7', 'slug' => 'nl-700', 'unit' => 'Kg']);
        foreach (['LOT-X' => '2027-01-01', 'LOT-Y' => '2027-12-31'] as $lot => $exp) {
            MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => 5, 'batch_number' => $lot, 'exp_date' => $exp]);
        }
        $customer = Customer::create(['code' => 'KH7', 'name' => 'KH', 'type' => 'Retail']);
        $orderCode = 'DLT' . today()->format('ym') . '0001';
        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => $orderCode, 'customer_id' => $customer->id, 'order_type' => 'DLT',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['item_id' => $raw->id, 'quantity' => 4]],
        ])->assertSessionHasNoErrors();
        $order = Order::where('order_code', $orderCode)->firstOrFail();
        $this->actingAs($user)->post(route('orders.submit-sales', $order))->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('orders.approve-sales', $order));
        $itemId = $order->items()->firstOrFail()->id;

        $this->actingAs($user)->post(route('warehouse.material-issues.issue', $order), ['lots' => [$itemId => [['batch' => 'LOT-Y', 'quantity' => 3]]]])->assertSessionHas('error');
        $this->actingAs($user)->post(route('warehouse.material-issues.issue', $order), ['lots' => [$itemId => [['batch' => 'LOT-Y', 'quantity' => 4]]]])->assertSessionHas('success');
        $this->assertSame(5.0, MaterialStockMovement::lotBalance('raw_material', $raw->id, 'LOT-X'));
        $this->assertSame(1.0, MaterialStockMovement::lotBalance('raw_material', $raw->id, 'LOT-Y'));

        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC6']);
        $acc = \App\Models\Accessory::create(['sku' => 'PL700', 'name' => 'PL7', 'slug' => 'pl-700', 'unit' => 'Cái']);
        $po = PurchaseOrder::create(['po_number' => 'PO-ACC-7', 'supplier_id' => $supplier->id, 'order_date' => today(), 'status' => 'delivered', 'user_id' => $user->id]);
        $item = $po->items()->create(['material_type' => 'accessory', 'material_id' => $acc->id, 'quantity' => 10, 'unit_price' => 1, 'total_price' => 10, 'unit' => 'Cái']);
        $this->actingAs($user)->post(route('material-receipts.store', $po), $this->receiptPayload([$item->id => ['received_quantity' => 10, 'returned_quantity' => 0]]))->assertSessionHas('success');
        $this->qaThenQcApprove($user, MaterialLot::where('material_type', 'accessory')->firstOrFail(), '');
        $this->assertDatabaseHas('material_stock_movements', ['material_type' => 'accessory', 'material_id' => $acc->id, 'batch_number' => null]);
        $rejectedLot = MaterialLot::create([
            'purchase_order_item_id' => $item->id,
            'material_type' => 'accessory',
            'material_id' => $acc->id,
            'batch_number' => 'PL-REJECTED',
            'quantity' => 3,
            'unit' => 'Cái',
            'status' => 'rejected',
            'qc_note' => 'x',
        ]);
        $payload = ['material_lot_id' => $rejectedLot->id, 'qc_test_report' => 'PKN', 'qc_date' => today()->toDateString(), 'reason' => 'x'];
        $this->actingAs($user)->post(route('supplier-returns.store', $item), $payload + ['quantity' => 3])->assertSessionHas('success');
        $this->assertSame(10.0, MaterialStockMovement::balance('accessory', $acc->id));
    }

    public function test_warehouse_production_issue_list_loads(): void
    {
        $this->actingAs($this->itUser())->get(route('warehouse.production-issues'))->assertOk()->assertSee('Xuất và xác nhận nguyên liệu');
    }}
