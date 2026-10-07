<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BomApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_planning_submits_bom_and_only_director_or_it_can_approve_it(): void
    {
        $planningUser = User::factory()->create(['role' => 'production_planner']);
        $qaUser = User::factory()->create(['role' => 'qa']);
        $itUser = User::factory()->create(['role' => 'it']);
        $directorUser = User::factory()->create(['role' => 'general_director']);
        $finishedProduct = Product::create([
            'name' => 'BOM approval finished product',
            'slug' => 'bom-approval-finished-product',
            'sku' => 'BOM-APPROVAL-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $rawProduct = Product::create([
            'name' => 'BOM approval raw material',
            'slug' => 'bom-approval-raw-material',
            'sku' => 'BOM-APPROVAL-RAW',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);

        $payload = [
            'product_id' => $finishedProduct->id,
            'output_quantity' => 100,
            'output_unit' => 'kg',
            'output_to_base_factor' => 1,
            'yield_percent' => 95,
            'notes' => 'BOM chờ duyệt',
            'items' => [[
                'component_product_id' => $rawProduct->id,
                'quantity' => 120,
                'unit' => 'kg',
                'to_base_factor' => 1,
            ]],
        ];

        $this->actingAs($planningUser)
            ->post(route('boms.store'), $payload)
            ->assertRedirect(route('boms.index'));

        $bom = ProductBom::sole();
        $this->assertSame('pending_approval', $bom->status);
        $this->assertFalse($bom->is_active);
        $this->assertSame($planningUser->id, $bom->submitted_by);

        $this->actingAs($planningUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Kế hoạch nguyên liệu');
        $this->actingAs($itUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kế hoạch nguyên liệu');

        $this->actingAs($directorUser)
            ->get(route('boms.index'))
            ->assertOk()
            ->assertDontSee('Duyệt định mức BOM')
            ->assertDontSee('Định mức sản xuất (BOM)')
            ->assertDontSee('Nhu cầu nguyên liệu')
            ->assertSee('Duyệt BOM')
            ->assertDontSee('Tạo phiên bản BOM')
            ->assertDontSee('Tính nhu cầu MRP');
        $this->get(route('boms.create'))->assertForbidden();

        $this->actingAs($itUser)
            ->get(route('boms.create'))
            ->assertOk()
            ->assertSee('Duyệt định mức BOM')
            ->assertSee('Định mức sản xuất (BOM)')
            ->assertSee('Nhu cầu nguyên liệu');

        $this->actingAs($qaUser)
            ->get(route('boms.index'))
            ->assertOk()
            ->assertDontSee('Duyệt định mức BOM')
            ->assertDontSee('Định mức sản xuất (BOM)')
            ->assertDontSee('Nhu cầu nguyên liệu')
            ->assertDontSee('Tạo phiên bản BOM');
        $this->get(route('boms.create'))->assertForbidden();
        $this->post(route('boms.store'), $payload)->assertForbidden();
        $this->assertSame(1, ProductBom::count());

        $this->actingAs($qaUser)
            ->post(route('boms.approve', $bom))
            ->assertForbidden();
        $this->assertSame('pending_approval', $bom->fresh()->status);

        $this->actingAs($directorUser)
            ->post(route('boms.approve', $bom))
            ->assertRedirect();

        $this->assertSame('approved', $bom->fresh()->status);
        $this->assertTrue($bom->fresh()->is_active);
        $this->assertSame($directorUser->id, $bom->fresh()->approved_by);
    }

    public function test_rejected_bom_is_not_available_for_mrp(): void
    {
        $planningUser = User::factory()->create(['role' => 'production_planner']);
        $directorUser = User::factory()->create(['role' => 'director']);
        $finishedProduct = Product::create([
            'name' => 'BOM rejection finished product',
            'slug' => 'bom-rejection-finished-product',
            'sku' => 'BOM-REJECTION-FIN',
            'unit' => 'kg',
            'classification' => 'VT',
        ]);
        $rawProduct = Product::create([
            'name' => 'BOM rejection raw material',
            'slug' => 'bom-rejection-raw-material',
            'sku' => 'BOM-REJECTION-RAW',
            'unit' => 'kg',
            'classification' => 'DL',
        ]);

        $this->actingAs($planningUser)->post(route('boms.store'), [
            'product_id' => $finishedProduct->id,
            'output_quantity' => 10,
            'output_unit' => 'kg',
            'yield_percent' => 100,
            'items' => [[
                'component_product_id' => $rawProduct->id,
                'quantity' => 10,
                'unit' => 'kg',
            ]],
        ])->assertRedirect();

        $bom = ProductBom::sole();
        $this->actingAs($directorUser)
            ->post(route('boms.reject', $bom), ['reason' => 'Cần cập nhật định mức hao hụt'])
            ->assertRedirect();

        $this->assertSame('rejected', $bom->fresh()->status);
        $this->assertFalse($bom->fresh()->is_active);
        $this->actingAs($planningUser)
            ->get(route('boms.plan', ['product_id' => $finishedProduct->id, 'quantity' => 10]))
            ->assertOk()
            ->assertSee('chưa có BOM đang áp dụng');
    }
}
