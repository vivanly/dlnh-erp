<?php

namespace Tests\Feature;

use App\Models\Accessory;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Excipient;
use App\Models\ManualLabelPrint;
use App\Models\Order;
use App\Models\PackagingMaterial;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->actingAs($user)->post(route('excipients.store'), [
            'sku' => 'TD001', 'name' => 'Tá dược A', 'unit' => 'Kg',
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('packaging.store'), [
            'sku' => 'BB001', 'name' => 'Bao bì A', 'unit' => 'Cái',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('raw_materials', ['sku' => 'NL001']);
        $this->assertDatabaseHas('accessories', ['sku' => 'PL001']);
        $this->assertDatabaseHas('excipients', ['sku' => 'TD001']);
        $this->assertDatabaseHas('packaging_materials', ['sku' => 'BB001']);
        $this->actingAs($user)->get(route('raw-materials.index'))->assertOk();
        $this->actingAs($user)->get(route('accessories.index'))->assertOk();
        $this->actingAs($user)->get(route('excipients.index'))->assertOk();
        $this->actingAs($user)->get(route('packaging.index'))->assertOk();
    }

    public function test_non_qa_user_cannot_manage_catalog(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)->post(route('raw-materials.store'), [
            'sku' => 'NL002', 'name' => 'X', 'unit' => 'Kg',
        ])->assertForbidden();
    }

    public function test_purchase_order_supports_each_purchase_catalog_type(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC1']);
        $raw = RawMaterial::create(['sku' => 'NL010', 'name' => 'NL', 'slug' => 'nl', 'unit' => 'Kg']);
        $acc = Accessory::create(['sku' => 'PL010', 'name' => 'PL', 'slug' => 'pl', 'unit' => 'Cái']);
        $excipient = Excipient::create(['sku' => 'TD010', 'name' => 'TD', 'slug' => 'td', 'unit' => 'Kg']);
        $packaging = PackagingMaterial::create(['sku' => 'BB010', 'name' => 'BB', 'slug' => 'bb', 'unit' => 'Cái']);
        $product = Product::create(['name' => 'SP', 'slug' => 'sp', 'sku' => 'SP010', 'unit' => 'Kg']);

        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-SP-1', 'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(),
            'items' => [['item_type' => 'product', 'item_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]],
        ])->assertSessionHasErrors('items.0.item_type');
        $this->assertDatabaseMissing('purchase_orders', ['po_number' => 'PO-SP-1']);

        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-TYPE-1', 'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(), 'item_type' => 'accessory',
            'items' => [['item_id' => $acc->id, 'quantity' => 3, 'unit_price' => 10]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('purchase_order_items', ['material_type' => 'PL', 'material_id' => $acc->id, 'quantity' => 3]);
        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-INGREDIENT-1', 'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(), 'item_type' => 'ingredient',
            'items' => [['item_id' => $acc->id, 'quantity' => 4, 'unit_price' => 10]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('purchase_order_items', ['material_type' => 'NL', 'material_id' => $acc->id, 'quantity' => 4]);
        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-EXCIPIENT-1', 'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(), 'item_type' => 'excipient',
            'items' => [['item_id' => $excipient->id, 'quantity' => 5, 'unit_price' => 10]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('purchase_order_items', ['material_type' => 'TD', 'material_id' => $excipient->id]);
        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-PACKAGING-1', 'supplier_id' => $supplier->id, 'order_date' => today()->toDateString(), 'item_type' => 'packaging',
            'items' => [['item_id' => $packaging->id, 'quantity' => 6, 'unit_price' => 10]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('purchase_order_items', ['material_type' => 'BB', 'material_id' => $packaging->id]);

        $this->actingAs($user)
            ->get(route('api.purchase-items.search', ['type' => 'BB', 'q' => 'BB010']))
            ->assertOk()
            ->assertJsonFragment(['id' => $packaging->id, 'sku' => 'BB010']);

        $this->actingAs($user)->get(route('purchase-orders.create'))
            ->assertOk()
            ->assertSee('NL · Nguyên Liệu')
            ->assertSee('Tá Dược')
            ->assertSee('Bao Bì');
        $this->actingAs($user)->post(route('purchase-orders.store'), [
            'po_number' => 'PO-MIX-1',
            'supplier_id' => $supplier->id,
            'order_date' => today()->toDateString(),
            'items' => [
                ['item_type' => 'DLT', 'item_id' => $raw->id, 'quantity' => 5, 'unit_price' => 100],
                ['item_type' => 'PL', 'item_id' => $acc->id, 'quantity' => 2, 'unit_price' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $po = PurchaseOrder::where('po_number', 'PO-MIX-1')->firstOrFail();
        $this->assertDatabaseHas('purchase_order_items', ['purchase_order_id' => $po->id, 'material_type' => 'DLT', 'material_id' => $raw->id, 'product_id' => null]);
        $this->assertDatabaseHas('purchase_order_items', ['purchase_order_id' => $po->id, 'material_type' => 'PL', 'material_id' => $acc->id]);

        $this->actingAs($user)->get(route('purchase-orders.show', $po))->assertOk()->assertSee('NL010');

        $this->actingAs($user)->delete(route('raw-materials.destroy', $raw))->assertSessionHas('error');
        $this->assertDatabaseHas('raw_materials', ['id' => $raw->id]);
    }

    public function test_sales_product_suggestions_follow_selected_order_type(): void
    {
        $user = $this->itUser();
        Product::create(['name' => 'DL product', 'slug' => 'dl-product', 'sku' => 'DL012', 'unit' => 'Kg', 'classification' => 'DL']);
        $processedHerb = Product::create(['name' => 'DS product', 'slug' => 'ds-product', 'sku' => 'DS012', 'unit' => 'Kg', 'classification' => 'DLSC']);

        $this->actingAs($user)
            ->get(route('api.products.search', ['order_type' => 'DLSC', 'q' => '012']))
            ->assertOk()
            ->assertJsonFragment(['sku' => 'DS012'])
            ->assertJsonMissing(['sku' => 'DL012']);

        $this->actingAs($user)->get(route('orders.create'))
            ->assertOk()
            ->assertSee('DLSC · Dược Liệu Sơ Chế')
            ->assertSee('Vị Thuốc');

        $customer = Customer::create(['code' => 'CUST-DS', 'name' => 'DS customer', 'type' => 'Retail', 'is_active' => true]);
        $orderCode = 'DLSC' . today()->format('ym') . '0001';
        $this->actingAs($user)->post(route('orders.store'), [
            'order_code' => $orderCode,
            'customer_id' => $customer->id,
            'order_type' => 'DLSC',
            'order_date' => today()->toDateString(),
            'delivery_date' => today()->addDay()->toDateString(),
            'province_city' => 'Hà Nội',
            'items' => [['item_id' => $processedHerb->id, 'quantity' => 1]],
        ])->assertRedirect(route('orders.index'));
        $this->assertDatabaseHas('orders', ['order_code' => $orderCode, 'order_type' => 'DLSC']);
        $this->assertDatabaseHas('order_items', ['product_id' => $processedHerb->id, 'raw_material_id' => null]);
    }

    public function test_sales_codes_filter_each_product_category_and_generate_matching_prefixes(): void
    {
        $user = $this->itUser();
        $products = [];
        foreach (Product::CLASSIFICATIONS as $code => $label) {
            $products[$code] = Product::create([
                'name' => $label,
                'slug' => 'catalog-' . strtolower($code),
                'sku' => 'CAT-' . $code,
                'unit' => 'Kg',
                'classification' => $code,
            ]);
        }

        foreach ($products as $code => $product) {
            $response = $this->actingAs($user)
                ->get(route('api.products.search', ['order_type' => $code, 'q' => 'CAT-']));
            $response->assertOk()->assertJsonCount(1)->assertJsonFragment(['sku' => $product->sku]);
        }

        $orderDate = today()->toDateString();
        foreach (\App\Models\Order::TYPE_CODES as $code) {
            $response = $this->actingAs($user)
                ->get(route('orders.suggested-code', ['order_type' => $code, 'order_date' => $orderDate]));
            $response->assertOk();
            $this->assertStringStartsWith($code . today()->format('ym'), $response->json('code'));
        }
    }

    public function test_type_code_migration_updates_catalogs_orders_and_print_history(): void
    {
        $user = $this->itUser();
        $supplier = Supplier::create(['name' => 'NCC', 'code' => 'NCC-CODE']);
        $customer = Customer::create(['code' => 'CUST-CODE', 'name' => 'Khách hàng', 'type' => 'Retail']);
        $product = Product::create(['name' => 'Vị thuốc', 'slug' => 'vi-thuoc-code', 'sku' => 'VT-CODE', 'classification' => 'DL']);
        DB::table('products')->where('id', $product->id)->update(['classification' => 'Vị Thuốc Cổ Truyền']);
        $order = Order::create([
            'order_code' => 'VT26010001',
            'customer_id' => $customer->id,
            'order_type' => 'VT',
            'order_date' => today(),
            'delivery_date' => today()->addDay(),
            'province_city' => 'Hà Nội',
            'status' => 'draft',
        ]);
        ManualLabelPrint::create([
            'label_type' => 'vt',
            'product_name' => 'Vị thuốc',
            'batch' => 'LOT-CODE',
            'copies_count' => 1,
            'order_code' => $order->order_code,
            'qr_content' => 'https://example.test/orders/' . $order->order_code,
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-CODE',
            'supplier_id' => $supplier->id,
            'user_id' => $user->id,
            'order_date' => today(),
            'status' => 'draft',
        ]);
        $raw = RawMaterial::create(['sku' => 'DLT-CODE', 'name' => 'Thô', 'slug' => 'tho-code', 'unit' => 'Kg']);
        $item = $purchaseOrder->items()->create([
            'material_type' => 'raw_material',
            'material_id' => $raw->id,
            'quantity' => 1,
            'unit' => 'Kg',
            'unit_price' => 1,
            'total_price' => 1,
        ]);

        $migration = require base_path('database/migrations/2026_11_16_000000_standardize_item_and_order_type_codes.php');
        $migration->up();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'classification' => 'VTCT']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_type' => 'VTCT', 'order_code' => 'VTCT26010001']);
        $this->assertDatabaseHas('manual_label_prints', [
            'order_code' => 'VTCT26010001',
            'qr_content' => 'https://example.test/orders/VTCT26010001',
        ]);
        $this->assertDatabaseHas('purchase_order_items', ['id' => $item->id, 'material_type' => 'DLT']);
    }

    public function test_catalogs_can_export_and_import_excel(): void
    {
        $user = $this->itUser();
        foreach ([
            'raw-materials' => \App\Models\RawMaterial::class,
            'accessories' => \App\Models\Accessory::class,
            'excipients' => Excipient::class,
            'packaging' => PackagingMaterial::class,
        ] as $prefix => $model) {
            $this->actingAs($user)->get(route($prefix . '.import.form'))->assertOk();
            $this->actingAs($user)->get(route($prefix . '.export'))->assertOk();

            $csv = "ma_hang_sku,ten_hang_hoa,dvt,phan_loai\nIMP-" . $prefix . ",Hang nhap," . "Kg,NL\n,thieu ma,Kg,NL\n";
            $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('import.csv', $csv);
            $this->actingAs($user)->post(route($prefix . '.import'), ['file' => $file])->assertRedirect();
            $this->assertDatabaseHas((new $model)->getTable(), ['sku' => 'IMP-' . $prefix, 'name' => 'Hang nhap']);
        }
    }}
