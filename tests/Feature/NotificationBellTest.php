<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Accessory;
use App\Models\Excipient;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Ingredient;
use App\Models\MaterialLot;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PackagingMaterial;
use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrderLabelPrint;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Models\Supplier;
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
        $this->order('pending_planning');
        $this->order('pending_warehouse_check');

        $this->assertContains('Đơn hàng cần duyệt', $this->labels($this->user('HCNS', 'Tổng Giám Đốc')));

        $qaUser = $this->user('QA');
        $qa = $this->labels($qaUser);
        $this->assertNotContains('Đơn hàng cần chốt lô', $qa);
        $this->assertNotContains('Đơn hàng cần duyệt', $qa);
        $this->actingAs($qaUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Cập nhật lô cho đơn hàng');
        $this->actingAs($qaUser)->get('/qa/order-batches')->assertNotFound();

        $this->assertContains('Đơn hàng cần xác nhận tồn kho', $this->labels($this->user('KHO')));
        $this->assertSame([], $this->labels($this->user('KE-TOAN')));
    }

    public function test_department_directors_only_receive_board_notifications(): void
    {
        $this->order('pending_sales_approval');
        $this->order('pending_planning');

        $director = $this->user('QA', 'Giám Đốc');
        $items = collect($this->actingAs($director)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $labels = $items->pluck('label')->all();

        $this->assertContains('Đơn hàng cần duyệt', $labels);
        $this->assertNotContains('Đơn hàng cần chốt lô', $labels);
    }

    public function test_guest_cannot_read_notifications(): void
    {
        $this->getJson(route('notifications.summary'))->assertUnauthorized();
    }

    public function test_purchase_notifications_follow_delivery_and_receipt_status(): void
    {
        $warehouse = $this->user('KHO');
        $supplier = Supplier::create(['code' => 'NOTIFY-PO-SUP', 'name' => 'Notification supplier']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-NOTIFY',
            'supplier_id' => $supplier->id,
            'user_id' => $warehouse->id,
            'order_date' => today(),
            'status' => 'approved',
            'subtotal' => 0,
            'tax_amount' => 0,
            'grand_total' => 0,
        ]);
        $this->actingAs($warehouse);

        $items = collect($this->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertSame(1, $items->firstWhere('label', 'Đơn mua chờ nhận hàng')['count']);
        $this->assertNull($items->firstWhere('label', 'Đơn mua chờ nhập kho'));

        $this->post(route('warehouse.purchase-orders.mark-delivered', $purchaseOrder))->assertSessionHas('success');
        $items = collect($this->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertNull($items->firstWhere('label', 'Đơn mua chờ nhận hàng'));
        $notice = $items->firstWhere('label', 'Đơn mua chờ nhập kho');
        $this->assertSame(1, $notice['count']);
        $this->assertSame(route('warehouse.purchase-orders'), $notice['url']);
        $this->get($notice['url'])->assertOk()->assertSee('PO-NOTIFY');

        $purchaseOrder->update(['status' => 'completed']);
        $this->assertNotContains('Đơn mua chờ nhập kho', $this->labels($warehouse));
    }

    public function test_supplier_coa_notification_links_to_the_update_workflow(): void
    {
        $qa = $this->user('QA');
        $supplier = Supplier::create(['code' => 'NOTIFY-COA-SUP', 'name' => 'COA supplier']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-NOTIFY-COA',
            'supplier_id' => $supplier->id,
            'user_id' => $qa->id,
            'order_date' => today(),
            'status' => 'completed',
            'subtotal' => 0,
            'tax_amount' => 0,
            'grand_total' => 0,
        ]);
        $product = Product::create(['name' => 'COA product', 'slug' => 'coa-notify', 'sku' => 'COA-NOTIFY', 'unit' => 'kg', 'classification' => 'VT']);
        $purchaseItem = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id, 'product_id' => $product->id,
            'quantity' => 1, 'unit' => 'kg', 'unit_price' => 0, 'total_price' => 0,
        ]);
        $receipt = GoodsReceipt::create([
            'purchase_order_id' => $purchaseOrder->id, 'receipt_code' => 'GR-NOTIFY-COA',
            'receipt_date' => today(), 'receiver_id' => $qa->id, 'qc_status' => 'pending',
        ]);
        $receiptItem = GoodsReceiptItem::create([
            'goods_receipt_id' => $receipt->id, 'purchase_order_item_id' => $purchaseItem->id,
            'herb_id' => $product->id, 'ordered_quantity' => 1, 'received_quantity' => 1,
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => $receiptItem->id, 'product_id' => $product->id,
            'batch_number' => 'COA-NOTIFY-LOT', 'initial_quantity' => 1,
            'current_quantity' => 1, 'status' => 'pending_qa',
        ]);

        $this->actingAs($qa);
        $items = collect($this->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $notice = $items->firstWhere('label', 'Lô sản phẩm mua từ NCC chờ QA cập nhật COA');
        $this->assertSame(1, $notice['count']);
        $this->assertSame(route('qa.coas.index'), $notice['url']);
        $this->get($notice['url'])->assertOk()->assertSee(route('goods-receipts.show', $receipt));
        $this->get(route('goods-receipts.show', $receipt))->assertOk()->assertSee(route('supplier-batches.update-coa', $batch));

        $batch->update(['coa_file' => 'coas/test.pdf', 'mfg_date' => today(), 'exp_date' => today()->addYear()]);
        $this->assertNotContains('Lô sản phẩm mua từ NCC chờ QA cập nhật COA', $this->labels($qa));
        $this->assertContains('Lô sản phẩm mua từ NCC chờ QC xác nhận', $this->labels($this->user('QC')));
    }

    public function test_raw_material_issue_notification_matches_warehouse_queue_type(): void
    {
        $this->order('pending_material_issue');
        $warehouse = $this->user('KHO');
        $this->assertNotContains('Đơn dược liệu thô cần xuất kho', $this->labels($warehouse));

        Order::where('status', 'pending_material_issue')->update(['order_type' => 'DLT']);
        $items = collect($this->actingAs($warehouse)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        $this->assertSame(1, $items->firstWhere('label', 'Đơn dược liệu thô cần xuất kho')['count']);
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
            'reserved_quantity' => 1,
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

        $finishedBatch = ProductionFinishedBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'LABEL-NOTIFY-LOT-2',
            'planned_quantity' => 1,
            'initial_quantity' => 1,
            'current_quantity' => 1,
            'unit' => 'kg',
            'status' => 'pending_qc_inspection',
            'exp_date' => today()->addYear(),
        ]);
        $secondAllocation = SalesOrderLotAllocation::create([
            'order_item_id' => $item->id,
            'production_finished_batch_id' => $finishedBatch->id,
            'reserved_quantity' => 1,
            'status' => 'reserved',
        ]);

        $this->assertContains('Đơn hàng cần in nhãn', $this->labels($authorizedUser));
        $finishedBatch->update(['exp_date' => today()->subDay()]);
        $this->assertNotContains('Đơn hàng cần in nhãn', $this->labels($authorizedUser));
        $finishedBatch->update(['exp_date' => today()->addYear(), 'status' => 'rejected']);
        $this->assertNotContains('Đơn hàng cần in nhãn', $this->labels($authorizedUser));
        $finishedBatch->update(['status' => 'pending_qa']);
        $this->assertContains('Đơn hàng cần in nhãn', $this->labels($authorizedUser));

        SalesOrderLabelPrint::create([
            'order_item_id' => $item->id,
            'sales_order_lot_allocation_id' => $secondAllocation->id,
            'copies_count' => 1,
            'printed_by' => $authorizedUser->id,
        ]);
        $this->assertNotContains('Đơn hàng cần in nhãn', $this->labels($authorizedUser));
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
        $this->assertContains('Lô nội bộ do QA cấp chờ PKN', collect($qcResponse->json('items'))->pluck('label')->all());

        $batch->update(['pending_warehouse_quantity' => 0]);
        $qcResponse = $this->actingAs($qcUser)->getJson(route('notifications.summary'))->assertOk();
        $this->assertContains('Lô nội bộ do QA cấp chờ PKN', collect($qcResponse->json('items'))->pluck('label')->all());

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
        $this->assertContains('Lệnh sản xuất cần xác nhận bắt đầu', $this->labels($production));
        $order->update(['status' => 'production_started']);
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
        $notice = $items->firstWhere('label', 'Lô nội bộ do QA cấp chờ PKN');
        $this->assertSame(1, $notice['count']);
        $this->get($notice['url'])->assertOk()->assertSee('NOTIFY-FLOW-BATCH');
        $batch->update(['qc_result' => 'passed', 'qc_test_report_file' => 'reports/passed.pdf']);
        $this->assertNotContains('Lô nội bộ do QA cấp chờ PKN', $this->labels($qc));
    }

    public function test_qc_notifications_cover_each_non_raw_material_type_separately(): void
    {
        $qc = $this->user('QC');
        $supplier = Supplier::create(['name' => 'QC materials supplier', 'code' => 'QC-MATERIALS']);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-QC-MATERIALS',
            'supplier_id' => $supplier->id,
            'order_date' => today(),
            'status' => 'delivered',
            'user_id' => $qc->id,
        ]);
        $catalog = [
            'ingredient' => Ingredient::create(['sku' => 'QC-ING', 'name' => 'Nguyên liệu QC', 'slug' => 'qc-ing', 'unit' => 'Kg']),
            'accessory' => Accessory::create(['sku' => 'QC-ACC', 'name' => 'Phụ liệu QC', 'slug' => 'qc-acc', 'unit' => 'Kg']),
            'excipient' => Excipient::create(['sku' => 'QC-EXC', 'name' => 'Tá dược QC', 'slug' => 'qc-exc', 'unit' => 'Kg']),
            'packaging' => PackagingMaterial::create(['sku' => 'QC-PKG', 'name' => 'Bao bì QC', 'slug' => 'qc-pkg', 'unit' => 'Cái']),
        ];

        foreach ($catalog as $type => $item) {
            $purchaseItem = $purchaseOrder->items()->create([
                'material_type' => $type,
                'material_id' => $item->id,
                'quantity' => 1,
                'unit' => $item->unit,
                'unit_price' => 1,
                'total_price' => 1,
            ]);
            MaterialLot::create([
                'purchase_order_item_id' => $purchaseItem->id,
                'material_type' => $type,
                'material_id' => $item->id,
                'quantity' => 1,
                'unit' => $item->unit,
                'coa_file' => 'material-coas/' . $type . '.pdf',
                'status' => 'pending_qc_inspection',
            ]);
        }

        $items = collect($this->actingAs($qc)->getJson(route('notifications.summary'))->assertOk()->json('items'));
        foreach ([
            'ingredient' => 'Nguyên liệu chờ QC xác định chất lượng',
            'accessory' => 'Phụ liệu chờ QC xác định chất lượng',
            'excipient' => 'Tá dược chờ QC xác định chất lượng',
            'packaging' => 'Bao bì chờ QC xác định chất lượng',
        ] as $type => $label) {
            $notice = $items->firstWhere('label', $label);
            $this->assertSame(1, $notice['count']);
            $this->assertSame(route('qc.material-lots.index', ['type' => $type]), $notice['url']);
        }
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
