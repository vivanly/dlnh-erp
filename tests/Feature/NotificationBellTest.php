<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
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
        $this->assertNotContains('Lô thành phẩm chờ phiếu kiểm nghiệm', collect($qcResponse->json('items'))->pluck('label')->all());

        $batch->update(['pending_warehouse_quantity' => 0]);
        $qcResponse = $this->actingAs($qcUser)->getJson(route('notifications.summary'))->assertOk();
        $this->assertContains('Lô thành phẩm chờ phiếu kiểm nghiệm', collect($qcResponse->json('items'))->pluck('label')->all());

        $this->assertSame(0.0, (float) $productionOrder->fresh()->pending_finished_quantity);
    }
}
