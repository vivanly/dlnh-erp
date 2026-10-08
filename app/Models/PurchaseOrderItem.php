<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'material_type',
        'material_id',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
    ];

    // Quan hệ ngược lại với Đơn mua hàng
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // Quan hệ với Vị thuốc / Dược liệu
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'material_id');
    }

    public function accessory()
    {
        return $this->belongsTo(Accessory::class, 'material_id');
    }

    public static function catalogExists(string $type, int $id): bool
    {
        return match ($type) {
            'product' => Product::whereKey($id)->exists(),
            'raw_material' => RawMaterial::whereKey($id)->exists(),
            'accessory' => Accessory::whereKey($id)->exists(),
            default => false,
        };
    }

    public static function catalogColumns(string $type, int $id): array
    {
        return $type === 'product'
            ? ['product_id' => $id, 'material_type' => null, 'material_id' => null]
            : ['product_id' => null, 'material_type' => $type, 'material_id' => $id];
    }

    // Hàng hóa của dòng đơn mua: sản phẩm, nguyên liệu thô hoặc phụ liệu
    public function getCatalogItemAttribute()
    {
        return match ($this->material_type) {
            'raw_material' => $this->rawMaterial,
            'accessory' => $this->accessory,
            default => $this->product,
        };
    }

    public function getItemTypeAttribute(): string
    {
        return $this->material_type ?: 'product';
    }

    public function getItemTypeLabelAttribute(): string
    {
        return match ($this->item_type) {
            'raw_material' => 'Nguyên liệu thô',
            'accessory' => 'Phụ liệu',
            default => 'Dược liệu / Sản phẩm',
        };
    }

    // Lượng đã xử lý (đạt + trả) của dòng hàng, kể cả nguyên liệu thô/phụ liệu
    public function processedQuantity(): float
    {
        if ($this->material_type) {
            return (float) MaterialStockMovement::where('purchase_order_item_id', $this->id)
                ->whereIn('movement_type', ['RECEIVE_PURCHASE', 'REJECT_PURCHASE'])
                ->sum('quantity')
                + (float) MaterialLot::where('purchase_order_item_id', $this->id)->where('status', 'pending_qa')->sum('quantity');
        }

        return (float) GoodsReceiptItem::where('purchase_order_item_id', $this->id)
            ->selectRaw('COALESCE(SUM(received_quantity + returned_quantity), 0) as q')
            ->value('q');
    }

    public function materialMovements()
    {
        return $this->hasMany(MaterialStockMovement::class, 'purchase_order_item_id')
            ->whereIn('movement_type', ['RECEIVE_PURCHASE', 'REJECT_PURCHASE']);
    }

    public function goodsReceiptItems()
    {
        return $this->hasMany(GoodsReceiptItem::class, 'purchase_order_item_id');
    }
}