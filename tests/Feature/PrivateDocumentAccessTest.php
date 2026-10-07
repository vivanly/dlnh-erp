<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\SupplierBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_documents_are_moved_to_private_storage(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('coas/supplier.pdf', '%PDF-supplier');
        Storage::disk('public')->put('qc-reports/finished.pdf', '%PDF-qc');
        Storage::disk('public')->put('unrelated/logo.png', 'logo');

        $this->artisan('documents:secure')
            ->expectsOutput('Secured 2 document(s).')
            ->assertSuccessful();

        Storage::disk('local')->assertExists('coas/supplier.pdf');
        Storage::disk('local')->assertExists('qc-reports/finished.pdf');
        Storage::disk('public')->assertMissing('coas/supplier.pdf');
        Storage::disk('public')->assertMissing('qc-reports/finished.pdf');
        Storage::disk('public')->assertExists('unrelated/logo.png');
    }

    public function test_only_authenticated_users_can_view_private_supplier_coa(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('coas/private.pdf', '%PDF-private');
        $product = Product::create([
            'name' => 'Private document product',
            'slug' => 'private-document-product',
            'sku' => 'PRIVATE-DOC-1',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'PRIVATE-DOC-LOT',
            'initial_quantity' => 1,
            'current_quantity' => 1,
            'coa_file' => 'coas/private.pdf',
        ]);

        $this->get(route('private-documents.supplier-coa', $batch))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->get(route('private-documents.supplier-coa', $batch))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_only_authenticated_users_can_view_private_production_qc_report(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('qc-reports/private.pdf', '%PDF-private-qc');

        $user = User::factory()->create(['role' => 'staff']);
        $customer = Customer::create([
            'code' => 'PRIVATE-DOC-CUSTOMER',
            'name' => 'Private document customer',
            'type' => 'Retail',
        ]);
        $product = Product::create([
            'name' => 'Private QC product',
            'slug' => 'private-qc-product',
            'sku' => 'PRIVATE-QC-1',
        ]);
        $order = Order::create([
            'order_code' => 'PRIVATE-DOC-ORDER',
            'customer_id' => $customer->id,
            'order_type' => 'DL',
            'order_date' => today(),
            'delivery_date' => today()->addDay(),
            'province_city' => 'Hà Nội',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'packaging_spec' => '1',
        ]);
        $bom = ProductBom::create([
            'product_id' => $product->id,
            'version' => 1,
            'output_quantity' => 1,
            'output_unit' => 'kg',
            'yield_rate' => 1,
            'is_active' => true,
        ]);
        $productionOrder = ProductionOrder::create([
            'production_code' => 'PRIVATE-DOC-PROD',
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_bom_id' => $bom->id,
            'product_id' => $product->id,
            'planned_quantity' => 1,
            'unit' => 'kg',
            'yield_rate' => 1,
            'created_by' => $user->id,
        ]);
        $batch = ProductionFinishedBatch::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $product->id,
            'batch_number' => 'PRIVATE-QC-LOT',
            'initial_quantity' => 1,
            'current_quantity' => 1,
            'unit' => 'kg',
            'qc_test_report_file' => 'qc-reports/private.pdf',
        ]);

        $this->get(route('private-documents.production-qc-report', $batch))
            ->assertRedirect(route('login'));

        $this->actingAs($user)
            ->get(route('private-documents.production-qc-report', $batch))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private');
    }
}
