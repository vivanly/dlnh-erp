<?php

namespace Tests\Feature;

use App\Exports\ProductsExport;
use App\Exports\PpcbsExport;
use App\Models\Product;
use App\Models\Ppcb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class QaCatalogExcelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_catalog_searches_by_name_sorts_by_classification_and_limits_page_sizes(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        Product::create(['name' => 'Bạch chỉ', 'slug' => 'bach-chi', 'sku' => 'SKU-BC', 'classification' => 'DL']);
        Product::create(['name' => 'Atiso', 'slug' => 'atiso', 'sku' => 'SKU-AT', 'classification' => 'VT']);
        Product::create(['name' => 'Cam thảo', 'slug' => 'cam-thao', 'sku' => 'SKU-CT', 'classification' => 'DL']);

        $this->actingAs($qaUser)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('100 dòng')
            ->assertViewHas('products', function ($products) {
                return $products->perPage() === 100
                    && $products->pluck('name')->all() === ['Bạch chỉ', 'Cam thảo', 'Atiso'];
            });

        $this->actingAs($qaUser)
            ->get(route('products.index', ['search' => 'Atiso']))
            ->assertViewHas('products', fn ($products) => $products->pluck('name')->all() === ['Atiso']);

        $this->actingAs($qaUser)
            ->get(route('products.index', ['search' => 'SKU-AT']))
            ->assertViewHas('products', fn ($products) => $products->isEmpty());

        $this->actingAs($qaUser)
            ->get(route('products.index', ['classification' => 'DL', 'per_page' => 500]))
            ->assertOk()
            ->assertSee('500 dòng')
            ->assertSee('value="DL" selected', false)
            ->assertViewHas('products', fn ($products) => $products->perPage() === 500
                && $products->pluck('name')->all() === ['Bạch chỉ', 'Cam thảo']);

        $this->actingAs($qaUser)
            ->get(route('products.index', ['per_page' => 1000]))
            ->assertSee('1000 dòng')
            ->assertViewHas('products', fn ($products) => $products->perPage() === 1000);

        $this->actingAs($qaUser)
            ->get(route('products.index', ['per_page' => 50]))
            ->assertViewHas('products', fn ($products) => $products->perPage() === 100);
    }

    public function test_editing_product_returns_to_same_catalog_filters_and_product_row(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $product = Product::create([
            'name' => 'Atiso',
            'slug' => 'atiso-return',
            'sku' => 'SKU-RETURN',
            'classification' => 'DL',
        ]);
        $query = [
            'search' => 'Atiso',
            'classification' => 'DL',
            'per_page' => 500,
            'page' => 1,
        ];

        $this->actingAs($qaUser)
            ->get(route('products.index', $query))
            ->assertOk()
            ->assertSee(route('products.edit', $product->id) . '?search=Atiso&amp;classification=DL&amp;per_page=500&amp;page=1', false);

        $this->actingAs($qaUser)
            ->get(route('products.edit', ['product' => $product->id] + $query))
            ->assertOk()
            ->assertSee('name="return_search" value="Atiso"', false)
            ->assertSee('name="return_classification" value="DL"', false)
            ->assertSee('name="return_per_page" value="500"', false)
            ->assertSee('name="return_page" value="1"', false);

        $this->actingAs($qaUser)
            ->put(route('products.update', $product), [
                'name' => 'Atiso',
                'sku' => 'SKU-RETURN',
                'classification' => 'DL',
                'return_search' => 'Atiso',
                'return_classification' => 'DL',
                'return_per_page' => 500,
                'return_page' => 1,
            ])
            ->assertRedirect(route('products.index', $query) . '#product-' . $product->id);
    }

    public function test_qa_can_import_and_export_products_with_matching_columns(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $ppcb = Ppcb::create(['ma' => 'P01', 'ten_ppcb' => 'Sấy']);

        $this->actingAs($qaUser)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Import Excel',
                'Export Excel',
                'STT',
                'Phân loại',
                'Mã hàng',
                'Tên hàng',
                'ĐVT',
                'Nguồn gốc',
                'Bộ phận dùng',
                'PPCB',
                'Tên khoa học',
                'Ghi chú',
                'Mã GTIN',
                'Hành động',
            ]);

        $this->actingAs($qaUser)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSeeInOrder([
                'Phân loại',
                'Mã hàng (SKU)',
                'Tên hàng hóa',
                'Đơn vị tính (ĐVT)',
                'Nguồn gốc',
                'Bộ phận dùng',
                'PPCB',
                'Tên khoa học',
                'Ghi chú',
                'Mã GTIN',
                'Tài liệu tham khảo tên khoa học',
            ]);

        $this->actingAs($qaUser)
            ->get(route('products.import.form'))
            ->assertOk()
            ->assertSeeInOrder([
                'phan_loai',
                'ma_hang_sku',
                'ten_hang_hoa',
                'dvt',
                'nguon_goc',
                'bo_phan_dung',
                'ma_ppcb',
                'ten_khoa_hoc',
                'ghi_chu',
                'gtin',
                'tai_lieu_tham_khao',
            ]);

        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "phan_loai,ma_hang_sku,ten_hang_hoa,dvt,nguon_goc,bo_phan_dung,ma_ppcb,ten_khoa_hoc,ghi_chu,gtin,tai_lieu_tham_khao\n"
            . "Thuốc,SKU-1,Atiso,Kg,Đà Lạt,Lá,P01,Cynara,Ghi chú,GTIN-1,Tài liệu\n"
        );

        $this->actingAs($qaUser)
            ->post(route('products.import'), ['file' => $file])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Import thành công 1 sản phẩm!');

        $product = Product::where('sku', 'SKU-1')->firstOrFail();
        $this->assertSame($ppcb->id, $product->ppcb_id);

        $this->actingAs($qaUser)
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertSeeInOrder([
                'Phân loại',
                'Mã hàng (SKU)',
                'Tên hàng hóa',
                'Đơn vị tính (ĐVT)',
                'Nguồn gốc',
                'Bộ phận dùng',
                'PPCB',
                'Tên khoa học',
                'Ghi chú',
                'Mã GTIN',
                'Tài liệu tham khảo tên khoa học',
            ]);

        Excel::fake();
        $this->actingAs($qaUser)->get(route('products.export'))->assertOk();
        Excel::assertDownloaded('danh-muc-san-pham.xlsx', function (ProductsExport $export) use ($product) {
            $this->assertSame([
                'phan_loai',
                'ma_hang_sku',
                'ten_hang_hoa',
                'dvt',
                'nguon_goc',
                'bo_phan_dung',
                'ma_ppcb',
                'ten_khoa_hoc',
                'ghi_chu',
                'gtin',
                'tai_lieu_tham_khao',
            ], $export->headings());
            $this->assertSame('P01', $export->map($product)[6]);
            $this->assertSame('Tài liệu', $export->map($product)[10]);

            return true;
        });
    }

    public function test_qa_can_import_and_export_processing_methods(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);

        $this->actingAs($qaUser)
            ->get(route('ppcb.index'))
            ->assertOk()
            ->assertSeeInOrder(['Import Excel', 'Export Excel']);

        $file = UploadedFile::fake()->createWithContent(
            'ppcb.csv',
            "ma,ten_ppcb,chi_tiet_ppcb,ghi_chu\nP02,Phơi khô,Phơi tự nhiên,Ghi chú\n"
        );

        $this->actingAs($qaUser)
            ->post(route('ppcb.import'), ['file' => $file])
            ->assertRedirect(route('ppcb.index'))
            ->assertSessionHas('success', 'Import thành công 1 phương pháp chế biến!');

        $this->assertDatabaseHas('ppcb', ['ma' => 'P02', 'ten_ppcb' => 'Phơi khô']);

        Excel::fake();
        $this->actingAs($qaUser)->get(route('ppcb.export'))->assertOk();
        Excel::assertDownloaded('phuong-phap-che-bien.xlsx', function (PpcbsExport $export) {
            $this->assertSame(['ma', 'ten_ppcb', 'chi_tiet_ppcb', 'ghi_chu'], $export->headings());
            $this->assertCount(1, $export->query()->get());

            return true;
        });
    }

    public function test_duplicate_product_skus_are_skipped_and_reported(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        Product::create([
            'name' => 'Existing product',
            'slug' => 'existing-product',
            'sku' => 'SKU-DUP',
        ]);
        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "ma_hang_sku,ten_hang_hoa\nSKU-DUP,Duplicate product\n"
        );

        $this->actingAs($qaUser)
            ->post(route('products.import'), ['file' => $file])
            ->assertRedirect(route('products.import.form'))
            ->assertSessionHas('warning', 'Đã import 0 sản phẩm; bỏ qua 1 dòng không hợp lệ.')
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_products_with_unknown_processing_method_are_skipped_and_reported(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        $file = UploadedFile::fake()->createWithContent(
            'products.csv',
            "ma_hang_sku,ten_hang_hoa,ma_ppcb\nSKU-UNKNOWN,Unknown method product,P-UNKNOWN\n"
        );

        $this->actingAs($qaUser)
            ->post(route('products.import'), ['file' => $file])
            ->assertRedirect(route('products.import.form'))
            ->assertSessionHas('warning', 'Đã import 0 sản phẩm; bỏ qua 1 dòng không hợp lệ.')
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_duplicate_processing_method_codes_are_skipped_and_reported(): void
    {
        $qaUser = User::factory()->create(['role' => 'qa']);
        Ppcb::create(['ma' => 'P-DUP', 'ten_ppcb' => 'Existing method']);
        $file = UploadedFile::fake()->createWithContent(
            'ppcb.csv',
            "ma,ten_ppcb\nP-DUP,Duplicate method\n"
        );

        $this->actingAs($qaUser)
            ->post(route('ppcb.import'), ['file' => $file])
            ->assertRedirect(route('ppcb.import.form'))
            ->assertSessionHas('warning', 'Đã import 0 phương pháp chế biến; bỏ qua 1 dòng không hợp lệ.')
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('ppcb', 1);
    }

    public function test_users_outside_qa_and_it_cannot_import_or_export_qa_catalogs(): void
    {
        $user = User::factory()->create(['role' => 'staff']);

        $this->actingAs($user)
            ->get(route('products.import.form'))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error');
        $this->actingAs($user)->post(route('products.import'))->assertForbidden();
        $this->actingAs($user)->get(route('products.export'))->assertForbidden();
        $this->actingAs($user)->get(route('ppcb.export'))->assertForbidden();
        $this->actingAs($user)->get(route('ppcb.import.form'))->assertForbidden();
        $this->actingAs($user)->post(route('ppcb.import'))->assertForbidden();
    }
}
