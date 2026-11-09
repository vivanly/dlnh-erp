<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\SalesOrderLabelPrint;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $deptCode, string $position = 'Nhân Viên Văn Phòng', string $role = 'staff'): User
    {
        $department = Department::firstOrCreate(['code' => $deptCode], ['name' => $deptCode]);

        return User::factory()->create([
            'department_id' => $department->id,
            'position' => $position,
            'role' => $role,
        ]);
    }

    private function order(string $status): void
    {
        Order::create([
            'order_code' => uniqid('DH'),
            'customer_id' => Customer::create(['code' => uniqid('KH'), 'name' => 'KH', 'type' => 'Retail'])->id,
            'order_type' => 'VT',
            'province_city' => 'Hà Nội',
            'order_date' => today(),
            'delivery_date' => today()->addDay(),
            'status' => $status,
            'sales_approved_at' => now(),
        ]);
    }

    private function labels(User $user): array
    {
        return collect($this->actingAs($user)->getJson(route('notifications.summary'))->assertOk()->json('items'))
            ->pluck('label')
            ->all();
    }

    public function test_each_role_only_sees_its_own_tasks(): void
    {
        $this->order('pending_sales_approval');
        $this->order('pending_qa');
        $this->order('pending_warehouse_check');

        $this->assertContains('Đơn hàng cần duyệt', $this->labels($this->user('HCNS', 'Tổng Giám Đốc')));

        $qa = $this->labels($this->user('QA'));
        $this->assertContains('Đơn hàng cần chốt lô', $qa);
        $this->assertNotContains('Đơn hàng cần duyệt', $qa);

        $this->assertContains('Đơn hàng cần xác nhận tồn kho', $this->labels($this->user('KHO')));
        $this->assertSame([], $this->labels($this->user('KE-TOAN')));
    }

    public function test_guest_cannot_read_notifications(): void
    {
        $this->getJson(route('notifications.summary'))->assertUnauthorized();
    }

    public function test_orders_waiting_for_labels_notify_only_users_with_print_permission(): void
    {
        $customer = Customer::create(['code' => 'LABEL-NOTIFY-CUST', 'name' => 'Label Notify Customer', 'type' => 'Retail']);
        $product = Product::create([
            'name' => 'Label notification herb',
            'slug' => 'label-notification-herb',
            'sku' => 'LABEL-NOTIFY-1',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'LABEL-NOTIFY-LOT-1',
            'initial_quantity' => 2,
            'current_quantity' => 2,
            'mfg_date' => today()->subMonth(),
            'exp_date' => today()->addYear(),
            'status' => 'active',
        ]);
        $order = Order::create([
            'order_code' => 'SO-LABEL-NOTIFY-001',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDays(3),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'packaging_spec' => '1',
            'finished_quantity' => 2,
        ]);
        $allocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'supplier_batch_id' => $batch->id,
            'reserved_quantity' => 2,
            'status' => 'reserved',
        ]);

        $authorizedUser = User::factory()->create(['can_print_labels' => true]);
        $unauthorizedUser = User::factory()->create(['can_print_labels' => false]);

        $authorizedItems = collect($this->actingAs($authorizedUser)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $labelNotice = $authorizedItems->firstWhere('label', 'Đơn hàng cần in nhãn');
        $this->assertSame(1, $labelNotice['count']);
        $this->assertSame(route('labels.index'), $labelNotice['url']);

        $unauthorizedItems = collect($this->actingAs($unauthorizedUser)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertNotContains('Đơn hàng cần in nhãn', $unauthorizedItems->pluck('label')->all());

        SalesOrderLabelPrint::create([
            'order_item_id' => $item->id,
            'sales_order_lot_allocation_id' => $allocation->id,
            'copies_count' => 2,
            'printed_by' => $authorizedUser->id,
        ]);
        $authorizedItems = collect($this->actingAs($authorizedUser)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertNotContains('Đơn hàng cần in nhãn', $authorizedItems->pluck('label')->all());
    }

    public function test_production_lot_notifications_match_available_work_at_each_stage(): void
    {
        $qaUser = $this->user('QA');
        $warehouseUser = $this->user('KHO');
        $qcUser = $this->user('QC');
        $product = Product::create([
            'name' => 'Notification finished product',
            'slug' => 'notification-finished-product',
            'sku' => 'NOTIFY-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'MO-NOTIFY-1',
            'product_id' => $product->id,
            'planned_quantity' => 20,
            'actual_quantity' => 20,
            'pending_finished_quantity' => 20,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'completed',
        ]);
        $batch = ProductionFinishedBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'NOTIFY-LOT-1',
            'planned_quantity' => 20,
            'initial_quantity' => 20,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 20,
            'unit' => 'kg',
            'status' => 'pending_qa',
        ]);

        $qaResponse = $this->actingAs($qaUser)->getJson(route('notifications.summary'))->assertOk();
        $this->assertContains('Lệnh sản xuất hoàn tất chờ phân bổ lô', collect($qaResponse->json('items'))->pluck('label')->all());
        $this->assertSame(route('qa.internal-lots.create'), collect($qaResponse->json('items'))->firstWhere('label', 'Lệnh sản xuất hoàn tất chờ phân bổ lô')['url']);
        $this->get(route('qa.internal-lots.create'))->assertOk()->assertSee('MO-NOTIFY-1');
        $productionOrder->update(['pending_finished_quantity' => 0]);
        $qaResponse = $this->actingAs($qaUser)->getJson(route('notifications.summary'))->assertOk();
        $this->assertNotContains('Lệnh sản xuất hoàn tất chờ phân bổ lô', collect($qaResponse->json('items'))->pluck('label')->all());

        $warehouseResponse = $this->actingAs($warehouseUser)->getJson(route('notifications.summary'))->assertOk();
        $this->assertContains('Lô thành phẩm chờ Kho nhập', collect($warehouseResponse->json('items'))->pluck('label')->all());

        $batch->update([
            'status' => 'active',
            'warehouse_received_at' => now(),
            'pending_warehouse_quantity' => 5,
        ]);
        $qcResponse = $this->actingAs($qcUser)->getJson(route('notifications.summary'))->assertOk();
        $this->assertContains('Lô thành phẩm chờ phiếu kiểm nghiệm', collect($qcResponse->json('items'))->pluck('label')->all());

        $batch->update(['pending_warehouse_quantity' => 0]);
        $qcResponse = $this->actingAs($qcUser)->getJson(route('notifications.summary'))->assertOk();
        $this->assertContains('Lô thành phẩm chờ phiếu kiểm nghiệm', collect($qcResponse->json('items'))->pluck('label')->all());

        $this->assertSame(0.0, (float) $productionOrder->fresh()->pending_finished_quantity);
    }

    public function test_production_notifications_follow_batch_assignment_issue_and_warehouse_confirmation(): void
    {
        $qa = $this->user('QA');
        $warehouse = $this->user('KHO');
        $production = $this->user('SAN-XUAT');
        $product = Product::create(['name' => 'Flow herb', 'slug' => 'notify-flow-herb', 'sku' => 'NOTIFY-FLOW', 'unit' => 'kg', 'classification' => 'VT']);
        $order = ProductionOrder::create([
            'production_code' => 'MO-NOTIFY-FLOW',
            'product_id' => $product->id,
            'planned_quantity' => 10,
            'unit' => 'kg',
            'yield_rate' => 1,
            'status' => 'released',
        ]);
        $qaItems = collect($this->actingAs($qa)->getJson(route('notifications.summary'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->json('items'));
        $notice = $qaItems->firstWhere('label', 'Lệnh sản xuất chờ QA gán số lô');
        $this->assertSame(1, $notice['count']);
        $this->assertSame(route('qa.production-batch-assignments.index'), $notice['url']);
        $this->assertSame(array_sum($qaItems->pluck('count')->all()), $this->getJson(route('notifications.summary'))->json('total'));
        $this->get($notice['url'])->assertOk()->assertSee('MO-NOTIFY-FLOW');
        $this->assertNotContains('Lệnh sản xuất chờ Kho xuất nguyên liệu', $this->labels($warehouse));

        $this->actingAs($qa)->post(route('production-orders.assign-batch-number', $order), ['batch_number' => 'NOTIFY-FLOW-BATCH'])->assertSessionHas('success');
        $batch = ProductionFinishedBatch::where('production_order_id', $order->id)->sole();
        $this->assertNotContains('Lệnh sản xuất chờ QA gán số lô', $this->labels($qa));
        $items = collect($this->actingAs($warehouse)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $notice = $items->firstWhere('label', 'Lệnh sản xuất chờ Kho xuất nguyên liệu');
        $this->assertSame(1, $notice['count']);
        $this->assertSame(route('warehouse.production-issues'), $notice['url']);
        $this->get($notice['url'])->assertOk()->assertSee('MO-NOTIFY-FLOW')->assertSee('Xuất nguyên liệu');

        $order->update(['status' => 'materials_issued']);
        $this->assertNotContains('Lệnh sản xuất chờ Kho xuất nguyên liệu', $this->labels($warehouse));
        $this->assertContains('Lệnh sản xuất cần báo sản lượng', $this->labels($production));

        $order->update(['status' => 'production_reported', 'actual_quantity' => 10]);
        $batch->update(['status' => 'pending_qa', 'initial_quantity' => 10, 'pending_warehouse_quantity' => 10]);
        $items = collect($this->actingAs($warehouse)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $notice = $items->firstWhere('label', 'Lệnh sản xuất chờ Kho xác nhận hoàn tất');
        $this->assertSame(route('warehouse.production-issues'), $notice['url']);
        $this->get($notice['url'])->assertOk()->assertSee('MO-NOTIFY-FLOW')->assertSee('Xác nhận nguyên liệu trả');
        $this->assertNull($items->firstWhere('label', 'Lô thành phẩm chờ Kho nhập'));

        $order->update(['status' => 'completed']);
        $items = collect($this->actingAs($warehouse)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertNull($items->firstWhere('label', 'Lệnh sản xuất chờ Kho xác nhận hoàn tất'));
        $notice = $items->firstWhere('label', 'Lô thành phẩm chờ Kho nhập');
        $this->assertSame(1, $notice['count']);
        $this->assertSame(route('warehouse.production-batches'), $notice['url']);
        $this->get($notice['url'])->assertOk()->assertSee('NOTIFY-FLOW-BATCH');

        $batch->update(['status' => 'active', 'warehouse_received_at' => now(), 'pending_warehouse_quantity' => 5]);
        $qc = $this->user('QC');
        $items = collect($this->actingAs($qc)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $notice = $items->firstWhere('label', 'Lô thành phẩm chờ phiếu kiểm nghiệm');
        $this->assertSame(1, $notice['count']);
        $this->get($notice['url'])->assertOk()->assertSee('NOTIFY-FLOW-BATCH');
        $batch->update(['qc_result' => 'passed', 'qc_test_report_file' => 'reports/passed.pdf']);
        $this->assertNotContains('Lô thành phẩm chờ phiếu kiểm nghiệm', $this->labels($qc));
    }

    public function test_warehouse_notifications_distinguish_packing_from_confirming_shipment(): void
    {
        $warehouse = $this->user('KHO');
        $customer = Customer::create(['code' => 'SHIP-NOTIFY', 'name' => 'Shipment customer', 'type' => 'Retail']);
        $order = Order::create([
            'order_code' => 'SHIP-NOTIFY-ORDER',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today(),
            'province_city' => 'Hà Nội',
            'status' => 'ready_to_ship',
            'qa_confirmed_at' => now(),
        ]);
        $items = collect($this->actingAs($warehouse)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertSame(1, $items->firstWhere('label', 'Đơn hàng chờ Kho đóng hàng')['count']);
        $this->assertNull($items->firstWhere('label', 'Đơn hàng chờ Kho xác nhận gửi'));

        $order->update(['warehouse_packed_at' => now()]);
        $items = collect($this->actingAs($warehouse)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertNull($items->firstWhere('label', 'Đơn hàng chờ Kho đóng hàng'));
        $notice = $items->firstWhere('label', 'Đơn hàng chờ Kho xác nhận gửi');
        $this->assertSame(1, $notice['count']);
        $this->assertSame(route('warehouse.sales-orders'), $notice['url']);
        $this->get($notice['url'])->assertOk()->assertSee('SHIP-NOTIFY-ORDER');
        $order->update(['status' => 'completed', 'warehouse_confirmed_at' => now()]);
        $this->assertNotContains('Đơn hàng chờ Kho xác nhận gửi', $this->labels($warehouse));
    }

    public function test_independent_lot_with_receipt_capacity_is_included_in_warehouse_notifications(): void
    {
        $warehouse = $this->user('KHO');
        $product = Product::create(['name' => 'Independent lot', 'slug' => 'independent-notice', 'sku' => 'INDEPENDENT-NOTICE', 'unit' => 'kg', 'classification' => 'VT']);
        $batch = ProductionFinishedBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'INDEPENDENT-NOTIFY',
            'planned_quantity' => 10,
            'initial_quantity' => 0,
            'current_quantity' => 0,
            'pending_warehouse_quantity' => 0,
            'unit' => 'kg',
            'status' => 'pending_qa',
        ]);
        $items = collect($this->actingAs($warehouse)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $notice = $items->firstWhere('label', 'Lô thành phẩm chờ Kho nhập');
        $this->assertSame(1, $notice['count']);
        $this->get($notice['url'])->assertOk()->assertSee('INDEPENDENT-NOTIFY');
        $batch->update(['status' => 'closed']);
        $this->assertNotContains('Lô thành phẩm chờ Kho nhập', $this->labels($warehouse));
    }
}
