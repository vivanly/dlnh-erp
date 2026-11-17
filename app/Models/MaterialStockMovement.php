<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialStockMovement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function getCatalogItemAttribute()
    {
        return CatalogItem::findByType($this->material_type, (int) $this->material_id);
    }

    public static function lotBalances(string $type, int $id, bool $onlyPositive = true)
    {
        $lots = static::where('material_type', $type)->where('material_id', $id)
            ->whereIn('direction', ['in', 'out'])->orderBy('id')->get()
            ->groupBy(fn ($movement) => $type === 'raw_material'
                ? 'supplier:' . (string) $movement->batch_number
                : ($movement->material_lot_id ? 'material_lot:' . $movement->material_lot_id : 'legacy'))
            ->map(function ($group) use ($type) {
                $first = $group->firstWhere('direction', 'in');

                return (object) [
                    'batch' => $type === 'raw_material' ? (string) $first?->batch_number : '',
                    'material_lot_id' => $first?->material_lot_id,
                    'received' => (float) $group->where('movement_type', 'RECEIVE_PURCHASE')->sum('quantity'),
                    'returned' => (float) $group->where('movement_type', 'RETURN_SUPPLIER')->sum('quantity'),
                    'issued' => (float) $group->whereIn('movement_type', ['ISSUE_SALE', 'ISSUE_PRODUCTION'])->sum('quantity') - (float) $group->where('movement_type', 'RETURN_PRODUCTION')->sum('quantity'),
                    'balance' => (float) $group->where('direction', 'in')->sum('quantity') - (float) $group->where('direction', 'out')->sum('quantity'),
                    'exp_date' => $first?->exp_date,
                    'first_id' => $first?->id ?? PHP_INT_MAX,
                ];
            })
            ->sort(fn ($a, $b) => $type === 'raw_material'
                ? [$a->exp_date ?? '9999-12-31', $a->first_id] <=> [$b->exp_date ?? '9999-12-31', $b->first_id]
                : $a->first_id <=> $b->first_id)
            ->values();

        if ($type !== 'raw_material') {
            $legacy = $lots->firstWhere('material_lot_id', null);
            if ($legacy && $legacy->balance < -0.0001) {
                $remainingLegacyIssue = abs($legacy->balance);
                $layerLots = $lots->reject(fn ($lot) => $lot->material_lot_id === null)->values();

                foreach ($layerLots as $lot) {
                    if ($remainingLegacyIssue <= 0.0001) {
                        break;
                    }

                    $allocatedIssue = min(max(0, $lot->balance), $remainingLegacyIssue);
                    $lot->balance -= $allocatedIssue;
                    $lot->issued += $allocatedIssue;
                    $remainingLegacyIssue -= $allocatedIssue;
                }

                $lots = $remainingLegacyIssue > 0.0001
                    ? $layerLots->push($legacy)
                    : $layerLots;
            }
        }

        return $onlyPositive ? $lots->filter(fn ($l) => $l->balance > 0.0001)->values() : $lots;
    }

    public static function lotBalance(string $type, int $id, ?string $batch): float
    {
        if ($type !== 'raw_material' && (string) $batch === '') {
            return (float) static::lotBalances($type, $id, false)
                ->where('batch', '')
                ->sum('balance');
        }

        return (float) static::lotBalances($type, $id, false)->firstWhere('batch', (string) $batch)?->balance;
    }

    public static function layerBalance(string $type, int $id, int $materialLotId): float
    {
        return (float) static::lotBalances($type, $id, false)
            ->firstWhere('material_lot_id', $materialLotId)?->balance;
    }

    public static function balance(string $type, int $id): float
    {
        $in = (float) static::where('material_type', $type)->where('material_id', $id)->where('direction', 'in')->sum('quantity');
        $out = (float) static::where('material_type', $type)->where('material_id', $id)->where('direction', 'out')->sum('quantity');

        return $in - $out;
    }
}
