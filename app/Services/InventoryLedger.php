<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductionFinishedBatch;
use App\Models\SupplierBatch;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryLedger
{
    public function post(
        SupplierBatch|ProductionFinishedBatch $batch,
        string $movementType,
        string $direction,
        float $quantity,
        string $unit,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdBy = null,
        ?string $notes = null,
    ): InventoryMovement {
        if (!in_array($direction, ['in', 'out'], true) || $quantity <= 0) {
            throw new DomainException('Giao dịch kho cần chiều hợp lệ và số lượng lớn hơn 0.');
        }

        return DB::transaction(function () use ($batch, $movementType, $direction, $quantity, $unit, $referenceType, $referenceId, $createdBy, $notes) {
            $lockedBatch = $batch::query()->whereKey($batch->getKey())->lockForUpdate()->firstOrFail();
            if ($direction === 'out' && (float) $lockedBatch->current_quantity + 0.0001 < $quantity) {
                throw new DomainException("Tồn lô không đủ để ghi giao dịch {$movementType}.");
            }

            $direction === 'out'
                ? $lockedBatch->decrement('current_quantity', $quantity)
                : $lockedBatch->increment('current_quantity', $quantity);

            $batchColumn = match (true) {
                $lockedBatch instanceof SupplierBatch => 'supplier_batch_id',
                $lockedBatch instanceof ProductionFinishedBatch => 'production_finished_batch_id',
            };

            return InventoryMovement::create([
                'product_id' => $lockedBatch->product_id,
                $batchColumn => $lockedBatch->id,
                'movement_type' => $movementType,
                'direction' => $direction,
                'quantity' => $quantity,
                'unit' => $unit,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $createdBy,
                'notes' => $notes,
            ]);
        });
    }

    public function reconcile(): Collection
    {
        $definitions = [
            [SupplierBatch::class, 'supplier_batch_id', 'batch_number'],
            [ProductionFinishedBatch::class, 'production_finished_batch_id', 'batch_number'],
        ];
        $mismatches = collect();

        foreach ($definitions as [$model, $batchColumn, $codeColumn]) {
            $ledgerBalances = InventoryMovement::query()
                ->whereNotNull($batchColumn)
                ->selectRaw("{$batchColumn} as batch_id, SUM(CASE WHEN direction = 'in' THEN quantity WHEN direction = 'out' THEN -quantity ELSE 0 END) as ledger_quantity")
                ->groupBy($batchColumn)
                ->get()
                ->keyBy('batch_id');

            foreach ($model::with('product')->get() as $batch) {
                $ledgerQuantity = (float) ($ledgerBalances->get($batch->id)->ledger_quantity ?? 0);
                $currentQuantity = (float) $batch->current_quantity;
                if (abs($currentQuantity - $ledgerQuantity) > 0.0001) {
                    $mismatches->push([
                        'batch_type' => class_basename($model),
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->{$codeColumn},
                        'product' => $batch->product->name ?? '---',
                        'current_quantity' => $currentQuantity,
                        'ledger_quantity' => $ledgerQuantity,
                        'difference' => round($currentQuantity - $ledgerQuantity, 4),
                    ]);
                }
            }
        }

        return $mismatches;
    }
}