<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductBomItem;
use App\Services\BomMaterialCalculator;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class BomMaterialCalculatorTest extends TestCase
{
    public function test_calculates_raw_material_quantity_with_yield_loss(): void
    {
        $finishedProduct = new Product(['name' => 'Hoài Sơn thái lát', 'unit' => 'kg']);
        $rawProduct = new Product(['name' => 'Củ mài', 'unit' => 'kg']);

        $bom = new ProductBom([
            'output_quantity' => 100,
            'output_unit' => 'kg',
            'yield_rate' => 0.834,
        ]);
        $bom->setRelation('product', $finishedProduct);

        $component = new ProductBomItem([
            'quantity' => 100,
            'unit' => 'kg',
        ]);
        $component->setRelation('componentProduct', $rawProduct);
        $bom->setRelation('items', new Collection([$component]));

        $requirements = (new BomMaterialCalculator())->calculate($bom, 100, 'kg');

        $this->assertCount(1, $requirements);
        $this->assertSame('Củ mài', $requirements[0]['product_name']);
        $this->assertSame('kg', $requirements[0]['unit']);
        $this->assertEqualsWithDelta(119.9041, $requirements[0]['quantity'], 0.0001);
    }

    public function test_rejects_a_bom_with_zero_yield(): void
    {
        $bom = new ProductBom([
            'output_quantity' => 100,
            'output_unit' => 'kg',
            'yield_rate' => 0,
        ]);

        $this->expectException(DomainException::class);
        (new BomMaterialCalculator())->calculate($bom, 100, 'kg');
    }
}