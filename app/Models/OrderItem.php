<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'raw_material_id',
        'quantity',
        'actual_quantity',
        'packed_quantity',
        'warehouse_stock_available_quantity',
        'warehouse_stock_confirmed_quantity',
        'packaging_spec',
        'finished_quantity',
        'ppcb_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'finished_quantity' => 'decimal:2',
        'actual_quantity' => 'decimal:2',
        'packed_quantity' => 'decimal:2',
        'warehouse_stock_available_quantity' => 'decimal:4',
        'warehouse_stock_confirmed_quantity' => 'decimal:4',
    ];

    // Quan hệ ngược về đơn hàng tổng
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Quan hệ tới vị thuốc/sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function getCatalogItemAttribute()
    {
        return $this->raw_material_id ? $this->rawMaterial : $this->product;
    }

    public function ppcb()
    {
        return $this->belongsTo(Ppcb::class, 'ppcb_id');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function lotAllocations()
    {
        return $this->hasMany(SalesOrderLotAllocation::class);
    }

    public function labelPrints()
    {
        return $this->hasMany(SalesOrderLabelPrint::class);
    }

    public function getLabelTargetCountAttribute(): int
    {
        if ((float) $this->finished_quantity > 0) {
            return (int) round((float) $this->finished_quantity);
        }

        preg_match('/[\d.,]+/', (string) $this->packaging_spec, $matches);
        $packageSize = isset($matches[0]) ? (float) str_replace(',', '.', $matches[0]) : 0.0;

        return $packageSize > 0
            ? (int) ceil(((float) $this->quantity / $packageSize) - 0.000001)
            : 0;
    }

    public function distributeLabelTarget(Collection $allocations): array
    {
        $target = $this->label_target_count;
        $totalQuantity = $allocations->sum(fn ($allocation) => max(0, (float) $allocation->reserved_quantity));
        if ($target <= 0 || $totalQuantity <= 0) {
            return $allocations->mapWithKeys(fn ($allocation) => [$allocation->id => 0])->all();
        }

        $shares = $allocations->map(function ($allocation) use ($target, $totalQuantity) {
            $exact = $target * max(0, (float) $allocation->reserved_quantity) / $totalQuantity;

            return ['id' => $allocation->id, 'count' => (int) floor($exact), 'remainder' => $exact - floor($exact)];
        });
        $remaining = $target - $shares->sum('count');
        foreach ($shares->sortByDesc('remainder')->take($remaining) as $share) {
            $shares = $shares->map(fn ($candidate) => $candidate['id'] === $share['id']
                ? [...$candidate, 'count' => $candidate['count'] + 1]
                : $candidate);
        }

        return $shares->mapWithKeys(fn ($share) => [$share['id'] => $share['count']])->all();
    }
}