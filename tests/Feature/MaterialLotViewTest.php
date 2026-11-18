<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MaterialLotViewTest extends TestCase
{
    public function test_warehouse_and_quality_views_compile_to_valid_php(): void
    {
        foreach ([
            'views/material-lots/index.blade.php',
            'views/material-lots/ready-for-receipt.blade.php',
            'views/material-lots/stock-receipt.blade.php',
            'views/material-receipts/create.blade.php',
            'views/material-receipts/show.blade.php',
            'views/warehouse/customer-returns.blade.php',
            'views/layouts/partials/sidebar-links.blade.php',
        ] as $path) {
            $compiled = Blade::compileString(file_get_contents(resource_path($path)));

            $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE), $path);
        }
    }
}
