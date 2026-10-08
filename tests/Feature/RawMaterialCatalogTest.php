<?php

namespace Tests\Feature;

use App\Models\Accessory;
use App\Models\Department;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawMaterialCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function itUser(): User
    {
        $department = Department::firstOrCreate(['code' => 'IT'], ['name' => 'IT']);

        return User::factory()->create(['department_id' => $department->id, 'role' => 'it']);
    }

    public function test_it_can_create_raw_material_and_accessory(): void
    {
        $user = $this->itUser();

        $this->actingAs($user)->post(route('raw-materials.store'), [
            'sku' => 'NL001', 'name' => 'Nguyên liệu A', 'unit' => 'Kg',
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('accessories.store'), [
            'sku' => 'PL001', 'name' => 'Phụ liệu A', 'unit' => 'Cái',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('raw_materials', ['sku' => 'NL001']);
        $this->assertDatabaseHas('accessories', ['sku' => 'PL001']);
        $this->actingAs($user)->get(route('raw-materials.index'))->assertOk();
        $this->actingAs($user)->get(route('accessories.index'))->assertOk();
    }

    public function test_non_qa_user_cannot_manage_catalog(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)->post(route('raw-materials.store'), [
            'sku' => 'NL002', 'name' => 'X', 'unit' => 'Kg',
        ])->assertForbidden();
    }

    public function test_purchase_order_accepts_mixed_items_and_legacy_product_payload(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC1']);
        $raw = RawMaterial::create(['sku' => 'NL010', 'name' => 'NL', 'slug' => 'nl', 'unit' => 'Kg']);
        $acc = Accessory::create(['sku' => 'PL010', 'name' => 'PL', 'slug' => 'pl', 'unit' => 'Cái']);
        $product = Product::create(['name' => 'SP', 'slug' => 'sp', 'sku' => 'SP010', 'unit' => 'Kg']);

        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-MIX-1',
            'supplier_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'items' => [
                ['item_type' => 'raw_material', 'item_id' => $raw->id, 'quantity' => 5, 'unit_price' => 100],
                ['item_type' => 'accessory', 'item_id' => $acc->id, 'quantity' => 2, 'unit_price' => 10],
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50],
            ],
        ])->assertSessionHasNoErrors();

        $po = PurchaseOrder::where('po_number', 'PO-MIX-1')->firstOrFail();
        $this->assertDatabaseHas('purchase_order_items', ['purchase_order_id' => $po->id, 'material_type' => 'raw_material', 'material_id' => $raw->id, 'product_id' => null]);
        $this->assertDatabaseHas('purchase_order_items', ['purchase_order_id' => $po->id, 'material_type' => 'accessory', 'material_id' => $acc->id]);
        $this->assertDatabaseHas('purchase_order_items', ['purchase_order_id' => $po->id, 'product_id' => $product->id]);

        $this->actingAs($user)->get(route('purchase-orders.show', $po))->assertOk()->assertSee('NL010');

        $this->actingAs($user)->delete(route('raw-materials.destroy', $raw))->assertSessionHas('error');
        $this->assertDatabaseHas('raw_materials', ['id' => $raw->id]);
    }
}
