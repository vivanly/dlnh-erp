<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductQualityStandard;
use App\Models\ProductStorageMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QualityCatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_qc_can_manage_quality_standards_and_qa_cannot_modify_them(): void
    {
        $qc = User::factory()->create(['role' => 'qc']);
        $qa = User::factory()->create(['role' => 'qa']);
        $product = $this->product();

        $this->actingAs($qc)->get(route('product-quality-standards.index'))->assertOk()->assertSee(route('product-quality-standards.create'), false);
        $this->get(route('product-quality-standards.create'))->assertOk()->assertSee('name="standard_type"', false);
        $this->actingAs($qa)->get(route('product-quality-standards.create'))->assertForbidden();
        $this->actingAs($qa)->post(route('product-quality-standards.store'), $this->standardData($product))->assertForbidden();

        $this->actingAs($qc)->post(route('product-quality-standards.store'), $this->standardData($product))->assertRedirect(route('product-quality-standards.index'));
        $standard = ProductQualityStandard::sole();
        $this->actingAs($qc)->put(route('product-quality-standards.update', $standard), $this->standardData($product, ['indicator' => 'Moisture']))->assertRedirect(route('product-quality-standards.index'));
        $this->assertDatabaseHas('product_quality_standards', ['id' => $standard->id, 'indicator' => 'Moisture']);
        $this->get(route('product-quality-standards.edit', $standard))->assertOk()->assertSee('value="Moisture"', false);
        $this->actingAs($qc)->delete(route('product-quality-standards.destroy', $standard))->assertRedirect(route('product-quality-standards.index'));
        $this->assertDatabaseMissing('product_quality_standards', ['id' => $standard->id]);
    }

    public function test_qa_can_manage_storage_methods_and_qc_cannot_modify_them(): void
    {
        $qa = User::factory()->create(['role' => 'qa']);
        $qc = User::factory()->create(['role' => 'qc']);
        $product = $this->product();
        $data = ['product_id' => $product->id, 'storage_method' => 'Keep cool and dry', 'note' => 'Avoid sunlight'];

        $this->actingAs($qa)->get(route('product-storage-methods.index'))->assertOk()->assertSee(route('product-storage-methods.create'), false);
        $this->get(route('product-storage-methods.create'))->assertOk()->assertSee('name="storage_method"', false);
        $this->actingAs($qc)->get(route('product-storage-methods.create'))->assertForbidden();
        $this->actingAs($qc)->post(route('product-storage-methods.store'), $data)->assertForbidden();

        $this->actingAs($qa)->post(route('product-storage-methods.store'), $data)->assertRedirect(route('product-storage-methods.index'));
        $method = ProductStorageMethod::sole();
        $updated = array_merge($data, ['storage_method' => 'Store in a dry ventilated area']);
        $this->actingAs($qa)->put(route('product-storage-methods.update', $method), $updated)->assertRedirect(route('product-storage-methods.index'));
        $this->assertDatabaseHas('product_storage_methods', ['id' => $method->id, 'storage_method' => 'Store in a dry ventilated area']);
        $this->get(route('product-storage-methods.edit', $method))->assertOk()->assertSee('Store in a dry ventilated area');
        $this->actingAs($qa)->delete(route('product-storage-methods.destroy', $method))->assertRedirect(route('product-storage-methods.index'));
        $this->assertDatabaseMissing('product_storage_methods', ['id' => $method->id]);
    }

    public function test_it_can_manage_both_catalogs(): void
    {
        $it = User::factory()->create(['role' => 'it']);
        $product = $this->product();
        $this->actingAs($it);

        $this->post(route('product-quality-standards.store'), $this->standardData($product))->assertRedirect(route('product-quality-standards.index'));
        $this->post(route('product-storage-methods.store'), ['product_id' => $product->id, 'storage_method' => 'Store sealed'])->assertRedirect(route('product-storage-methods.index'));
        $this->assertDatabaseCount('product_quality_standards', 1);
        $this->assertDatabaseCount('product_storage_methods', 1);
    }

    private function product(): Product
    {
        return Product::create(['name' => 'Quality test product', 'slug' => 'quality-test-product', 'sku' => 'QUALITY-TEST']);
    }

    private function standardData(Product $product, array $overrides = []): array
    {
        return array_merge([
            'product_id' => $product->id,
            'standard_type' => 'Sensory',
            'indicator' => 'Color',
            'requirement' => 'Product typical appearance',
            'method' => 'Visual inspection',
            'note' => null,
        ], $overrides);
    }
}
