<?php

namespace App\Http\Controllers;

use App\Models\MaterialLot;
use App\Models\MaterialStockMovement;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionOrder;
use App\Models\SupplierBatch;
use Illuminate\Http\Request;

class TraceabilityController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $traceBatch = null;
        $traceabilityType = null;
        $productionOrders = collect();
        $finishedBatches = collect();
        $materialInputs = collect();
        $salesAllocations = collect();
        $materialLotMap = collect();
        $materialLotBalance = null;

        if ($search !== '') {
            $traceBatch = SupplierBatch::with([
                'product',
                'goodsReceiptItem.goodsReceipt.purchaseOrder.supplier',
                'goodsReceiptItem.purchaseOrderItem.purchaseOrder',
                'salesOrderAllocations.orderItem.product',
                'salesOrderAllocations.orderItem.order.customer',
            ])
                ->where('batch_number', 'like', "%{$search}%")
                ->first();

            if ($traceBatch) {
                $traceabilityType = 'supplier';
                $productionOrders = ProductionOrder::with([
                    'product',
                    'order.customer',
                    'materials.product',
                    'materials.lots.supplierBatch',
                    'finishedBatches.inputs.materialLot.supplierBatch.product',
                    'finishedBatches.inputs.materialLot.supplierBatch.goodsReceiptItem.goodsReceipt.purchaseOrder.supplier',
                    'finishedBatches.inputs.materialLot.supplierBatch.goodsReceiptItem.purchaseOrderItem.purchaseOrder',
                    'finishedBatches.inputs.materialLot.material.product',
                    'finishedBatches.salesOrderAllocations.orderItem.order.customer',
                ])->whereHas('materials.lots', fn ($query) => $query->where('supplier_batch_id', $traceBatch->id))->get();
                $finishedBatches = $productionOrders->flatMap(fn ($order) => $order->finishedBatches)->unique('id')->values();
                $materialInputs = $finishedBatches->flatMap(fn ($batch) => $batch->inputs)->unique('id')->values();
                $salesAllocations = $traceBatch->salesOrderAllocations
                    ->merge($finishedBatches->flatMap(fn ($batch) => $batch->salesOrderAllocations))
                    ->unique('id')
                    ->values();
            } elseif ($traceBatch = MaterialLot::with('purchaseOrderItem.purchaseOrder.supplier')
                ->whereNotNull('batch_number')
                ->where('batch_number', 'like', "%{$search}%")
                ->first()) {
                $traceabilityType = 'material';
                $materialLotBalance = MaterialStockMovement::lotBalance($traceBatch->material_type, $traceBatch->material_id, $traceBatch->batch_number);
                $productionOrders = ProductionOrder::with([
                    'product',
                    'order.customer',
                    'finishedBatches.inputs.materialLot.material',
                    'finishedBatches.salesOrderAllocations.orderItem.order.customer',
                ])->whereHas('materials', fn ($material) => $material
                    ->where('material_type', $traceBatch->material_type)
                    ->where('material_id', $traceBatch->material_id)
                    ->whereHas('lots', fn ($lot) => $lot->where('batch_number', $traceBatch->batch_number)))->get();
                $finishedBatches = $productionOrders->flatMap(fn ($order) => $order->finishedBatches)->unique('id')->values();
                $salesAllocations = $finishedBatches->flatMap(fn ($batch) => $batch->salesOrderAllocations)->unique('id')->values();
            } else {
                $traceBatch = ProductionFinishedBatch::with([
                    'product',
                    'codeHistories.changedBy',
                    'productionOrder.product',
                    'productionOrder.order.customer',
                    'inputs.materialLot.supplierBatch.product',
                    'inputs.materialLot.supplierBatch.goodsReceiptItem.goodsReceipt.purchaseOrder.supplier',
                    'inputs.materialLot.supplierBatch.goodsReceiptItem.purchaseOrderItem.purchaseOrder',
                    'inputs.materialLot.material.product',
                    'salesOrderAllocations.orderItem.product',
                    'salesOrderAllocations.orderItem.order.customer',
                ])
                    ->where(function ($query) use ($search) {
                        $query->where('batch_number', 'like', "%{$search}%")
                            ->orWhere('provisional_batch_number', 'like', "%{$search}%")
                            ->orWhereHas('codeHistories', function ($history) use ($search) {
                                $history->where('old_batch_number', 'like', "%{$search}%")
                                    ->orWhere('new_batch_number', 'like', "%{$search}%");
                            });
                    })
                    ->first();

                if ($traceBatch) {
                    $traceabilityType = 'finished';
                    $productionOrders = collect([$traceBatch->productionOrder])->filter()->values();
                    $finishedBatches = collect([$traceBatch]);
                    $materialInputs = $traceBatch->inputs;
                    $salesAllocations = $traceBatch->salesOrderAllocations;
                }
            }
        }

        foreach ($materialInputs as $input) {
            $lot = $input->materialLot;
            $material = $lot?->material;
            if ($lot && $lot->batch_number && $material && $material->material_id) {
                $key = $material->material_type . '|' . $material->material_id . '|' . $lot->batch_number;
                if (! $materialLotMap->has($key)) {
                    $materialLotMap[$key] = MaterialLot::with('purchaseOrderItem.purchaseOrder.supplier')
                        ->where('material_type', $material->material_type)
                        ->where('material_id', $material->material_id)
                        ->where('batch_number', $lot->batch_number)
                        ->first();
                }
            }
        }

        return view('traceability.index', compact(
            'search',
            'traceBatch',
            'traceabilityType',
            'productionOrders',
            'finishedBatches',
            'materialInputs',
            'salesAllocations',
            'materialLotMap',
            'materialLotBalance'
        ));
    }
}
