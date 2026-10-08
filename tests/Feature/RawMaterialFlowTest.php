<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\MaterialLot;
use App\Models\MaterialStockMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
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
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->actingAs($user)->patch(route('material-lots.update-coa', $lot), [
            'batch_number' => $batch, 'mfg_date' => '2026-01-01', 'exp_date' => '2028-01-01',
            'coa_file' => \Illuminate\Http\UploadedFile::fake()->create('coa.pdf', 10, 'application/pdf'),
        ])->assertSessionHas('success');
        $this->actingAs($user)->post(route('material-lots.approve', $lot))->assertSessionHas('success');
    }

    public function test_mixed_purchase_order_receives_materials_into_stock_and_completes(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC9']);
        $raw = RawMaterial::create(['sku' => 'NL100', 'name' => 'NL', 'slug' => 'nl-100', 'unit' => 'Kg']);

        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-NL-1', 'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(),
            'items' => [['item_type' => 'raw_material', 'item_id' => $raw->id, 'quantity' => 10, 'unit_price' => 5]],
        ])->assertSessionHasNoErrors();
        $po = PurchaseOrder::where('po_number', 'PO-NL-1')->firstOrFail();
        $po->update(['status' => 'delivered']);
        $itemId = $po->items()->firstOrFail()->id;

        $this->actingAs($user)->get(route('material-receipts.create', $po))->assertOk();
        $this->actingAs($user)->post(route('material-receipts.store', $po), [
            'items' => [$itemId => ['received_quantity' => 8, 'returned_quantity' => 2]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $lot = MaterialLot::firstOrFail();
        $this->qaThenQcApprove($user, $lot, 'L1');
        $this->assertSame(8.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->assertSame('completed', $po->fresh()->status);
        $this->actingAs($user)->get(route('warehouse.material-stock'))->assertOk()->assertSee('NL100');
        $this->actingAs($user)->get(route('purchase-orders.show', $po))->assertOk()->assertSee('8.00');
    }

    public function test_raw_material_sales_order_flows_to_warehouse_issue(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'NL200', 'name' => 'NL2', 'slug' => 'nl-200', 'unit' => 'Kg']);
        MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => 5]);
        $customer = Customer::create(['code' => 'KH1', 'name' => 'KH', 'type' => 'Retail']);

        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => 'DH-NL-1', 'customer_id' => $customer->id, 'order_type' => 'NL',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['item_id' => $raw->id, 'quantity' => 6]],
        ])->assertSessionHasNoErrors();
        $order = Order::where('order_code', 'DH-NL-1')->firstOrFail();
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'raw_material_id' => $raw->id, 'product_id' => null]);

        $this->actingAs($user)->post(route('orders.approve-sales', $order))->assertSessionHasNoErrors();
        $this->assertSame('pending_material_issue', $order->fresh()->status);
        $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertSee('NL200');
        $this->actingAs($user)->get(route('warehouse.material-issues'))->assertOk()->assertSee('DH-NL-1');

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
            'order_code' => 'DH-DL-1', 'customer_id' => $customer->id, 'order_type' => 'DL',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'raw_material_id' => null]);
    }

    public function test_supplier_return_for_material_reduces_stock_and_blocks_over_return(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC8']);
        $raw = RawMaterial::create(['sku' => 'NL300', 'name' => 'NL3', 'slug' => 'nl-300', 'unit' => 'Kg']);
        $po = PurchaseOrder::create(['po_number' => 'PO-RET-1', 'supplier_id' => $supplier->id, 'order_date' => today(), 'status' => 'delivered', 'user_id' => $user->id]);
        $item = $po->items()->create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'quantity' => 10, 'unit_price' => 1, 'total_price' => 10, 'unit' => 'Kg']);
        MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => 10, 'purchase_order_item_id' => $item->id, 'batch_number' => 'LOT-R1']);

        $this->actingAs($user)->get(route('supplier-returns.index'))->assertOk()->assertSee('NL300');

        $payload = ['batch_number' => 'LOT-R1', 'qc_test_report' => 'PKN-1', 'qc_date' => today()->toDateString(), 'reason' => 'Kém chất lượng'];
        $this->actingAs($user)->post(route('supplier-returns.store', $item), $payload + ['quantity' => 11])->assertSessionHas('error');
        $this->actingAs($user)->post(route('supplier-returns.store', $item), $payload + ['quantity' => 4])->assertSessionHas('success');

        $this->assertSame(6.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $returnOrder = \App\Models\SupplierReturnOrder::sole();
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

        $this->actingAs($user)->post(route('material-receipts.store', $po), ['items' => [
            $rawItem->id => ['received_quantity' => 5, 'returned_quantity' => 0],
            $accItem->id => ['received_quantity' => 5, 'returned_quantity' => 0],
        ]])->assertSessionHas('success');

        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->assertSame(5.0, MaterialStockMovement::balance('accessory', $acc->id));
        $lot = MaterialLot::firstOrFail();
        $this->assertSame('pending_qa', $lot->status);
        $this->actingAs($user)->get(route('material-lots.index'))->assertOk()->assertSee('NL400');

        $this->actingAs($user)->post(route('material-lots.approve', $lot))->assertSessionHas('error');
        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));

        $this->qaThenQcApprove($user, $lot, 'NCC-LOT-9');
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
        $this->actingAs($user)->post(route('material-receipts.store', $po), ['items' => [$item->id => ['received_quantity' => 4, 'returned_quantity' => 0]]])->assertSessionHas('success');
        $lot = MaterialLot::firstOrFail();

        $this->actingAs($user)->post(route('material-lots.reject', $lot), ['qc_note' => 'Không đạt chỉ tiêu'])->assertSessionHas('success');

        $this->assertSame('rejected', $lot->fresh()->status);
        $this->assertSame(0.0, MaterialStockMovement::balance('raw_material', $raw->id));
        $this->assertSame(4.0, $item->fresh()->processedQuantity());
        $this->actingAs($user)->post(route('material-lots.approve', $lot))->assertSessionHas('error');
    }

    public function test_issue_consumes_lots_in_expiry_order_and_return_is_per_lot(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'NL500', 'name' => 'NL5', 'slug' => 'nl-500', 'unit' => 'Kg']);
        $in = fn ($lot, $qty, $exp) => MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => $qty, 'batch_number' => $lot, 'exp_date' => $exp]);
        $in('LOT-LATE', 5, '2027-12-31');
        $in('LOT-EARLY', 5, '2027-01-01');
        $customer = Customer::create(['code' => 'KH5', 'name' => 'KH', 'type' => 'Retail']);
        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => 'DH-NL-5', 'customer_id' => $customer->id, 'order_type' => 'NL',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['item_id' => $raw->id, 'quantity' => 7]],
        ])->assertSessionHasNoErrors();
        $order = Order::where('order_code', 'DH-NL-5')->firstOrFail();
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

        $this->actingAs($user)->get(route('production-orders.show', $mo))->assertOk()->assertSee('NCC-A')->assertSee('PL6');

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
        $this->actingAs($user)->post(route('production-orders.receive-finished-batch', $mo), [
            'actual_quantity' => 5, 'returned_materials' => [$lot->id => 2], 'mfg_date' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertSame(6.0, MaterialStockMovement::lotBalance('raw_material', $raw->id, 'NCC-A'));
    }

    public function test_sales_issue_uses_manually_chosen_lots_and_accessory_return_is_by_total(): void
    {
        $user = $this->itUser();
        $raw = RawMaterial::create(['sku' => 'NL700', 'name' => 'NL7', 'slug' => 'nl-700', 'unit' => 'Kg']);
        foreach (['LOT-X' => '2027-01-01', 'LOT-Y' => '2027-12-31'] as $lot => $exp) {
            MaterialStockMovement::create(['material_type' => 'raw_material', 'material_id' => $raw->id, 'movement_type' => 'RECEIVE_PURCHASE', 'direction' => 'in', 'quantity' => 5, 'batch_number' => $lot, 'exp_date' => $exp]);
        }
        $customer = Customer::create(['code' => 'KH7', 'name' => 'KH', 'type' => 'Retail']);
        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => 'DH-NL-7', 'customer_id' => $customer->id, 'order_type' => 'NL',
            'order_date' => today()->toDateString(), 'delivery_date' => today()->addDay()->toDateString(), 'province_city' => 'HN',
            'items' => [['item_id' => $raw->id, 'quantity' => 4]],
        ])->assertSessionHasNoErrors();
        $order = Order::where('order_code', 'DH-NL-7')->firstOrFail();
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
        $this->actingAs($user)->post(route('material-receipts.store', $po), ['items' => [$item->id => ['received_quantity' => 10, 'returned_quantity' => 0, 'batch_number' => 'IGNORED']]])->assertSessionHas('success');
        $this->assertDatabaseHas('material_stock_movements', ['material_type' => 'accessory', 'material_id' => $acc->id, 'batch_number' => null]);
        $payload = ['qc_test_report' => 'PKN', 'qc_date' => today()->toDateString(), 'reason' => 'x'];
        $this->actingAs($user)->post(route('supplier-returns.store', $item), $payload + ['quantity' => 3])->assertSessionHas('success');
        $this->assertSame(7.0, MaterialStockMovement::balance('accessory', $acc->id));
    }

    public function test_warehouse_production_issue_list_loads(): void
    {
        $this->actingAs($this->itUser())->get(route('warehouse.production-issues'))->assertOk()->assertSee('Xuất nguyên liệu');
    }}
