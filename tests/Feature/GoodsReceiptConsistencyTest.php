<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierBatch;
use App\Models\SupplierReturnOrder;
use App\Models\User;
use App\Services\InventoryLedger;
use App\Services\SupplierBatchAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GoodsReceiptConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_order_receipt_posts_once_and_only_received_good_quantity_enters_stock(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse_manager']);
        $supplier = Supplier::create(['name' => 'Receipt consistency supplier', 'code' => 'GR-CHECK-1']);
        $product = Product::create([
            'name' => 'Receipt consistency product',
            'slug' => 'receipt-consistency-product',
            'sku' => 'GR-CHECK-1',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-GR-CHECK-1',
            'supplier_id' => $supplier->id,
            'user_id' => $warehouseUser->id,
            'order_date' => today(),
            'expected_delivery_date' => today()->addDay(),
            'status' => 'delivered',
        ]);
        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit' => 'kg',
            'unit_price' => 1,
            'total_price' => 10,
        ]);
        $receiptData = [
            'receipt_code' => 'GRN-GR-CHECK-1',
            'receipt_date' => today()->toDateString(),
            'items' => [$item->id => [
                'product_id' => $product->id,
                'received_quantity' => 8,
                'returned_quantity' => 2,
                'return_reason' => 'Quality rejection',
                'batch_number' => 'GR-CHECK-LOT-1',
            ]],
        ];

        $this->actingAs($warehouseUser)
            ->post(route('goods-receipts.store', $purchaseOrder), $receiptData)
            ->assertRedirect(route('warehouse.purchase-orders'));

        $this->assertSame('completed', $purchaseOrder->fresh()->status);
        $this->assertSame(1, GoodsReceipt::count());
        $batch = SupplierBatch::sole();
        $this->assertEquals(8, $batch->initial_quantity);
        $this->assertEquals(8, $batch->current_quantity);
        $this->assertSame('pending_qa', $batch->status);
        $this->assertSame('pending', GoodsReceipt::sole()->qc_status);
        $this->assertEquals(8, InventoryMovement::where('movement_type', 'RECEIVE_PURCHASE')->sum('quantity'));

        Storage::fake('local');
        $qaUser = User::factory()->create(['role' => 'qa']);
        $qaData = [
            'batch_number' => $batch->batch_number,
            'mfg_date' => today()->subMonth()->toDateString(),
            'exp_date' => today()->addYear()->toDateString(),
        ];
        $this->actingAs($qaUser)
            ->patch(route('supplier-batches.update-coa', $batch), $qaData)
            ->assertRedirect();
        $this->assertSame('pending_qa', $batch->fresh()->status);

        $this->actingAs($qaUser)
            ->patch(route('supplier-batches.update-coa', $batch), $qaData + [
                'coa_file' => UploadedFile::fake()->create('supplier-coa.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();
        $this->assertSame('pending_qa', $batch->fresh()->status);
        $this->assertNotNull($batch->fresh()->coa_file);
        Storage::disk('local')->assertExists($batch->fresh()->coa_file);

        auth()->logout();
        $this->get(route('private-documents.supplier-coa', $batch))
            ->assertRedirect(route('login'));

        $this->actingAs($qaUser)
            ->get(route('private-documents.supplier-coa', $batch))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($qaUser)
            ->post(route('supplier-batches.approve', $batch))
            ->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'qc']))
            ->post(route('supplier-batches.approve', $batch))
            ->assertRedirect();
        $this->assertSame('active', $batch->fresh()->status);
        $this->assertSame('partially_passed', GoodsReceipt::sole()->qc_status);

        $this->actingAs($warehouseUser)
            ->post(route('goods-receipts.store', $purchaseOrder), array_merge($receiptData, ['receipt_code' => 'GRN-GR-CHECK-RETRY']))
            ->assertRedirect(route('warehouse.purchase-orders'))
            ->assertSessionHas('error');

        $this->assertSame(1, GoodsReceipt::count());
        $this->assertSame(1, SupplierBatch::count());
        $this->assertEquals(8, $batch->fresh()->current_quantity);
        $this->assertSame(1, InventoryMovement::where('movement_type', 'RECEIVE_PURCHASE')->count());
    }

    public function test_partial_receipt_keeps_purchase_order_open_until_a_later_delivery_resolves_the_balance(): void
    {
        $warehouseUser = User::factory()->create(['role' => 'warehouse_manager']);
        $supplier = Supplier::create(['name' => 'Partial receipt supplier', 'code' => 'GR-PARTIAL-1']);
        $product = Product::create([
            'name' => 'Partial receipt product',
            'slug' => 'partial-receipt-product',
            'sku' => 'GR-PARTIAL-1',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => 'PO-GR-PARTIAL-1',
            'supplier_id' => $supplier->id,
            'user_id' => $warehouseUser->id,
            'order_date' => today(),
            'expected_delivery_date' => today()->addDay(),
            'status' => 'delivered',
        ]);
        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit' => 'kg',
            'unit_price' => 1,
            'total_price' => 10,
        ]);

        $this->actingAs($warehouseUser)
            ->get(route('goods-receipts.create', $purchaseOrder))
            ->assertOk()
            ->assertSee('Còn xử lý');

        $this->actingAs($warehouseUser)
            ->post(route('goods-receipts.store', $purchaseOrder), [
                'receipt_code' => 'GRN-GR-PARTIAL-1A',
                'receipt_date' => today()->toDateString(),
                'items' => [$item->id => [
                    'product_id' => $product->id,
                    'received_quantity' => 4,
                    'returned_quantity' => 1,
                ]],
            ])
            ->assertRedirect(route('warehouse.purchase-orders'));

        $this->assertSame('approved', $purchaseOrder->fresh()->status);
        $this->assertEquals(4, SupplierBatch::sum('initial_quantity'));
        $this->assertEquals(4, InventoryMovement::where('movement_type', 'RECEIVE_PURCHASE')->sum('quantity'));

        $this->actingAs($warehouseUser)
            ->post(route('warehouse.purchase-orders.mark-delivered', $purchaseOrder))
            ->assertRedirect();

        $this->actingAs($warehouseUser)
            ->get(route('goods-receipts.create', $purchaseOrder))
            ->assertOk()
            ->assertSee('5.00 kg');

        $this->actingAs($warehouseUser)
            ->post(route('goods-receipts.store', $purchaseOrder), [
                'receipt_code' => 'GRN-GR-PARTIAL-1B',
                'receipt_date' => today()->toDateString(),
                'items' => [$item->id => [
                    'product_id' => $product->id,
                    'received_quantity' => 5,
                    'returned_quantity' => 0,
                ]],
            ])
            ->assertRedirect(route('warehouse.purchase-orders'));

        $this->assertSame('completed', $purchaseOrder->fresh()->status);
        $this->assertEquals(9, SupplierBatch::sum('initial_quantity'));
        $this->assertEquals(9, InventoryMovement::where('movement_type', 'RECEIVE_PURCHASE')->sum('quantity'));
        $this->assertEquals(1, GoodsReceiptItem::sum('returned_quantity'));
    }

}
