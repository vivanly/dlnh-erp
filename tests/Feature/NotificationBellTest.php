<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Order;
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
}
