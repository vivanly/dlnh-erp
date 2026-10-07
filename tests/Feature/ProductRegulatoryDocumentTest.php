<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductRegulatoryDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRegulatoryDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_regulatory_documents_filter_and_search_are_linked_to_products(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $firstProduct = Product::create([
            'name' => 'Atiso A',
            'slug' => 'atiso-a',
            'sku' => 'SKU-A',
        ]);
        $secondProduct = Product::create([
            'name' => 'Atiso B',
            'slug' => 'atiso-b',
            'sku' => 'SKU-B',
        ]);
        $firstDocument = ProductRegulatoryDocument::create([
            'product_id' => $firstProduct->id,
            'document_type' => 'cong_bo',
            'document_number' => 'DOC-A-001',
            'document_date' => '2026-01-01',
            'document_form' => 'Tự công bố',
        ]);
        ProductRegulatoryDocument::create([
            'product_id' => $secondProduct->id,
            'document_type' => 'dang_ky',
            'document_number' => 'DOC-B-001',
            'document_date' => '2026-01-02',
            'document_form' => 'Số tự đăng ký',
        ]);

        $this->actingAs($qaUser)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee(route('product-regulatory-documents.index', ['product_id' => $firstProduct->id]), false);

        $this->actingAs($qaUser)
            ->get(route('product-regulatory-documents.index', [
                'product_id' => $firstProduct->id,
                'search' => 'A',
                'document_type' => '',
            ]))
            ->assertOk()
            ->assertSee($firstProduct->name)
            ->assertSee($firstDocument->document_number)
            ->assertDontSee('DOC-B-001')
            ->assertViewHas('documents', fn ($documents) => $documents->count() === 1
                && $documents->first()->product_id === $firstProduct->id);
    }

    public function test_gpnk_can_be_managed_as_a_product_document(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $product = Product::create([
            'name' => 'Imported herb',
            'slug' => 'imported-herb',
            'sku' => 'SKU-GPNK',
            'classification' => 'Dược liệu Trung Quốc',
        ]);

        $this->actingAs($qaUser)
            ->get(route('product-regulatory-documents.create', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('Giấy phép nhập khẩu (GPNK)')
            ->assertSee('Imported herb - SKU-GPNK (Dược liệu Trung Quốc)')
            ->assertSee('Lọc theo phân loại')
            ->assertSee('value="Dược liệu Trung Quốc"', false)
            ->assertSee('data-classification="Dược liệu Trung Quốc"', false);

        $this->actingAs($qaUser)
            ->get(route('product-regulatory-documents.index', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('Imported herb - SKU-GPNK (Dược liệu Trung Quốc)');

        $this->actingAs($qaUser)
            ->post(route('product-regulatory-documents.store'), [
                'product_id' => $product->id,
                'document_type' => 'gpnk',
                'document_number' => 'GPNK-2026-001',
                'document_date' => '2026-01-10',
                'document_form' => 'Giấy phép nhập khẩu',
            ])
            ->assertRedirect(route('product-regulatory-documents.index', ['product_id' => $product->id]));

        $this->assertDatabaseHas('product_regulatory_documents', [
            'product_id' => $product->id,
            'document_type' => 'gpnk',
            'document_number' => 'GPNK-2026-001',
        ]);

        $this->actingAs($qaUser)
            ->get(route('product-regulatory-documents.index', ['document_type' => 'gpnk']))
            ->assertOk()
            ->assertSee('Giấy phép nhập khẩu (GPNK)')
            ->assertSee('GPNK-2026-001');
    }
}
