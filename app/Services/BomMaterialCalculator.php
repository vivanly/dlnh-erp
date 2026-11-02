<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBom;
use Illuminate\Database\Eloquent\Model;
use DomainException;

class BomMaterialCalculator
{
    public function calculate(ProductBom $bom, float $targetQuantity, string $targetUnit): array
    {
        if ($targetQuantity <= 0) {
            throw new DomainException('Số lượng thành phẩm cần sản xuất phải lớn hơn 0.');
        }

        $yieldRate = (float) $bom->yield_rate;
        if ($yieldRate <= 0 || $yieldRate > 1) {
            throw new DomainException('Tỷ lệ thu hồi của BOM phải lớn hơn 0 và không vượt quá 1.');
        }

        $targetBaseQuantity = $targetQuantity * $this->conversionFactor($bom->product, $targetUnit);
        $outputBaseQuantity = (float) $bom->output_quantity * $this->conversionFactor($bom->product, $bom->output_unit);
        if ($outputBaseQuantity <= 0) {
            throw new DomainException('Sản lượng cơ sở của BOM phải lớn hơn 0.');
        }

        $batchFactor = $targetBaseQuantity / ($outputBaseQuantity * $yieldRate);
        $requirements = [];

        foreach ($bom->items as $item) {
            $component = $item->component_type
                ? $item->component
                : $item->componentProduct;
            if (!$component) {
                throw new DomainException('BOM có nguyên liệu không tồn tại.');
            }
            $baseQuantity = (float) $item->quantity * $this->conversionFactor($component, $item->unit) * $batchFactor;
            $type = match (true) {
                $component instanceof Product => 'product',
                default => match ($component::class) {
                    \App\Models\RawMaterial::class => 'raw_material',
                    \App\Models\Accessory::class => 'accessory',
                    \App\Models\Excipient::class => 'excipient',
                    \App\Models\PackagingMaterial::class => 'packaging',
                    default => throw new DomainException('BOM có loại nguyên liệu không được hỗ trợ.'),
                },
            };
            $key = $type . ':' . $component->id;

            if (!isset($requirements[$key])) {
                $requirements[$key] = [
                    'material_type' => $type,
                    'material_id' => $component->id,
                    'product_id' => $component instanceof Product ? $component->id : null,
                    'product_name' => $component->name,
                    'sku' => $component->sku,
                    'quantity' => 0.0,
                    'unit' => $component->unit,
                ];
            }

            $requirements[$key]['quantity'] += $baseQuantity;
        }

        foreach ($requirements as &$requirement) {
            $requirement['quantity'] = round($requirement['quantity'], 4);
        }
        unset($requirement);

        return array_values($requirements);
    }

    private function conversionFactor(Model $component, string $unit): float
    {
        if (strcasecmp(trim((string) $component->unit), trim($unit)) === 0) {
            return 1.0;
        }

        if (!$component instanceof Product) {
            throw new DomainException("Đơn vị {$unit} phải trùng đơn vị gốc {$component->unit} cho {$component->name}.");
        }

        $conversion = $component->unitConversions()
            ->whereRaw('LOWER(unit) = ?', [mb_strtolower(trim($unit))])
            ->first();

        if (!$conversion || (float) $conversion->to_base_factor <= 0) {
            throw new DomainException("Chưa khai báo quy đổi đơn vị {$unit} sang đơn vị gốc cho sản phẩm {$component->name}.");
        }

        return (float) $conversion->to_base_factor;
    }
}