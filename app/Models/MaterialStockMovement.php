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
        return $this->material_type === 'accessory'
            ? Accessory::find($this->material_id)
            : RawMaterial::find($this->material_id);
    }

    public static function lotBalances(string $type, int $id, bool $onlyPositive = true)
    {
        $lots = static::where('material_type', $type)->where('material_id', $id)
            ->whereIn('direction', ['in', 'out'])->orderBy('id')->get()
            ->groupBy(fn ($m) => (string) $m->batch_number)
            ->map(function ($group, $batch) {
                $first = $group->firstWhere('direction', 'in');

                return (object) [
                    'batch' => $batch,
                    'received' => (float) $group->where('movement_type', 'RECEIVE_PURCHASE')->sum('quantity'),
                    'returned' => (float) $group->where('movement_type', 'RETURN_SUPPLIER')->sum('quantity'),
                    'issued' => (float) $group->whereIn('movement_type', ['ISSUE_SALE', 'ISSUE_PRODUCTION'])->sum('quantity') - (float) $group->where('movement_type', 'RETURN_PRODUCTION')->sum('quantity'),
                    'balance' => (float) $group->where('direction', 'in')->sum('quantity') - (float) $group->where('direction', 'out')->sum('quantity'),
                    'exp_date' => $first?->exp_date,
                    'first_id' => $first?->id ?? PHP_INT_MAX,
                ];
            })
            ->sort(fn ($a, $b) => [$a->exp_date ?? '9999-12-31', $a->first_id] <=> [$b->exp_date ?? '9999-12-31', $b->first_id])
            ->values();

        return $onlyPositive ? $lots->filter(fn ($l) => $l->balance > 0.0001)->values() : $lots;
    }

    public static function lotBalance(string $type, int $id, ?string $batch): float
    {
        return (float) static::lotBalances($type, $id, false)->firstWhere('batch', (string) $batch)?->balance;
    }

    public static function balance(string $type, int $id): float
    {
        $in = (float) static::where('material_type', $type)->where('material_id', $id)->where('direction', 'in')->sum('quantity');
        $out = (float) static::where('material_type', $type)->where('material_id', $id)->where('direction', 'out')->sum('quantity');

        return $in - $out;
    }
}
