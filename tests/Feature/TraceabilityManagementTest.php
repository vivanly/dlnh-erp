<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SupplierBatch;
use App\Models\TraceabilityLotCode;
use App\Models\TraceabilitySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lots_have_editable_trace_codes_and_fixed_qr_base_url(): void
    {
        $user = User::factory()->create(['role' => 'it']);
        $product = Product::create([
            'name' => 'Traceability herb',
            'slug' => 'traceability-herb',
            'origin' => 'Việt Nam',
            'unit' => 'kg',
            'classification' => 'DL',
            'sku' => 'TRACE-001',
        ]);
        $batch = SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => 'TRACE-LOT-001',
            'initial_quantity' => 10,
            'current_quantity' => 8,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('traceability.management.index'))
            ->assertOk()
            ->assertSee('TRACE-LOT-001')
            ->assertSee('NCC-' . $batch->id);

        $this->actingAs($user)
            ->get(route('traceability.management.index', ['type' => 'supplier', 'per_page' => 5000]))
            ->assertOk()
            ->assertSee('TRACE-LOT-001')
            ->assertSee('value="5000" selected', false)
            ->assertSee('value="supplier" selected', false);

        $this->actingAs($user)
            ->get(route('traceability.management.index', ['type' => 'finished']))
            ->assertOk()
            ->assertDontSee('TRACE-LOT-001')
            ->assertSee('value="finished" selected', false);

        $this->assertDatabaseMissing('traceability_lot_codes', [
            'supplier_batch_id' => $batch->id,
        ]);
        $this->assertDatabaseCount('traceability_settings', 0);

        $this->actingAs($user)
            ->put(route('traceability.management.base-url.update'), [
                'base_url' => 'https://trace.example.test/code=',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('traceability.management.lot-code.update', ['supplier', $batch->id]), [
                'trace_code' => 'CUSTOM-TRACE-001',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'https://trace.example.test/code=CUSTOM-TRACE-001',
            TraceabilitySetting::query()->firstOrFail()->base_url
                . TraceabilityLotCode::query()->where('supplier_batch_id', $batch->id)->value('trace_code')
        );

        $this->actingAs($user)
            ->delete(route('traceability.management.lot-code.delete', ['supplier', $batch->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('traceability_lot_codes', [
            'supplier_batch_id' => $batch->id,
            'trace_code' => null,
        ]);

        $this->actingAs($user)
            ->delete(route('traceability.management.base-url.delete'))
            ->assertSessionHasNoErrors();
        $this->assertNull(TraceabilitySetting::query()->firstOrFail()->base_url);
    }

    public function test_duplicate_trace_code_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'it']);
        $product = Product::create([
            'name' => 'Unique trace herb',
            'slug' => 'unique-trace-herb',
            'origin' => 'Việt Nam',
            'unit' => 'kg',
            'classification' => 'DL',
            'sku' => 'TRACE-UNIQUE',
        ]);
        $batches = collect(['TRACE-UNIQUE-1', 'TRACE-UNIQUE-2'])->map(fn ($number) => SupplierBatch::create([
            'goods_receipt_item_id' => 1,
            'product_id' => $product->id,
            'batch_number' => $number,
            'initial_quantity' => 1,
            'current_quantity' => 1,
            'status' => 'active',
        ]));
        TraceabilityLotCode::create([
            'supplier_batch_id' => $batches[0]->id,
            'trace_code' => 'TRACE-DUPLICATE',
        ]);
        TraceabilityLotCode::create([
            'supplier_batch_id' => $batches[1]->id,
            'trace_code' => 'TRACE-UNIQUE-2',
        ]);

        $this->actingAs($user)
            ->put(route('traceability.management.lot-code.update', ['supplier', $batches[1]->id]), [
                'trace_code' => 'TRACE-DUPLICATE',
            ])
            ->assertSessionHasErrors('trace_code');
    }

    public function test_traceability_is_readable_to_all_but_only_qa_and_it_can_manage_it(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $warehouseUser = User::factory()->create(['role' => 'warehouse']);

        $this->actingAs($qaUser)
            ->get(route('traceability.management.index'))
            ->assertOk();

        $this->actingAs($warehouseUser)
            ->get(route('traceability.management.index'))
            ->assertOk()
            ->assertDontSee('Lưu đường dẫn')
            ->assertDontSee('Cập nhật chuỗi');

        $this->actingAs($warehouseUser)
            ->put(route('traceability.management.base-url.update'), [
                'base_url' => 'https://trace.example.test/code=',
            ])
            ->assertForbidden();
    }
}
