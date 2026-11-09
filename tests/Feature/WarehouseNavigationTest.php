<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_menu_groups_all_links_in_workflow_order(): void
    {
        $warehouse = User::factory()->create(['role' => 'warehouse']);
        $response = $this->actingAs($warehouse)->get(route('warehouse.stock'))->assertOk();
        $document = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new DOMXPath($document);
        $menus = $xpath->query('//details[./summary//span[normalize-space(.)="Kho"]]');
        $this->assertCount(1, $menus);
        $menu = $menus->item(0);
        $items = [];
        foreach ($xpath->query('./div/div | ./div/a', $menu) as $node) {
            $items[] = trim($node->textContent);
        }

        $this->assertSame([
            'Mua hàng',
            'Nhận đơn mua (NL thô / phụ liệu)',
            'Trả hàng nhà cung cấp',
            'Sản xuất',
            'Xuất / nhận lại NL sản xuất',
            'Nhập thành phẩm',
            'Đơn bán',
            'Kiểm tra tồn đơn bán',
            'Xuất đơn bán dược liệu thô',
            'Đóng gói và xuất hàng',
            'Đơn đã giao',
            'Tồn kho',
            'Tồn kho dược liệu / thành phẩm',
            'Tồn dược liệu thô / phụ liệu',
        ], $items);

        foreach ([
            'warehouse.purchase-orders',
            'supplier-returns.index',
            'warehouse.production-issues',
            'warehouse.production-batches',
            'warehouse.sales-order-stock-checks',
            'warehouse.material-issues',
            'warehouse.sales-orders',
            'warehouse.delivered-sales-orders',
            'warehouse.stock',
            'warehouse.material-stock',
        ] as $routeName) {
            $this->assertCount(1, $xpath->query('.//a[@href="'.route($routeName).'"]', $menu), $routeName);
        }

        $activeLink = $xpath->query('.//a[@href="'.route('warehouse.stock').'"]', $menu)->item(0);
        $this->assertStringContainsString('bg-blue-50', $activeLink->getAttribute('class'));
    }
}
