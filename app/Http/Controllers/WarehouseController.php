<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMaterialLot;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Services\InventoryLedger;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    private function ensureWarehouseAccess(): void
    {
        abort_unless($this->canManageWarehouse(), 403);
    }

    private function canManageWarehouse(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isWarehouseDepartment') && $user->isWarehouseDepartment()) ||
            (method_exists($user, 'isWarehouseManager') && $user->isWarehouseManager()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    private function ensureWarehouseViewAccess(): void
    {
        abort_unless(auth()->check(), 403);
    }
    private function orderHasAssignedLots(Order $order): bool
    {
        return $order->items()
            ->whereHas('lotAllocations', fn ($query) => $query
                ->where('status', 'reserved')
                ->whereNotNull('production_finished_batch_id'))
            ->exists();
    }

    public function purchaseOrders(Request $request)
    {
        $this->ensureWarehouseViewAccess();

        $purchaseOrders = PurchaseOrder::with([
            'supplier',
            'user',
            'items.product',
            'items.goodsReceiptItems.goodsReceipt.receiver',
        ])
            ->whereIn('status', ['approved', 'delivered'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('goodsReceipts', function ($receiptQuery) use ($search) {
                            $receiptQuery->where('receipt_code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $canManageWarehouse = $this->canManageWarehouse();

        return view('warehouse.purchase-orders', compact('purchaseOrders', 'canManageWarehouse'));
    }

    public function markPurchaseOrderDelivered(PurchaseOrder $purchaseOrder)
    {
        $this->ensureWarehouseAccess();

        if ($purchaseOrder->status !== 'approved') {
            return back()->with('error', 'Chỉ đơn đã duyệt và đang chờ giao mới có thể xác nhận hàng đến.');
        }

        $purchaseOrder->update([
            'status' => 'delivered',
            'warehouse_confirmed_at' => now(),
        ]);

        return back()->with('success', 'Đã xác nhận hàng đến kho. Có thể lập phiếu nhập kho cho đơn này.');
    }

    public function salesOrderStockChecks(Request $request)
    {
        $this->ensureWarehouseViewAccess();

        $orders = Order::with(['customer', 'items.product'])
            ->where('status', 'pending_warehouse_check')
            ->whereNotNull('sales_approved_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items.product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order) {
            foreach ($order->items as $item) {
                $lots = $this->availableSalesLots($order, $item);
                $item->setAttribute('stock_check_lots', $lots);
                $item->setAttribute('stock_check_available', collect($lots)->sum('available_quantity'));
            }
        });

        $canManageWarehouse = $this->canManageWarehouse();

        return view('warehouse.sales-order-stock-checks', compact('orders', 'canManageWarehouse'));
    }

    public function confirmSalesOrderStockCheck(Request $request, Order $order)
    {
        $this->ensureWarehouseAccess();
        $validated = $request->validate([
            'confirmed_quantities' => ['required', 'array'],
            'confirmed_quantities.*' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_warehouse_check' || ! $lockedOrder->sales_approved_at) {
                    throw new DomainException('Đơn hàng chưa được Giám đốc Kinh doanh duyệt hoặc không còn chờ Kho xác nhận tồn.');
                }

                $items = $lockedOrder->items()->get();
                $submittedItemIds = array_map('intval', array_keys($validated['confirmed_quantities']));
                $expectedItemIds = $items->modelKeys();
                sort($submittedItemIds);
                sort($expectedItemIds);
                if ($submittedItemIds !== $expectedItemIds) {
                    throw new DomainException('Vui lòng xác nhận số lượng cho tất cả sản phẩm trong đơn.');
                }

                foreach ($items as $item) {
                    $lots = $this->availableSalesLots($lockedOrder, $item);
                    $item->update([
                        'warehouse_stock_available_quantity' => collect($lots)->sum('available_quantity'),
                        'warehouse_stock_confirmed_quantity' => $validated['confirmed_quantities'][$item->id],
                    ]);
                }

                $lockedOrder->update([
                    'status' => 'pending_qa',
                    'warehouse_stock_checked_at' => now(),
                    'warehouse_stock_checked_by' => auth()->id(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('warehouse.sales-order-stock-checks')->with('success', 'Đã xác nhận kiểm tồn; đơn được chuyển sang QA chốt lô. Tồn kho chưa bị giữ hoặc trừ.');
    }

    private function availableSalesLots(Order $order, OrderItem $item): array
    {
        $definitions = $order->order_type === 'DL'
            ? [
                [SupplierBatch::class, 'supplier_batch_id', 'batch_number', 'Lô NCC'],
                [ProductionFinishedBatch::class, 'production_finished_batch_id', 'batch_number', 'Lô nội bộ'],
            ]
            : [
                [ProductionFinishedBatch::class, 'production_finished_batch_id', 'batch_number', 'Lô nội bộ'],
            ];
        $rows = [];

        foreach ($definitions as [$model, $allocationColumn, $codeColumn, $typeLabel]) {
            $batches = $model::query()
                ->where('product_id', $item->product_id)
                ->where('status', 'active')
                ->where('current_quantity', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('exp_date')->orWhereDate('exp_date', '>=', today());
                })
                ->orderBy('exp_date')
                ->get();

            foreach ($batches as $batch) {
                $salesReservations = (float) SalesOrderLotAllocation::query()
                    ->where($allocationColumn, $batch->id)
                    ->where('status', 'reserved')
                    ->selectRaw('COALESCE(SUM(reserved_quantity - shipped_quantity), 0) as reserved_quantity')
                    ->value('reserved_quantity');
                $productionReservations = $allocationColumn === 'supplier_batch_id'
                    ? (float) ProductionMaterialLot::query()
                        ->where($allocationColumn, $batch->id)
                        ->whereHas('material.productionOrder', fn ($query) => $query->whereIn('status', ['planned', 'materials_assigned', 'released']))
                        ->selectRaw('COALESCE(SUM(allocated_quantity - issued_quantity), 0) as reserved_quantity')
                        ->value('reserved_quantity')
                    : 0.0;
                $available = max(0, (float) $batch->current_quantity - $salesReservations - $productionReservations);
                if ($available <= 0) {
                    continue;
                }

                $rows[] = [
                    'type' => $typeLabel,
                    'code' => $batch->{$codeColumn},
                    'exp_date' => $batch->exp_date,
                    'current_quantity' => (float) $batch->current_quantity,
                    'available_quantity' => $available,
                ];
            }
        }

        return $rows;
    }

    public function salesOrders(Request $request)
    {
        $this->ensureWarehouseViewAccess();

        $orders = Order::with(['customer', 'items.product', 'items.lotAllocations.supplierBatch', 'items.lotAllocations.finishedBatch'])
            ->where('status', 'ready_to_ship')
            ->whereNotNull('qa_confirmed_at')
            ->whereNull('warehouse_confirmed_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items.lotAllocations.supplierBatch', function ($lotQuery) use ($search) {
                            $lotQuery->where('batch_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('items.lotAllocations.finishedBatch', function ($lotQuery) use ($search) {
                            $lotQuery->where('batch_number', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order) {
            $order->setAttribute(
                'waiting_production_packaging',
                ! $order->production_packaged_at && $order->items->contains(fn (OrderItem $item) => $item->lotAllocations->where('status', 'reserved')->isNotEmpty()),
            );
        });

        $canManageWarehouse = $this->canManageWarehouse();

        return view('warehouse.sales-orders', compact('orders', 'canManageWarehouse'));
    }

    public function deliveredSalesOrders(Request $request)
    {
        $this->ensureWarehouseViewAccess();

        $orders = Order::with([
            'customer',
            'items.product',
            'items.lotAllocations.supplierBatch',
            'items.lotAllocations.finishedBatch',
        ])
            ->where('status', 'completed')
            ->whereNotNull('warehouse_confirmed_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items.product', fn ($product) => $product->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('items.lotAllocations.supplierBatch', fn ($batch) => $batch->where('batch_number', 'like', "%{$search}%"))
                        ->orWhereHas('items.lotAllocations.finishedBatch', fn ($batch) => $batch->where('batch_number', 'like', "%{$search}%"));
                });
            })
            ->latest('warehouse_confirmed_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $movements = InventoryMovement::query()
            ->where('reference_type', Order::class)
            ->whereIn('reference_id', $orders->getCollection()->modelKeys())
            ->where('movement_type', 'SALE_SHIPMENT')
            ->where('direction', 'out')
            ->get(['reference_id', 'product_id', 'supplier_batch_id', 'production_finished_batch_id', 'quantity', 'unit']);

        $orders->getCollection()->each(function (Order $order) use ($movements) {
            $orderMovements = $movements->where('reference_id', $order->id);
            foreach ($order->items as $item) {
                $supplierBatchIds = $item->lotAllocations->pluck('supplier_batch_id')->filter()->map(fn ($id) => (int) $id)->all();
                $finishedBatchIds = $item->lotAllocations->pluck('production_finished_batch_id')->filter()->map(fn ($id) => (int) $id)->all();
                $deductedQuantity = $orderMovements
                    ->filter(fn ($movement) => (int) $movement->product_id === (int) $item->product_id
                        && $movement->unit === ($item->product->unit ?? '')
                        && (
                            in_array((int) $movement->supplier_batch_id, $supplierBatchIds, true)
                            || in_array((int) $movement->production_finished_batch_id, $finishedBatchIds, true)
                        ))
                    ->sum('quantity');
                $item->setAttribute('ledger_deducted_quantity', $deductedQuantity);
                $item->setAttribute('stock_deduction_matches', abs($deductedQuantity - (float) $item->actual_quantity) <= 0.0001);
            }

            $hasMissingDeductions = $order->items->contains(fn (OrderItem $item) => ! $item->stock_deduction_matches);
            $order->setAttribute('has_missing_stock_deductions', $hasMissingDeductions || $orderMovements->isEmpty());
        });

        return view('warehouse.delivered-sales-orders', compact('orders'));
    }

    public function productionBatchReceipts(Request $request)
    {
        $this->ensureWarehouseViewAccess();

        $batches = ProductionFinishedBatch::with(['product', 'productionOrder.order', 'productionOrder.monthlyPlanLine.plan'])
            ->withCount(['salesOrderAllocations', 'inputs', 'inventoryMovements'])
            ->where('pending_warehouse_quantity', '>', 0)
            ->whereIn('status', ['pending_qa', 'active'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('batch_number', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('productionOrder', fn ($orderQuery) => $orderQuery->where('production_code', 'like', "%{$search}%"));
                });
            })
            ->oldest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $monthlyProductionOrders = ProductionOrder::with(['monthlyPlanLine.plan'])
            ->where('status', 'completed')
            ->where('pending_finished_quantity', '>', 0)
            ->whereHas('monthlyPlanLine')
            ->orderByDesc('completed_at')
            ->get();

        $canManageWarehouse = $this->canManageWarehouse();

        return view('warehouse.production-batch-receipts', compact('batches', 'canManageWarehouse', 'monthlyProductionOrders'));
    }

    public function receiveProductionBatch(ProductionFinishedBatch $productionFinishedBatch, InventoryLedger $inventoryLedger)
    {
        $this->ensureWarehouseAccess();
        $validated = request()->validate([
            'received_quantity' => ['required', 'numeric', 'gt:0'],
            'production_order_id' => ['nullable', 'integer', 'exists:production_orders,id'],
        ]);

        try {
            DB::transaction(function () use ($productionFinishedBatch, $inventoryLedger, $validated) {
                $batch = ProductionFinishedBatch::whereKey($productionFinishedBatch->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $quantity = (float) $validated['received_quantity'];
                $pendingQuantity = (float) $batch->pending_warehouse_quantity;
                if (! in_array($batch->status, ['pending_qa', 'active'], true) || $pendingQuantity <= 0) {
                    throw new DomainException('Lô không còn số lượng chờ Kho nhập.');
                }
                if ($quantity > $pendingQuantity + 0.000001) {
                    throw new DomainException('Số lượng nhập thực tế vượt lượng lô còn chờ nhập.');
                }
                $productionOrder = null;
                if ($batch->production_order_id) {
                    $linkedOrder = ProductionOrder::whereKey($batch->production_order_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    if ($linkedOrder->monthlyPlanLine()->exists()) {
                        $productionOrder = $linkedOrder;
                    }
                }
                if (! empty($validated['production_order_id'])) {
                    $selectedOrder = ProductionOrder::whereKey($validated['production_order_id'])
                        ->lockForUpdate()
                        ->firstOrFail();
                    if (
                        $selectedOrder->status !== 'completed'
                        || ! $selectedOrder->monthlyPlanLine()->exists()
                        || (int) $selectedOrder->product_id !== (int) $batch->product_id
                        || (float) $selectedOrder->pending_finished_quantity <= 0
                    ) {
                        throw new DomainException('Chỉ liên kết lô với lệnh kế hoạch tháng đã hoàn thành, còn sản lượng và đúng sản phẩm.');
                    }
                    if ($productionOrder && (int) $productionOrder->id !== (int) $selectedOrder->id) {
                        throw new DomainException('Lô thành phẩm đã được liên kết với một lệnh kế hoạch tháng khác.');
                    }
                    if (
                        ! $productionOrder
                        && (
                            $batch->production_order_id !== null
                            || (float) $batch->initial_quantity > 0.000001
                            || $batch->warehouse_received_at !== null
                            || $batch->salesOrderAllocations()->exists()
                            || $batch->inputs()->exists()
                            || $batch->inventoryMovements()->exists()
                        )
                    ) {
                        throw new DomainException('Chỉ liên kết kế hoạch tháng với lô QA chưa nhập kho hoặc phát sinh giao dịch.');
                    }
                    $productionOrder = $selectedOrder;
                }
                if ($productionOrder) {
                    $remainingProductionQuantity = max(
                        0,
                        (float) $productionOrder->pending_finished_quantity - $quantity,
                    );
                    if ((float) $productionOrder->actual_quantity <= 0) {
                        throw new DomainException('Lệnh kế hoạch tháng chưa có sản lượng thực tế để nhập thành phẩm.');
                    }
                    if ($productionOrder->status !== 'completed' || (int) $productionOrder->product_id !== (int) $batch->product_id) {
                        throw new DomainException('Lệnh kế hoạch tháng không còn phù hợp với sản phẩm của lô.');
                    }
                    if ($quantity > (float) $productionOrder->pending_finished_quantity + 0.000001) {
                        throw new DomainException('Số lượng nhập vượt sản lượng thực tế còn lại của lệnh kế hoạch tháng.');
                    }
                    $productionOrder->load('materials.lots.finishedBatchInputs');
                }

                $plannedCapacity = (float) $batch->planned_quantity;
                $isMonthlyPlanBatch = $productionOrder !== null;
                $capacityUsage = $batch->production_order_id && ! $isMonthlyPlanBatch
                    ? (float) $batch->initial_quantity
                    : (float) $batch->initial_quantity + $quantity;
                if ($plannedCapacity > 0 && $capacityUsage > $plannedCapacity + 0.000001) {
                    throw new DomainException("Số lượng nhập vượt lượng dự kiến của lô {$batch->batch_number} ({$plannedCapacity} {$batch->unit}).");
                }

                $inventoryLedger->post(
                    $batch,
                    'RECEIVE_PRODUCTION',
                    'in',
                    $quantity,
                    $batch->unit,
                    ProductionFinishedBatch::class,
                    $batch->id,
                    auth()->id(),
                );
                if ($productionOrder) {
                    $isFinalReceipt = $quantity >= (float) $productionOrder->pending_finished_quantity - 0.000001;
                    foreach ($productionOrder->materials as $material) {
                        foreach ($material->lots as $materialLot) {
                            $consumedQuantity = max(0, (float) $materialLot->issued_quantity - (float) $materialLot->returned_quantity);
                            $alreadyAssigned = (float) $materialLot->finishedBatchInputs->sum('consumed_quantity');
                            $remainingConsumed = max(0, $consumedQuantity - $alreadyAssigned);
                            $inputQuantity = $isFinalReceipt
                                ? $remainingConsumed
                                : min(
                                    $remainingConsumed,
                                    round($consumedQuantity * $quantity / (float) $productionOrder->actual_quantity, 4),
                                );
                            if ($inputQuantity <= 0) {
                                continue;
                            }

                            $existingInput = $batch->inputs()
                                ->where('production_material_lot_id', $materialLot->id)
                                ->lockForUpdate()
                                ->first();
                            if ($existingInput) {
                                $existingInput->update([
                                    'consumed_quantity' => (float) $existingInput->consumed_quantity + $inputQuantity,
                                ]);
                            } else {
                                $batch->inputs()->create([
                                    'production_material_lot_id' => $materialLot->id,
                                    'consumed_quantity' => $inputQuantity,
                                    'unit' => $material->unit,
                                ]);
                            }
                        }
                    }
                    $productionOrder->update([
                        'pending_finished_quantity' => $remainingProductionQuantity,
                    ]);
                } else {
                    $remainingProductionQuantity = null;
                }

                $batch->update([
                    'production_order_id' => $productionOrder?->id ?? $batch->production_order_id,
                    'initial_quantity' => $batch->production_order_id && ! $isMonthlyPlanBatch
                        ? $batch->initial_quantity
                        : (float) $batch->initial_quantity + $quantity,
                    'pending_warehouse_quantity' => $isMonthlyPlanBatch
                        ? min(max(0, $pendingQuantity - $quantity), $remainingProductionQuantity)
                        : max(0, $pendingQuantity - $quantity),
                    'status' => 'active',
                    'warehouse_received_at' => $batch->warehouse_received_at ?? now(),
                    'warehouse_received_by' => $batch->warehouse_received_by ?? auth()->id(),
                ]);

                $this->releaseOrdersWaitingForFinishedGoodsReceipt($batch);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã ghi nhận số lượng thực nhập; phần còn lại tiếp tục chờ Kho nhập. PKN chỉ bắt buộc trước khi xác nhận xuất hàng.');
    }

    private function releaseOrdersWaitingForFinishedGoodsReceipt(ProductionFinishedBatch $receivedBatch): void
    {
        $orderItemIds = SalesOrderLotAllocation::query()
            ->where('production_finished_batch_id', $receivedBatch->id)
            ->where('status', 'reserved')
            ->pluck('order_item_id');
        $orderIds = OrderItem::whereIn('id', $orderItemIds)->pluck('order_id')->unique()->sort();

        foreach ($orderIds as $orderId) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();
            if (! $order || $order->status !== 'waiting_finished_goods_receipt') {
                continue;
            }

            $order->load(['items.lotAllocations.finishedBatch', 'items.lotAllocations.supplierBatch']);
            $allItemsReady = $order->items->isNotEmpty() && $order->items->every(function (OrderItem $item) {
                $allocations = $item->lotAllocations->where('status', 'reserved');
                $allocatedQuantity = $allocations->sum(fn ($allocation) => max(
                    0,
                    (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity,
                ));
                if ($allocatedQuantity + 0.0001 < (float) $item->quantity) {
                    return false;
                }

                foreach ($allocations as $allocation) {
                    if ((float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity <= 0.0001) {
                        continue;
                    }

                    $batch = $allocation->finishedBatch ?? $allocation->supplierBatch;
                    if (! $batch || $batch->status !== 'active') {
                        return false;
                    }
                    $allocationColumn = $allocation->finishedBatch
                        ? 'production_finished_batch_id'
                        : 'supplier_batch_id';
                    $reservedQuantity = (float) SalesOrderLotAllocation::query()
                        ->where($allocationColumn, $batch->id)
                        ->where('status', 'reserved')
                        ->selectRaw('COALESCE(SUM(reserved_quantity - shipped_quantity), 0) as reserved_quantity')
                        ->value('reserved_quantity');
                    if ((float) $batch->current_quantity + 0.0001 < $reservedQuantity) {
                        return false;
                    }
                }

                return true;
            });

            if ($allItemsReady) {
                $order->update(['status' => 'ready_to_ship']);
            }
        }
    }

    public function materialStock(Request $request)
    {
        $this->ensureWarehouseViewAccess();

        $type = $request->input('type');
        $search = trim((string) $request->input('search'));

        $rows = collect(['raw_material' => \App\Models\RawMaterial::class, 'accessory' => \App\Models\Accessory::class])
            ->when($type, fn ($c) => $c->only([$type]))
            ->flatMap(function ($model, $materialType) use ($search) {
                return $model::query()
                    ->when($search !== '', fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")))
                    ->orderBy('name')
                    ->get()
                    ->map(function ($item) use ($materialType) {
                        $in = (float) \App\Models\MaterialStockMovement::where('material_type', $materialType)->where('material_id', $item->id)->where('direction', 'in')->sum('quantity');
                        $out = (float) \App\Models\MaterialStockMovement::where('material_type', $materialType)->where('material_id', $item->id)->where('direction', 'out')->sum('quantity');

                        $lots = \App\Models\MaterialStockMovement::lotBalances($materialType, $item->id, false);

                        return (object) ['type' => $materialType, 'item' => $item, 'in' => $in, 'out' => $out, 'balance' => $in - $out, 'lots' => $lots];
                    });
            })
            ->values();

        $movements = \App\Models\MaterialStockMovement::latest()->limit(20)->get();

        return view('warehouse.material-stock', compact('rows', 'movements', 'type', 'search'));
    }

    public function materialIssues()
    {
        $this->ensureWarehouseViewAccess();

        $orders = Order::with(['customer', 'items.rawMaterial'])
            ->where('order_type', 'NL')
            ->where('status', 'pending_material_issue')
            ->latest()
            ->paginate(20);

        $orders->getCollection()->each(function (Order $order) {
            foreach ($order->items as $item) {
                $item->setAttribute('stock_balance', \App\Models\MaterialStockMovement::balance('raw_material', (int) $item->raw_material_id));
            }
        });

        $canManageWarehouse = $this->canManageWarehouse();

        return view('warehouse.material-issues', compact('orders', 'canManageWarehouse'));
    }

    public function issueMaterialOrder(Order $order)
    {
        $this->ensureWarehouseAccess();

        try {
            DB::transaction(function () use ($order) {
                $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($locked->order_type !== 'NL' || $locked->status !== 'pending_material_issue') {
                    throw new DomainException('Đơn không còn chờ xuất nguyên liệu thô.');
                }

                foreach ($locked->items()->with('rawMaterial')->get() as $item) {
                    $balance = \App\Models\MaterialStockMovement::balance('raw_material', (int) $item->raw_material_id);
                    if ((float) $item->quantity > $balance + 0.0001) {
                        throw new DomainException('Không đủ tồn kho cho ' . ($item->rawMaterial->name ?? 'nguyên liệu') . " (cần {$item->quantity}, tồn {$balance}).");
                    }
                }

                foreach ($locked->items as $item) {
                    $needed = (float) $item->quantity;
                    foreach (\App\Models\MaterialStockMovement::lotBalances('raw_material', (int) $item->raw_material_id) as $lot) {
                        if ($needed <= 0.0001) {
                            break;
                        }
                        $take = min($needed, $lot->balance);
                        \App\Models\MaterialStockMovement::create([
                            'material_type' => 'raw_material',
                            'material_id' => $item->raw_material_id,
                            'movement_type' => 'ISSUE_SALE',
                            'direction' => 'out',
                            'quantity' => $take,
                            'unit' => $item->rawMaterial->unit ?? null,
                            'batch_number' => $lot->batch !== '' ? $lot->batch : null,
                            'order_item_id' => $item->id,
                            'user_id' => auth()->id(),
                            'note' => 'Xuất bán theo đơn ' . $locked->order_code . ' (FIFO/FEFO)',
                        ]);
                        $needed -= $take;
                    }
                    $item->update(['actual_quantity' => $item->quantity, 'packed_quantity' => $item->quantity]);
                }

                $locked->update(['status' => 'completed']);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('warehouse.material-issues')->with('success', 'Đã xuất kho nguyên liệu thô và hoàn tất đơn.');
    }
    public function stockOverview(Request $request)
    {
        $this->ensureWarehouseViewAccess();

        $filters = $this->stockCheckFilters($request, ['product_name', 'exp_date', 'quantity']);
        $stockRows = $this->getStockCheckRows(...array_values($filters));
        $products = $stockRows->groupBy('product_id')->map(function ($rows) {
            $first = $rows->first();
            $quantityByUnit = $rows->groupBy('product_unit')->map(fn ($unitRows) => $unitRows->sum('quantity'));
            $expirations = $rows->pluck('exp_date')->filter()->sort()->values();

            return [
                'product_id' => $first['product_id'],
                'product_name' => $first['product_name'],
                'product_sku' => $first['product_sku'],
                'quantity_by_unit' => $quantityByUnit,
                'available_quantity_by_unit' => $rows->groupBy('product_unit')->map(fn ($unitRows) => $unitRows->sum('available_quantity')),
                'quantity_sort' => $quantityByUnit->get($first['base_unit'], $quantityByUnit->first() ?? 0),
                'lot_count' => $rows->count(),
                'supplier_lot_count' => $rows->where('lot_type', 'Lô NCC')->count(),
                'internal_lot_count' => $rows->where('lot_type', 'Lô nội bộ')->count(),
                'earliest_exp_date' => $expirations->first(),
            ];
        })->values()->sort(function ($left, $right) use ($filters) {
            if ($filters['sortBy'] === 'exp_date' && ($left['earliest_exp_date'] === null || $right['earliest_exp_date'] === null)) {
                return $left['earliest_exp_date'] === $right['earliest_exp_date'] ? 0 : ($left['earliest_exp_date'] === null ? 1 : -1);
            }

            $comparison = match ($filters['sortBy']) {
                'quantity' => $left['quantity_sort'] <=> $right['quantity_sort'],
                'product_name' => strcasecmp($left['product_name'], $right['product_name']),
                default => strcmp($left['earliest_exp_date'] ?? '', $right['earliest_exp_date'] ?? ''),
            };

            return $filters['sortDirection'] === 'desc' ? -$comparison : $comparison;
        })->values();

        $stockByUnit = $stockRows->groupBy('product_unit')->map(fn ($rows) => $rows->sum('quantity'));
        $availableStockByUnit = $stockRows->groupBy('product_unit')->map(fn ($rows) => $rows->sum('available_quantity'));

        return view('warehouse.stock', [
            'products' => $products,
            'stockRows' => $stockRows,
            'stockByUnit' => $stockByUnit,
            'availableStockByUnit' => $availableStockByUnit,
            ...$filters,
        ]);
    }

    public function showStockProduct(Request $request, Product $product)
    {
        $this->ensureWarehouseViewAccess();

        $filters = $this->stockCheckFilters($request, ['exp_date', 'quantity', 'lot_code']);
        $stockRows = $this->getStockCheckRows(
            $filters['productQuery'],
            $filters['hsdFilter'],
            $filters['lotType'],
            $filters['sortBy'],
            $filters['sortDirection'],
            $product->id
        );
        $stockByUnit = $stockRows->groupBy('product_unit')->map(fn ($rows) => $rows->sum('quantity'));
        $availableStockByUnit = $stockRows->groupBy('product_unit')->map(fn ($rows) => $rows->sum('available_quantity'));

        return view('warehouse.stock-product', compact('product', 'stockRows', 'stockByUnit', 'availableStockByUnit') + $filters);
    }

    private function stockCheckFilters(Request $request, array $allowedSorts): array
    {
        $sortBy = $request->input('sort_by', 'exp_date');

        return [
            'productQuery' => trim((string) $request->input('product', '')),
            'hsdFilter' => in_array($request->input('hsd_status'), ['all', 'expiring', 'expired', 'valid'], true)
                ? $request->input('hsd_status', 'all') : 'all',
            'lotType' => in_array($request->input('lot_type'), ['all', 'supplier', 'internal'], true)
                ? $request->input('lot_type', 'all') : 'all',
            'sortBy' => in_array($sortBy, $allowedSorts, true) ? $sortBy : 'exp_date',
            'sortDirection' => in_array($request->input('sort_direction'), ['asc', 'desc'], true)
                ? $request->input('sort_direction', 'asc') : 'asc',
        ];
    }

    private function getStockCheckRows(
        string $productQuery,
        string $hsdFilter,
        string $lotType,
        string $sortBy,
        string $sortDirection,
        ?int $productId = null
    ) {
        $applyFilters = function ($query) use ($productQuery, $hsdFilter, $productId) {
            $query->where('current_quantity', '>', 0)
                ->when($productId !== null, fn ($query) => $query->where('product_id', $productId))
                ->when($productQuery !== '', function ($query) use ($productQuery) {
                    $query->whereHas('product', function ($product) use ($productQuery) {
                        $product->where('name', 'like', "%{$productQuery}%")
                            ->orWhere('sku', 'like', "%{$productQuery}%");
                    });
                })
                ->when($hsdFilter === 'expiring', function ($query) {
                    $query->whereNotNull('exp_date')->whereDate('exp_date', '>=', today())->whereDate('exp_date', '<=', now()->addDays(30));
                })
                ->when($hsdFilter === 'expired', fn ($query) => $query->whereNotNull('exp_date')->whereDate('exp_date', '<', today()))
                ->when($hsdFilter === 'valid', function ($query) {
                    $query->whereNotNull('exp_date')->whereDate('exp_date', '>', now()->addDays(30));
                });
        };

        $stockRows = collect();
        $reservations = [
            'supplier' => $this->stockReservationQuantities('supplier_batch_id', true),
            'internal' => $this->stockReservationQuantities('production_finished_batch_id', false),
        ];
        if ($lotType === 'all' || $lotType === 'supplier') {
            foreach (SupplierBatch::with('product')->tap($applyFilters)->get() as $batch) {
                $stockRows->push($this->stockCheckRow($batch, 'Lô NCC', $batch->batch_number, $batch->product->unit ?? '---', $reservations['supplier'][$batch->id] ?? 0));
            }
        }
        if ($lotType === 'all' || $lotType === 'internal') {
            foreach (ProductionFinishedBatch::with('product')->tap($applyFilters)->get() as $batch) {
                $stockRows->push($this->stockCheckRow($batch, 'Lô nội bộ', $batch->batch_number, $batch->unit ?: ($batch->product->unit ?? '---'), $reservations['internal'][$batch->id] ?? 0));
            }
        }

        return $stockRows->sort(function ($left, $right) use ($sortBy, $sortDirection) {
            if ($sortBy === 'exp_date' && ($left['exp_date'] === null || $right['exp_date'] === null)) {
                return $left['exp_date'] === $right['exp_date'] ? 0 : ($left['exp_date'] === null ? 1 : -1);
            }

            $comparison = match ($sortBy) {
                'quantity' => $left['quantity'] <=> $right['quantity'],
                'lot_code' => strcasecmp($left['lot_code'], $right['lot_code']),
                'product_name' => strcasecmp($left['product_name'], $right['product_name']),
                default => strcmp($left['exp_date'] ?? '', $right['exp_date'] ?? ''),
            };

            return $sortDirection === 'desc' ? -$comparison : $comparison;
        })->values();
    }

    private function stockReservationQuantities(string $allocationColumn, bool $includeProduction): array
    {
        $salesReservations = SalesOrderLotAllocation::query()
            ->whereNotNull($allocationColumn)
            ->where('status', 'reserved')
            ->selectRaw("{$allocationColumn} as batch_id, COALESCE(SUM(reserved_quantity - shipped_quantity), 0) as quantity")
            ->groupBy($allocationColumn)
            ->pluck('quantity', 'batch_id');

        $productionReservations = collect();
        if ($includeProduction) {
            $productionReservations = ProductionMaterialLot::query()
                ->whereNotNull($allocationColumn)
                ->whereHas('material.productionOrder', fn ($query) => $query->whereIn('status', ['planned', 'materials_assigned', 'released']))
                ->selectRaw("{$allocationColumn} as batch_id, COALESCE(SUM(allocated_quantity - issued_quantity), 0) as quantity")
                ->groupBy($allocationColumn)
                ->pluck('quantity', 'batch_id');
        }

        return $salesReservations->keys()->merge($productionReservations->keys())->unique()
            ->mapWithKeys(fn ($id) => [(int) $id => (float) $salesReservations->get($id, 0) + (float) $productionReservations->get($id, 0)])
            ->all();
    }

    private function stockCheckRow($batch, string $lotType, string $lotCode, string $unit, float $reservedQuantity): array
    {
        $expDate = $batch->exp_date ? date('Y-m-d', strtotime($batch->exp_date)) : null;
        $status = ! $expDate ? 'Chưa có HSD' : ($expDate < today()->toDateString()
            ? 'Hết HSD'
            : ($expDate <= now()->addDays(30)->toDateString() ? 'Sắp hết HSD' : 'Còn HSD'));
        $qaApproved = $batch->status === 'active';
        $isUnexpired = ! $expDate || $expDate >= today()->toDateString();
        $availableQuantity = $qaApproved && $isUnexpired
            ? max(0, (float) $batch->current_quantity - $reservedQuantity)
            : 0.0;

        return [
            'product_id' => $batch->product_id,
            'product_name' => $batch->product->name ?? '---',
            'product_sku' => $batch->product->sku ?? '---',
            'base_unit' => $batch->product->unit ?? $unit,
            'product_unit' => $unit,
            'lot_type' => $lotType,
            'lot_code' => $lotCode,
            'quantity' => (float) $batch->current_quantity,
            'reserved_quantity' => $reservedQuantity,
            'available_quantity' => $availableQuantity,
            'qa_status' => $qaApproved ? 'Đã QA cho phép' : 'Chưa được QA cho phép',
            'initial_quantity' => (float) $batch->initial_quantity,
            'mfg_date' => $batch->mfg_date ? date('Y-m-d', strtotime($batch->mfg_date)) : null,
            'exp_date' => $expDate,
            'status' => $status,
        ];
    }

    public function showSalesOrder(Order $order)
    {
        $this->ensureWarehouseViewAccess();

        if ($order->status !== 'ready_to_ship' || ! $order->qa_confirmed_at || $order->warehouse_confirmed_at) {
            return redirect()->route('warehouse.sales-orders')->with('error', 'Đơn hàng không còn chờ Kho xác nhận.');
        }
        if ($this->orderHasAssignedLots($order) && ! $order->production_packaged_at) {
            return redirect()->route('warehouse.sales-orders')->with('error', 'Sản xuất cần xác nhận đơn đã đóng gói trước khi Kho đóng đơn.');
        }

        $order->load([
            'customer',
            'items.product',
            'items.lotAllocations.supplierBatch',
            'items.lotAllocations.finishedBatch',
        ]);

        $shipmentBlockers = [];
        foreach ($order->items as $item) {
            foreach ($item->lotAllocations->where('status', 'reserved') as $allocation) {
                if ($allocation->finishedBatch && (! $allocation->finishedBatch->qc_test_report_file || $allocation->finishedBatch->qc_result !== 'passed')) {
                    $reason = $allocation->finishedBatch->qc_result === 'failed' ? 'PKN chưa đạt' : 'chưa có PKN đạt';
                    $shipmentBlockers[] = "Lô nội bộ {$allocation->finishedBatch->batch_number}: {$reason}.";
                } elseif ($allocation->supplierBatch && ($allocation->supplierBatch->status !== 'active' || ! $allocation->supplierBatch->coa_file)) {
                    $reason = $allocation->supplierBatch->status !== 'active' ? 'chưa được QC duyệt' : 'chưa có COA';
                    $shipmentBlockers[] = "Lô NCC {$allocation->supplierBatch->batch_number}: {$reason}.";
                }
            }
        }

        $canManageWarehouse = $this->canManageWarehouse();

        return view('warehouse.sales-order-confirm', compact('order', 'canManageWarehouse', 'shipmentBlockers'));
    }

    public function confirmSalesOrder(Request $request, Order $order)
    {
        $this->ensureWarehouseAccess();

        if ($order->status !== 'ready_to_ship' || ! $order->qa_confirmed_at || $order->warehouse_confirmed_at || $order->warehouse_packed_at) {
            return redirect()->route('warehouse.sales-orders')->with('error', 'Đơn hàng không còn chờ Kho xác nhận.');
        }
        if ($this->orderHasAssignedLots($order) && ! $order->production_packaged_at) {
            return redirect()->route('warehouse.sales-orders')->with('error', 'Sản xuất cần xác nhận đơn đã đóng gói trước khi Kho đóng đơn.');
        }

        $order->load('items');
        $rules = ['items' => 'required|array'];
        foreach ($order->items as $item) {
            $rules['items.'.$item->id] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules, [
            'items.*.required' => 'Vui lòng nhập số lượng thực tế cho tất cả sản phẩm.',
            'items.*.numeric' => 'Số lượng thực tế phải là số.',
            'items.*.min' => 'Số lượng thực tế không được âm.',
        ]);

        if (count($validated['items']) !== $order->items->count()) {
            return back()->withErrors(['items' => 'Vui lòng xác nhận đủ tất cả dòng sản phẩm.'])->withInput();
        }

        if (collect($validated['items'])->sum(fn ($quantity) => (float) $quantity) <= 0) {
            return back()->withErrors(['items' => 'Phiếu xuất phải có ít nhất một dòng số lượng lớn hơn 0.'])->withInput();
        }

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::with(['items.product', 'items.lotAllocations', 'items.labelPrints'])
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== 'ready_to_ship' || ! $lockedOrder->qa_confirmed_at || $lockedOrder->warehouse_confirmed_at || $lockedOrder->warehouse_packed_at || ($this->orderHasAssignedLots($lockedOrder) && ! $lockedOrder->production_packaged_at)) {
                    throw new DomainException('Đơn hàng không còn chờ đóng hàng.');
                }

                $hasPackedQuantity = false;
                foreach ($lockedOrder->items as $item) {
                    $actualQuantity = (float) $validated['items'][$item->id];
                    $allocations = $item->lotAllocations->where('status', 'reserved');
                    $availableReserved = $allocations->sum(function ($allocation) {
                        return max(0, (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity);
                    });

                    if ($item->label_target_count > 0 && (int) $item->labelPrints->sum('copies_count') < $item->label_target_count) {
                        throw new DomainException("Chưa in đủ {$item->label_target_count} nhãn thành phẩm cho {$item->product->name}.");
                    }

                    if ($actualQuantity > $availableReserved) {
                        throw new DomainException("Số lượng đóng hàng {$item->product->name} vượt quá lượng đã giữ theo lô.");
                    }

                    $remainingOrderQuantity = max(0, (float) $item->quantity - (float) $item->actual_quantity);
                    if ($actualQuantity > $remainingOrderQuantity) {
                        throw new DomainException("Số lượng đóng hàng {$item->product->name} vượt quá lượng còn lại của đơn.");
                    }

                    $item->update(['packed_quantity' => $actualQuantity]);
                    $hasPackedQuantity = $hasPackedQuantity || $actualQuantity > 0;
                }

                if (! $hasPackedQuantity) {
                    throw new DomainException('Phiếu đóng hàng phải có ít nhất một dòng số lượng lớn hơn 0.');
                }

                $lockedOrder->update(['warehouse_packed_at' => now()]);
            });
        } catch (DomainException $exception) {
            return back()->withErrors(['items' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('warehouse.sales-orders')->with('success', 'Đã ghi nhận đóng hàng; tồn kho chưa thay đổi. Xác nhận gửi hàng để ghi nhận xuất kho.');
    }

    public function confirmSalesOrderShipment(Order $order, InventoryLedger $inventoryLedger)
    {
        $this->ensureWarehouseAccess();

        if ($order->status !== 'ready_to_ship' || ! $order->qa_confirmed_at || $order->warehouse_confirmed_at || ! $order->warehouse_packed_at) {
            return redirect()->route('warehouse.sales-orders')->with('error', 'Đơn hàng chưa được đóng hàng hoặc không còn chờ xác nhận gửi.');
        }

        try {
            $orderCompleted = DB::transaction(function () use ($order, $inventoryLedger) {
                $lockedOrder = Order::with(['items.product', 'items.lotAllocations'])
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedOrder->status !== 'ready_to_ship' || ! $lockedOrder->qa_confirmed_at || $lockedOrder->warehouse_confirmed_at || ! $lockedOrder->warehouse_packed_at) {
                    throw new DomainException('Đơn hàng chưa được đóng hàng hoặc không còn chờ xác nhận gửi.');
                }

                foreach ($lockedOrder->items as $item) {
                    foreach ($item->lotAllocations->where('status', 'reserved') as $allocation) {
                        if ($allocation->production_finished_batch_id) {
                            $batch = ProductionFinishedBatch::whereKey($allocation->production_finished_batch_id)->lockForUpdate()->firstOrFail();
                            if (! $batch->qc_test_report_file || $batch->qc_result !== 'passed') {
                                throw new DomainException("Lô {$batch->batch_number} chưa có PKN đạt; chưa thể xác nhận xuất hàng.");
                            }
                        } elseif ($allocation->supplier_batch_id) {
                            $batch = SupplierBatch::whereKey($allocation->supplier_batch_id)->lockForUpdate()->firstOrFail();
                            if ($batch->status !== 'active' || ! $batch->coa_file) {
                                throw new DomainException("Lô NCC {$batch->batch_number} chưa có COA được QC duyệt; chưa thể xác nhận xuất hàng.");
                            }
                        }
                    }
                }

                $allItemsShipped = true;
                foreach ($lockedOrder->items as $item) {
                    $remainingToShip = (float) $item->packed_quantity;
                    $allocations = $item->lotAllocations->where('status', 'reserved');
                    $availableReserved = $allocations->sum(fn ($allocation) => max(0, (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity));
                    if ($remainingToShip > $availableReserved) {
                        throw new DomainException("Số lượng đã đóng của {$item->product->name} vượt quá lượng còn giữ theo lô.");
                    }

                    foreach ($allocations as $allocation) {
                        $allocationRemaining = max(0, (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity);
                        $shipQuantity = min($remainingToShip, $allocationRemaining);
                        if ($shipQuantity > 0) {
                            $this->decrementAllocatedLot($allocation, $shipQuantity, $item->product_id, $item->product->unit, $lockedOrder, $inventoryLedger);
                            $allocation->shipped_quantity = (float) $allocation->shipped_quantity + $shipQuantity;
                            $remainingToShip -= $shipQuantity;
                        }

                        $allocation->status = (float) $allocation->shipped_quantity >= (float) $allocation->reserved_quantity ? 'shipped' : 'reserved';
                        $allocation->save();

                        if ($remainingToShip <= 0) {
                            break;
                        }
                    }

                    $cumulativeQuantity = (float) $item->actual_quantity + (float) $item->packed_quantity;
                    $item->update(['actual_quantity' => $cumulativeQuantity, 'packed_quantity' => 0]);
                    if ($cumulativeQuantity + 0.0001 < (float) $item->quantity) {
                        $allItemsShipped = false;
                    }
                }

                $lockedOrder->update([
                    'status' => $allItemsShipped ? 'completed' : 'ready_to_ship',
                    'warehouse_confirmed_at' => $allItemsShipped ? now() : null,
                    'warehouse_packed_at' => null,
                ]);

                return $allItemsShipped;
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('warehouse.sales-orders')->with('success', $orderCompleted
            ? 'Đã xác nhận gửi đủ hàng, ghi nhận xuất kho và hoàn tất đơn.'
            : 'Đã xác nhận gửi hàng và ghi nhận xuất kho; đơn vẫn chờ giao phần còn lại.');
    }

    private function decrementAllocatedLot(SalesOrderLotAllocation $allocation, float $quantity, int $productId, string $unit, Order $order, InventoryLedger $inventoryLedger): void
    {
        $lotRelations = [
            'supplier_batch_id' => [SupplierBatch::class, 'supplier_batch_id'],
            'production_finished_batch_id' => [ProductionFinishedBatch::class, 'production_finished_batch_id'],
        ];

        foreach ($lotRelations as $foreignKey => [$model, $movementForeignKey]) {
            if (! $allocation->{$foreignKey}) {
                continue;
            }

            $lot = $model::whereKey($allocation->{$foreignKey})->lockForUpdate()->firstOrFail();
            if ((float) $lot->current_quantity < $quantity) {
                throw new DomainException("Tồn lô không đủ để xuất đơn hàng {$order->order_code}.");
            }

            $inventoryLedger->post(
                $lot,
                'SALE_SHIPMENT',
                'out',
                $quantity,
                $unit,
                Order::class,
                $order->id,
                auth()->id(),
            );

            return;
        }

        throw new DomainException('Dòng đơn hàng chưa được giữ tồn theo lô.');
    }
}
