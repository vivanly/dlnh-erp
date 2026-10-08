<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ppcb;
use App\Models\Product;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMaterialLot;
use App\Models\ProductionOrder;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Services\InventoryLedger;
use App\Services\SupplierBatchAvailability;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductionController extends Controller
{
    private function userCanManageBom(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isQADepartment') && $user->isQADepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    private function userCanWorkInWarehouse(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isWarehouseDepartment') && $user->isWarehouseDepartment()) ||
            (method_exists($user, 'isWarehouseManager') && $user->isWarehouseManager()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    private function userCanWorkInProduction(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isProductionDepartment') && $user->isProductionDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    private function userCanManageQuality(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isQCDepartment') && $user->isQCDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $canPlanOrders = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
        abort_unless($user, 403);

        $productionOrders = ProductionOrder::with(['order.customer', 'order.productionOrders', 'product', 'materials.product', 'monthlyPlanLine.plan'])
            ->whereIn('status', ['pending_director_approval', 'released', 'materials_issued', 'production_reported'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('production_code', 'like', "%{$search}%")
                        ->orWhereHas('order', function ($orderQuery) use ($search) {
                            $orderQuery->where('order_code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        })
                        ->orWhereHas('materials.product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        })
                        ->orWhereHas('materials.lots.supplierBatch', function ($batchQuery) use ($search) {
                            $batchQuery->where('batch_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('finishedBatches', function ($batchQuery) use ($search) {
                            $batchQuery->where('batch_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('allocatedFinishedBatches', function ($batchQuery) use ($search) {
                            $batchQuery->where('batch_number', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('production-orders.index', compact('productionOrders'));
    }

    public function qaFinishedBatches(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $canManageQuality = $this->userCanManageQuality();

        $batches = ProductionFinishedBatch::with(['product', 'productionOrder.order', 'orderAllocations.productionOrder', 'ppcb'])
            ->where('status', 'active')
            ->whereNotNull('warehouse_received_at')
            ->where('pending_warehouse_quantity', '<=', 0.000001)
            ->where(function ($query) {
                $query->whereNull('qc_test_report_file')->orWhere('qc_result', 'failed');
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('batch_number', 'like', "%{$search}%")
                        ->orWhere('provisional_batch_number', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('qa.production-batches', compact('batches', 'canManageQuality'));
    }

    public function qaLotsNeeded(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $productionOrders = ProductionOrder::with(['product', 'order', 'monthlyPlanLine.plan'])
            ->where('status', 'completed')
            ->where('pending_finished_quantity', '>', 0)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('production_code', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('order', fn ($order) => $order->where('order_code', 'like', "%{$search}%"));
                });
            })
            ->latest('completed_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $canManageLots = $this->userCanManageBom();

        return view('qa.production-output-lots', compact('productionOrders', 'canManageLots'));
    }

    public function createInternalLot(Request $request)
    {
        abort_unless($this->userCanManageBom(), 403);

        $sourceProductionOrder = null;
        if ($request->filled('production_order_id')) {
            $sourceProductionOrder = ProductionOrder::with('product')
                ->whereKey($request->integer('production_order_id'))
                ->where('status', 'completed')
                ->where('pending_finished_quantity', '>', 0)
                ->firstOrFail();
        }

        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'classification', 'origin']);
        $ppcbs = Ppcb::query()->orderBy('ten_ppcb')->get(['id', 'ma', 'ten_ppcb']);
        $availableProductionOrders = ProductionOrder::with(['product', 'monthlyPlanLine.plan'])
            ->where('status', 'completed')
            ->where('pending_finished_quantity', '>', 0)
            ->orderByDesc('completed_at')
            ->get();

        return view('qa.internal-lots.create', compact('products', 'ppcbs', 'sourceProductionOrder', 'availableProductionOrders'));
    }

    public function internalLotsIndex(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $canManageLots = $this->userCanManageBom();

        $batches = ProductionFinishedBatch::with(['product', 'productionOrder', 'orderAllocations.productionOrder', 'ppcb'])
            ->withCount(['salesOrderAllocations', 'inventoryMovements', 'inputs', 'codeHistories'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('batch_number', 'like', "%{$search}%")
                        ->orWhere('provisional_batch_number', 'like', "%{$search}%")
                        ->orWhere('origin', 'like', "%{$search}%")
                        ->orWhere('qc_test_report', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('ppcb', fn ($ppcb) => $ppcb->where('ma', 'like', "%{$search}%")->orWhere('ten_ppcb', 'like', "%{$search}%"))
                        ->orWhereHas('productionOrder', fn ($order) => $order->where('production_code', 'like', "%{$search}%"))
                        ->orWhereHas('orderAllocations.productionOrder', fn ($order) => $order->where('production_code', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        foreach ($batches as $batch) {
            $batch->setAttribute('can_delete', $this->canDeleteInternalLot($batch));
        }

        $availableProductionOrders = ProductionOrder::with('product')
            ->where('status', 'completed')
            ->where('pending_finished_quantity', '>', 0)
            ->orderByDesc('completed_at')
            ->get();

        $lotsToClose = ProductionFinishedBatch::with('product')
            ->where('status', 'active')
            ->where('current_quantity', '<=', 0.000001)
            ->where('pending_warehouse_quantity', '<=', 0.000001)
            ->where('qc_result', 'passed')
            ->whereNotNull('qc_test_report_file')
            ->whereDoesntHave('salesOrderAllocations', function ($query) {
                $query->where('status', 'reserved')
                    ->whereRaw('reserved_quantity - shipped_quantity > 0.000001');
            })
            ->latest()
            ->paginate($this->perPage($request), ['*'], 'close_page')
            ->withQueryString();

        return view('qa.internal-lots.index', compact('batches', 'lotsToClose', 'canManageLots', 'availableProductionOrders'));
    }

    public function editInternalLot(ProductionFinishedBatch $productionFinishedBatch)
    {
        abort_unless($this->userCanManageBom(), 403);

        $productionFinishedBatch->load(['product', 'productionOrder', 'orderAllocations.productionOrder']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'classification', 'origin']);
        $ppcbs = Ppcb::query()->orderBy('ten_ppcb')->get(['id', 'ma', 'ten_ppcb']);
        $canEditDefinition = $this->canEditInternalLotDefinition($productionFinishedBatch);
        $canEditProductionAllocations = $this->canEditInternalLotProductionAllocations($productionFinishedBatch);
        $existingProductionOrderIds = $productionFinishedBatch->orderAllocations->pluck('production_order_id')->all();
        if ($productionFinishedBatch->orderAllocations->isEmpty() && $productionFinishedBatch->production_order_id) {
            $existingProductionOrderIds[] = $productionFinishedBatch->production_order_id;
        }
        $availableProductionOrders = ProductionOrder::with('product')
            ->where('status', 'completed')
            ->where(function ($query) use ($existingProductionOrderIds) {
                $query->where('pending_finished_quantity', '>', 0);
                if ($existingProductionOrderIds) {
                    $query->orWhereIn('id', $existingProductionOrderIds);
                }
            })
            ->orderByDesc('completed_at')
            ->get();

        return view('qa.internal-lots.edit', compact('productionFinishedBatch', 'products', 'ppcbs', 'canEditDefinition', 'canEditProductionAllocations', 'availableProductionOrders'));
    }

    public function updateInternalLot(Request $request, ProductionFinishedBatch $productionFinishedBatch)
    {
        abort_unless($this->userCanManageBom(), 403);

        $validated = $request->validate([
            'batch_number' => ['required', 'string', 'max:255', 'unique:production_finished_batches,batch_number,'.$productionFinishedBatch->id],
            'product_id' => ['nullable', 'exists:products,id'],
            'planned_quantity' => ['nullable', 'numeric', 'gt:0'],
            'mfg_date' => ['nullable', 'date'],
            'exp_date' => ['nullable', 'date', 'after_or_equal:mfg_date'],
            'origin' => ['nullable', 'string', 'max:255'],
            'ppcb_id' => ['nullable', 'exists:ppcb,id'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'production_order_allocations' => ['sometimes', 'array'],
            'production_order_allocations.*' => ['nullable', 'numeric', 'gte:0'],
        ]);

        try {
            DB::transaction(function () use ($productionFinishedBatch, $validated) {
                $batch = ProductionFinishedBatch::whereKey($productionFinishedBatch->id)->lockForUpdate()->firstOrFail();
                $canEditDefinition = $this->canEditInternalLotDefinition($batch);
                $canEditProductionAllocations = $this->canEditInternalLotProductionAllocations($batch);
                $newCode = trim($validated['batch_number']);
                if (array_key_exists('production_order_allocations', $validated) && ! $canEditProductionAllocations) {
                    throw new DomainException('Khong the sua phan bo lenh sau khi QA duyet hoac Kho da nhap lo.');
                }
                if ($newCode !== $batch->batch_number && $batch->salesOrderAllocations()->whereHas('labelPrints')->exists()) {
                    throw new DomainException('Không thể đổi số lô sau khi đã in nhãn cho đơn hàng.');
                }
                if (! $canEditDefinition && (isset($validated['product_id']) || isset($validated['planned_quantity']))) {
                    throw new DomainException('Không thể đổi sản phẩm hoặc cỡ lô sau khi lô đã được nhập kho, phân bổ hoặc phát sinh giao dịch.');
                }

                if ($newCode !== $batch->batch_number) {
                    $batch->codeHistories()->create([
                        'old_batch_number' => $batch->batch_number,
                        'new_batch_number' => $newCode,
                        'changed_by' => auth()->id(),
                    ]);
                }

                $updates = [
                    'batch_number' => $newCode,
                    'mfg_date' => $validated['mfg_date'] ?? null,
                    'exp_date' => $validated['exp_date'] ?? null,
                    'origin' => $validated['origin'] ?? null,
                    'ppcb_id' => $validated['ppcb_id'] ?? null,
                    'license_number' => $validated['license_number'] ?? null,
                ];
                if ($canEditDefinition) {
                    $product = Product::findOrFail($validated['product_id']);
                    $updates['product_id'] = $product->id;
                    $updates['unit'] = $product->unit;
                    $updates['planned_quantity'] = $validated['planned_quantity'];
                    $updates['pending_warehouse_quantity'] = $batch->status === 'planned'
                        ? 0
                        : $validated['planned_quantity'];
                }

                $batch->update($updates);
                if (array_key_exists('production_order_allocations', $validated)) {
                    $this->syncInternalLotProductionAllocations($batch, $validated['production_order_allocations'] ?? []);
                }
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('qa.internal-lots.index')->with('success', 'Đã cập nhật lô nội bộ.');
    }

    public function destroyInternalLot(ProductionFinishedBatch $productionFinishedBatch)
    {
        abort_unless($this->userCanManageBom(), 403);

        try {
            DB::transaction(function () use ($productionFinishedBatch) {
                $batch = ProductionFinishedBatch::whereKey($productionFinishedBatch->id)->lockForUpdate()->firstOrFail();
                if (! $this->canDeleteInternalLot($batch)) {
                    throw new DomainException('Chỉ xóa được lô độc lập chưa nhập kho và chưa được phân bổ hoặc ghi nhận giao dịch.');
                }
                $batch->delete();
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('qa.internal-lots.index')->with('success', 'Đã xóa lô nội bộ chưa phát sinh giao dịch.');
    }

    private function canEditInternalLotDefinition(ProductionFinishedBatch $batch): bool
    {
        return ! $batch->production_order_id
            && ! $batch->orderAllocations()->exists()
            && ! $batch->warehouse_received_at
            && (float) $batch->current_quantity <= 0.000001
            && ! $batch->salesOrderAllocations()->exists()
            && ! $batch->inventoryMovements()->exists()
            && ! $batch->inputs()->exists();
    }

    private function canEditInternalLotProductionAllocations(ProductionFinishedBatch $batch): bool
    {
        return $batch->status === 'pending_qa'
            && ! $batch->qa_approved_at
            && ! $batch->qc_test_report_file
            && ! $batch->warehouse_received_at
            && (float) $batch->current_quantity <= 0.000001
            && ! $batch->salesOrderAllocations()->exists()
            && ! $batch->inventoryMovements()->exists();
    }

    private function syncInternalLotProductionAllocations(ProductionFinishedBatch $batch, array $requestedAllocations): void
    {
        $newAllocations = collect($requestedAllocations)
            ->mapWithKeys(fn ($quantity, $orderId) => [(int) $orderId => (float) ($quantity ?? 0)])
            ->filter(fn ($quantity) => $quantity > 0);
        $newTotal = (float) $newAllocations->sum();
        if ($newTotal > (float) $batch->planned_quantity + 0.000001) {
            throw new DomainException('Tong san luong phan bo khong duoc vuot suc chua cua lo.');
        }

        $existingAllocations = $batch->orderAllocations()->get();
        $previousAllocations = $existingAllocations->mapWithKeys(
            fn ($allocation) => [(int) $allocation->production_order_id => (float) $allocation->quantity],
        );
        if ($previousAllocations->isEmpty() && $batch->production_order_id && (float) $batch->initial_quantity > 0) {
            $previousAllocations->put((int) $batch->production_order_id, (float) $batch->initial_quantity);
        }

        $orderIds = $previousAllocations->keys()->merge($newAllocations->keys())->unique()->sort()->values();
        $orders = ProductionOrder::whereIn('id', $orderIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        if ($orders->count() !== $orderIds->count()) {
            throw new DomainException('Khong tim thay mot lenh san xuat dang lien ket.');
        }

        foreach ($previousAllocations as $orderId => $quantity) {
            $orders->get($orderId)->increment('pending_finished_quantity', $quantity);
        }

        $batch->orderAllocations()->delete();
        $batch->inputs()->delete();

        foreach ($newAllocations as $orderId => $quantity) {
            $order = $orders->get($orderId);
            if (
                $order->status !== 'completed'
                || (int) $order->product_id !== (int) $batch->product_id
                || (float) $order->pending_finished_quantity + 0.000001 < $quantity
            ) {
                throw new DomainException('Lenh phai hoan thanh, dung san pham va con du san luong chua phan bo.');
            }

            $availableBeforeAllocation = (float) $order->pending_finished_quantity;
            $batch->orderAllocations()->create([
                'production_order_id' => $order->id,
                'quantity' => $quantity,
            ]);
            $order->decrement('pending_finished_quantity', $quantity);
            $this->allocateOrderInputsToBatch($order, $batch, $quantity, $availableBeforeAllocation);
        }

        $batch->update([
            'production_order_id' => $newAllocations->count() === 1 ? $newAllocations->keys()->first() : null,
            'initial_quantity' => $newTotal,
            'pending_warehouse_quantity' => $newTotal > 0 ? $newTotal : $batch->planned_quantity,
        ]);
    }

    private function canDeleteInternalLot(ProductionFinishedBatch $batch): bool
    {
        return ! $batch->production_order_id
            && ! $batch->orderAllocations()->exists()
            && ! $batch->warehouse_received_at
            && (float) $batch->current_quantity <= 0.000001
            && ((float) $batch->pending_warehouse_quantity > 0 || $batch->status === 'planned')
            && ! $batch->salesOrderAllocations()->exists()
            && ! $batch->inventoryMovements()->exists()
            && ! $batch->inputs()->exists()
            && ! $batch->codeHistories()->exists();
    }

    public function storeInternalLot(Request $request)
    {
        abort_unless($this->userCanManageBom(), 403);

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'batch_number' => ['required', 'string', 'max:255', 'unique:production_finished_batches,batch_number'],
            'ppcb_id' => ['nullable', 'exists:ppcb,id'],
            'planned_quantity' => ['required', 'numeric', 'gt:0'],
            'mfg_date' => ['nullable', 'date'],
            'exp_date' => ['nullable', 'date', 'after_or_equal:mfg_date'],
            'license_number' => ['nullable', 'string', 'max:255'],
            'production_order_allocations' => ['nullable', 'array'],
            'production_order_allocations.*' => ['nullable', 'numeric', 'gt:0'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $product = Product::findOrFail($validated['product_id']);
                $capacity = (float) $validated['planned_quantity'];
                $batch = ProductionFinishedBatch::create([
                    'product_id' => $product->id,
                    'origin' => $product->origin,
                    'ppcb_id' => $validated['ppcb_id'] ?? null,
                    'license_number' => $validated['license_number'] ?? null,
                    'batch_number' => trim($validated['batch_number']),
                    'provisional_batch_number' => trim($validated['batch_number']),
                    'planned_quantity' => $capacity,
                    'initial_quantity' => 0,
                    'current_quantity' => 0,
                    'pending_warehouse_quantity' => 0,
                    'unit' => $product->unit,
                    'mfg_date' => $validated['mfg_date'] ?? null,
                    'exp_date' => $validated['exp_date'] ?? null,
                    'status' => 'pending_qa',
                ]);

                $allocations = collect($validated['production_order_allocations'] ?? [])
                    ->mapWithKeys(fn ($quantity, $orderId) => [(int) $orderId => (float) $quantity])
                    ->filter(fn ($quantity) => $quantity > 0);
                $allocationTotal = (float) $allocations->sum();
                if ($allocationTotal > $capacity + 0.000001) {
                    throw new DomainException('Total allocated production quantity cannot exceed the lot capacity.');
                }

                foreach ($allocations as $orderId => $quantity) {
                    $order = ProductionOrder::whereKey($orderId)->lockForUpdate()->firstOrFail();
                    if (
                        $order->status !== 'completed'
                        || (int) $order->product_id !== (int) $product->id
                        || (float) $order->pending_finished_quantity + 0.000001 < $quantity
                    ) {
                        throw new DomainException('A selected production order has insufficient unallocated output or a different product.');
                    }

                    $availableBeforeAllocation = (float) $order->pending_finished_quantity;
                    $existingAllocation = $batch->orderAllocations()->where('production_order_id', $order->id)->first();
                    $batch->orderAllocations()->updateOrCreate(
                        ['production_order_id' => $order->id],
                        ['quantity' => (float) ($existingAllocation?->quantity ?? 0) + $quantity],
                    );
                    $order->decrement('pending_finished_quantity', $quantity);
                    $this->allocateOrderInputsToBatch($order, $batch, $quantity, $availableBeforeAllocation);
                }

                $batch->update([
                    'initial_quantity' => $allocationTotal,
                    'pending_warehouse_quantity' => $allocationTotal > 0 ? $allocationTotal : $capacity,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('qa.internal-lots.index')
            ->with('success', 'Đã tạo mã lô độc lập với sản lượng/lệnh sản xuất. QA có thể phân bổ lượng dự kiến ngay; Kho nhập số lượng thực tế theo từng lần, không vượt lượng dự kiến.');
    }

    public function allocateProductionOrderToLot(Request $request, ProductionFinishedBatch $productionFinishedBatch)
    {
        abort_unless($this->userCanManageBom(), 403);
        $validated = $request->validate([
            'production_order_id' => ['required', 'integer', 'exists:production_orders,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            DB::transaction(function () use ($productionFinishedBatch, $validated) {
                $batch = ProductionFinishedBatch::whereKey($productionFinishedBatch->id)->lockForUpdate()->firstOrFail();
                if (
                    ! in_array($batch->status, ['pending_qa', 'active'], true)
                    || $batch->qa_approved_at !== null
                    || $batch->qc_test_report_file !== null
                    || ((float) $batch->initial_quantity > (float) $batch->orderAllocations()->sum('quantity') + 0.000001)
                ) {
                    throw new DomainException('Production can only be allocated before QA approval and must belong to the same lot genealogy.');
                }

                $order = ProductionOrder::whereKey($validated['production_order_id'])->lockForUpdate()->firstOrFail();
                $quantity = (float) $validated['quantity'];
                if (
                    $order->status !== 'completed'
                    || (int) $order->product_id !== (int) $batch->product_id
                    || (float) $order->pending_finished_quantity + 0.000001 < $quantity
                ) {
                    throw new DomainException('The production order must have enough unallocated output and match the lot product.');
                }
                if ((float) $batch->initial_quantity + $quantity > (float) $batch->planned_quantity + 0.000001) {
                    throw new DomainException('Allocated output cannot exceed the lot capacity.');
                }

                $availableBeforeAllocation = (float) $order->pending_finished_quantity;
                $existingAllocation = $batch->orderAllocations()->where('production_order_id', $order->id)->first();
                $batch->orderAllocations()->updateOrCreate(
                    ['production_order_id' => $order->id],
                    ['quantity' => (float) ($existingAllocation?->quantity ?? 0) + $quantity],
                );
                $batch->update([
                    'initial_quantity' => (float) $batch->initial_quantity + $quantity,
                    'pending_warehouse_quantity' => (float) $batch->pending_warehouse_quantity + $quantity,
                    'status' => $batch->warehouse_received_at ? 'active' : 'pending_qa',
                ]);
                $order->decrement('pending_finished_quantity', $quantity);
                $this->allocateOrderInputsToBatch($order, $batch, $quantity, $availableBeforeAllocation);
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Production output was allocated to the internal lot.');
    }

    public function closeFinishedBatch(ProductionFinishedBatch $productionFinishedBatch)
    {
        abort_unless($this->userCanManageBom(), 403);

        try {
            DB::transaction(function () use ($productionFinishedBatch) {
                $batch = ProductionFinishedBatch::whereKey($productionFinishedBatch->id)->lockForUpdate()->firstOrFail();
                if ($batch->status !== 'active') {
                    throw new DomainException('Chỉ chốt được lô đã QC đạt và đang hoạt động.');
                }
                if ($batch->qc_result !== 'passed' || ! $batch->qc_test_report_file) {
                    throw new DomainException('Chỉ chốt lô đã có PKN đạt do QC xác nhận.');
                }
                if ((float) $batch->current_quantity > 0.000001 || (float) $batch->pending_warehouse_quantity > 0.000001) {
                    throw new DomainException('Lô vẫn còn tồn thực tế hoặc lượng chờ Kho nhập.');
                }
                $reservedQuantity = (float) SalesOrderLotAllocation::query()
                    ->where('production_finished_batch_id', $batch->id)
                    ->where('status', 'reserved')
                    ->selectRaw('COALESCE(SUM(reserved_quantity - shipped_quantity), 0) as reserved_quantity')
                    ->value('reserved_quantity');
                if ($reservedQuantity > 0.000001) {
                    throw new DomainException('Lô còn số lượng đã giữ cho đơn chưa giao.');
                }

                $batch->update(['status' => 'closed']);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã chốt lô thành phẩm đã dùng hết.');
    }

    private function allocateOrderInputsToBatch(ProductionOrder $order, ProductionFinishedBatch $batch, float $quantity, float $unallocatedBefore): void
    {
        $order->loadMissing('materials.lots.finishedBatchInputs');
        $actualQuantity = (float) $order->actual_quantity;
        if ($actualQuantity <= 0) {
            throw new DomainException('Production order has no confirmed actual output to allocate.');
        }

        $isFinalAllocation = $quantity >= $unallocatedBefore - 0.000001;
        foreach ($order->materials as $material) {
            foreach ($material->lots as $materialLot) {
                $consumed = max(0, (float) $materialLot->issued_quantity - (float) $materialLot->returned_quantity);
                $alreadyAssigned = (float) $materialLot->finishedBatchInputs()->sum('consumed_quantity');
                $remaining = max(0, $consumed - $alreadyAssigned);
                $inputQuantity = $isFinalAllocation
                    ? $remaining
                    : min($remaining, round($consumed * $quantity / $actualQuantity, 4));
                if ($inputQuantity <= 0) {
                    continue;
                }

                $existingInput = $batch->inputs()->where('production_material_lot_id', $materialLot->id)->first();
                if ($existingInput) {
                    $existingInput->update(['consumed_quantity' => (float) $existingInput->consumed_quantity + $inputQuantity]);
                } else {
                    $batch->inputs()->create([
                        'production_material_lot_id' => $materialLot->id,
                        'consumed_quantity' => $inputQuantity,
                        'unit' => $material->unit,
                    ]);
                }
            }
        }
    }

    public function approveFinishedBatch(Request $request, ProductionFinishedBatch $productionFinishedBatch)
    {
        abort_unless($this->userCanManageQuality(), 403);

        $validated = $request->validate([
            'qc_test_report' => ['required', 'string', 'max:255'],
            'qc_date' => ['required', 'date'],
            'qc_test_report_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'qc_result' => ['required', 'in:passed,failed'],
        ]);

        $oldReportPath = $productionFinishedBatch->qc_test_report_file;
        $reportPath = null;

        try {
            DB::transaction(function () use ($productionFinishedBatch, $validated, $request, &$reportPath) {
                $batch = ProductionFinishedBatch::whereKey($productionFinishedBatch->id)->lockForUpdate()->firstOrFail();
                if ($batch->status !== 'active' || ! $batch->warehouse_received_at) {
                    throw new DomainException('QC chỉ kiểm nghiệm lô sau khi Kho đã nhập thành phẩm.');
                }
                if ((float) $batch->pending_warehouse_quantity > 0.000001) {
                    throw new DomainException('QC chỉ được duyệt sau khi Kho nhập đủ sản lượng đã phân bổ cho lô.');
                }

                $reportPath = $request->file('qc_test_report_file')->store('qc-reports', 'local');

                $batch->update([
                    'qc_test_report' => trim($validated['qc_test_report']),
                    'qc_date' => $validated['qc_date'],
                    'qc_test_report_file' => $reportPath,
                    'qc_result' => $validated['qc_result'],
                    'qa_approved_at' => now(),
                    'qa_approved_by' => auth()->id(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($oldReportPath && $oldReportPath !== $reportPath) {
            Storage::disk('local')->delete($oldReportPath);
        }

        return back()->with('success', $validated['qc_result'] === 'passed'
            ? 'Đã ghi nhận PKN đạt cho lô thành phẩm.'
            : 'Đã ghi nhận PKN không đạt; lô bị chặn xuất giao.');
    }

    public function show(ProductionOrder $productionOrder)
    {
        $user = auth()->user();
        $canPlanOrders = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
        abort_unless($user, 403);

        $productionOrder->load([
            'order.customer',
            'orderItem.product',
            'product',
            'materials.product',
            'materials.lots.supplierBatch',
            'finishedBatches',
            'allocatedFinishedBatches',
            'monthlyPlanLine.plan',
        ]);
        $productionOrder->setRelation(
            'allFinishedBatches',
            $productionOrder->finishedBatches->merge($productionOrder->allocatedFinishedBatches)->unique('id')->values(),
        );

        $materialLots = collect();
        if ($productionOrder->status === 'released') {
            $pairs = \App\Models\MaterialStockMovement::select('material_type', 'material_id')->distinct()->get();
            foreach ($pairs as $pair) {
                $catalogItem = $pair->material_type === 'accessory' ? \App\Models\Accessory::find($pair->material_id) : \App\Models\RawMaterial::find($pair->material_id);
                if (!$catalogItem) {
                    continue;
                }
                foreach (\App\Models\MaterialStockMovement::lotBalances($pair->material_type, (int) $pair->material_id) as $lot) {
                    $materialLots->push((object) ['type' => $pair->material_type, 'id' => $pair->material_id, 'item' => $catalogItem, 'batch' => $lot->batch, 'exp_date' => $lot->exp_date, 'balance' => $lot->balance]);
                }
            }
        }
        return view('production-orders.show', [
            'productionOrder' => $productionOrder,
            'materialLots' => $materialLots,
            'canIssueMaterials' => $this->userCanWorkInWarehouse(),
            'canReceiveFinishedBatch' => $this->userCanWorkInWarehouse(),
            'canManageLots' => $this->userCanManageBom(),
            'canWorkInProduction' => $this->userCanWorkInProduction(),
        ]);
    }

    public function reportProductionOutput(Request $request, ProductionOrder $productionOrder)
    {
        abort_unless($this->userCanWorkInProduction(), 403);

        if ($productionOrder->status !== 'materials_issued') {
            return back()->with('error', 'Production order is no longer waiting for an output report.');
        }

        $validated = $request->validate([
            'actual_quantity' => ['required', 'numeric', 'min:0.0001'],
            'mfg_date' => ['nullable', 'date'],
            'reported_returned_materials' => ['nullable', 'array'],
            'reported_returned_materials.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($productionOrder, $validated) {
                $lockedOrder = ProductionOrder::whereKey($productionOrder->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'materials_issued') {
                    throw new DomainException('Production order is no longer waiting for an output report.');
                }
                $lockedOrder->load('materials.lots');

                $issuedLots = $lockedOrder->materials
                    ->flatMap(fn ($material) => $material->lots)
                    ->filter(fn ($allocation) => (float) $allocation->issued_quantity > 0);
                $submittedReturnIds = array_map('intval', array_keys($validated['reported_returned_materials'] ?? []));
                if (array_diff($submittedReturnIds, $issuedLots->pluck('id')->map(fn ($id) => (int) $id)->all())) {
                    throw new DomainException('A returned material does not belong to this production order.');
                }
                foreach ($issuedLots as $allocation) {
                    $reportedReturn = (float) ($validated['reported_returned_materials'][$allocation->id] ?? 0);
                    if ($reportedReturn > (float) $allocation->issued_quantity + 0.0001) {
                        throw new DomainException('Reported returned material cannot exceed the issued quantity.');
                    }
                    $allocation->update(['reported_returned_quantity' => $reportedReturn]);
                }

                $actualOutputQuantity = (float) $validated['actual_quantity'];
                $remainingOutput = $actualOutputQuantity;
                $isMonthlyPlanOrder = $lockedOrder->monthlyPlanLine()->exists();

                if (! $isMonthlyPlanOrder) {
                    $outputBatches = $lockedOrder->finishedBatches()
                        ->where('status', 'planned')
                        ->lockForUpdate()
                        ->get();
                    foreach ($outputBatches as $outputBatch) {
                        $capacity = max(0, (float) $outputBatch->planned_quantity
                            - (float) $outputBatch->initial_quantity);
                        $reportedBatchQuantity = min($remainingOutput, $capacity);
                        if ($reportedBatchQuantity <= 0) {
                            continue;
                        }

                        $outputBatch->update([
                            'initial_quantity' => (float) $outputBatch->initial_quantity + $reportedBatchQuantity,
                            'pending_warehouse_quantity' => (float) $outputBatch->pending_warehouse_quantity + $reportedBatchQuantity,
                            'mfg_date' => $validated['mfg_date'] ?? today(),
                            'status' => 'pending_qa',
                            'received_by' => auth()->id(),
                        ]);
                        $outputBatch->orderAllocations()->updateOrCreate(
                            ['production_order_id' => $lockedOrder->id],
                            ['quantity' => (float) $outputBatch->orderAllocations()->where('production_order_id', $lockedOrder->id)->value('quantity') + $reportedBatchQuantity],
                        );
                        $remainingOutput -= $reportedBatchQuantity;
                    }
                }

                $lockedOrder->update([
                    'actual_quantity' => $actualOutputQuantity,
                    'pending_finished_quantity' => $remainingOutput,
                    'status' => 'production_reported',
                    'production_reported_at' => now(),
                    'production_reported_by' => auth()->id(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('production-orders.show', $productionOrder)
            ->with('success', 'Production output reported. The order is waiting for Warehouse to verify returned materials and confirm completion.');
    }

    public function packagingQueue(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $orders = Order::with([
            'customer',
            'items.product',
            'items.labelPrints',
            'items.lotAllocations.finishedBatch',
            'items.lotAllocations.supplierBatch',
            'items.lotAllocations.labelPrints',
        ])
            ->where('status', 'ready_to_ship')
            ->whereNotNull('qa_confirmed_at')
            ->whereNull('production_packaged_at')
            ->whereHas('items.lotAllocations', fn ($query) => $query->where('status', 'reserved'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $canWorkInProduction = $this->userCanWorkInProduction();

        return view('production.packaging-queue', compact('orders', 'canWorkInProduction'));
    }

    public function confirmOrderPackaged(Order $order)
    {
        abort_unless($this->userCanWorkInProduction(), 403);

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::with(['items.product', 'items.labelPrints', 'items.lotAllocations.labelPrints'])
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($lockedOrder->status !== 'ready_to_ship' || ! $lockedOrder->qa_confirmed_at || $lockedOrder->production_packaged_at) {
                    throw new DomainException('Đơn chưa sẵn sàng đóng gói hoặc đã được xác nhận đóng gói.');
                }

                $hasLots = false;
                foreach ($lockedOrder->items as $item) {
                    $allocations = $item->lotAllocations->where('status', 'reserved');
                    if ($allocations->isEmpty() || $allocations->sum(fn ($allocation) => (float) $allocation->reserved_quantity) + 0.0001 < (float) $item->quantity) {
                        throw new DomainException("QA chưa giữ đủ lô cho {$item->product->name}.");
                    }
                    if ($item->label_target_count > 0 && (int) $item->labelPrints->sum('copies_count') < $item->label_target_count) {
                        throw new DomainException("Chưa in đủ {$item->label_target_count} nhãn cho {$item->product->name}.");
                    }
                    $hasLots = true;
                }
                if (! $hasLots) {
                    throw new DomainException('Đơn chưa được QA gán lô để đóng gói.');
                }

                $lockedOrder->update([
                    'production_packaged_at' => now(),
                    'production_packaged_by' => auth()->id(),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã xác nhận đơn hàng được Sản xuất đóng gói.');
    }

    public function issueMaterials(Request $request, ProductionOrder $productionOrder, InventoryLedger $inventoryLedger, SupplierBatchAvailability $availability)
    {
        abort_unless($this->userCanWorkInWarehouse(), 403);

        if ($productionOrder->status !== 'released') {
            return back()->with('error', 'Giám đốc cần duyệt kế hoạch trước khi Kho xuất nguyên liệu.');
        }
        $validated = $request->validate([
            'lots' => ['nullable', 'array'],
            'lots.*' => ['nullable', 'numeric', 'min:0'],
            'material_lots' => ['nullable', 'array'],
            'material_lots.*.type' => ['required', 'in:raw_material,accessory'],
            'material_lots.*.id' => ['required', 'integer'],
            'material_lots.*.batch' => ['nullable', 'string', 'max:255'],
            'material_lots.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);
        $materialSelected = collect($validated['material_lots'] ?? [])
            ->map(fn ($row) => ['type' => $row['type'], 'id' => (int) $row['id'], 'batch' => (string) ($row['batch'] ?? ''), 'quantity' => round((float) ($row['quantity'] ?? 0), 4)])
            ->filter(fn ($row) => $row['quantity'] > 0)
            ->groupBy(fn ($row) => $row['type'] . '|' . $row['id'] . '|' . $row['batch'])
            ->map(fn ($rows) => ['type' => $rows[0]['type'], 'id' => $rows[0]['id'], 'batch' => $rows[0]['batch'], 'quantity' => round($rows->sum('quantity'), 4)])
            ->values();
        $selected = collect($validated['lots'] ?? [])
            ->mapWithKeys(fn ($quantity, $batchId) => [(int) $batchId => round((float) $quantity, 4)])
            ->filter(fn ($quantity) => $quantity > 0);
        if ($selected->isEmpty() && $materialSelected->isEmpty()) {
            return back()->withInput()->with('error', 'Kho chưa chọn lô nguyên liệu và số lượng xuất.');
        }

        try {
            DB::transaction(function () use ($productionOrder, $selected, $materialSelected, $inventoryLedger, $availability) {
                $lockedOrder = ProductionOrder::whereKey($productionOrder->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'released') {
                    throw new DomainException('Lệnh sản xuất không còn chờ xuất nguyên liệu.');
                }

                $batches = SupplierBatch::with('product')
                    ->whereIn('id', $selected->keys())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
                if ($batches->count() !== $selected->count()) {
                    throw new DomainException('Có lô nguyên liệu được chọn không tồn tại.');
                }
                $availability->addTo($batches);

                $materials = [];
                foreach ($batches as $batch) {
                    $quantity = $selected->get($batch->id);
                    if ($quantity > (float) $batch->available_quantity + 0.0001) {
                        throw new DomainException("Lô {$batch->batch_number} không còn đủ tồn khả dụng để xuất.");
                    }

                    $material = $materials[$batch->product_id] ??= $lockedOrder->materials()->firstOrCreate(
                        ['product_id' => $batch->product_id],
                        ['required_quantity' => 0, 'issued_quantity' => 0, 'unit' => $batch->product->unit],
                    );
                    $material->lots()->create([
                        'supplier_batch_id' => $batch->id,
                        'allocated_quantity' => $quantity,
                        'issued_quantity' => $quantity,
                        'issued_at' => now(),
                        'assigned_by' => auth()->id(),
                    ]);
                    $inventoryLedger->post(
                        $batch,
                        'EXPORT_PRODUCTION',
                        'out',
                        $quantity,
                        $material->unit,
                        ProductionOrder::class,
                        $lockedOrder->id,
                        auth()->id(),
                    );
                    $material->issued_quantity = (float) $material->issued_quantity + $quantity;
                }

                foreach ($materialSelected as $row) {
                    $catalogModel = $row['type'] === 'accessory' ? \App\Models\Accessory::class : \App\Models\RawMaterial::class;
                    $catalogItem = $catalogModel::whereKey($row['id'])->lockForUpdate()->first();
                    if (!$catalogItem) {
                        throw new DomainException('Có nguyên liệu/phụ liệu được chọn không tồn tại.');
                    }
                    $lotBalance = \App\Models\MaterialStockMovement::lotBalance($row['type'], $row['id'], $row['batch']);
                    if ($row['quantity'] > $lotBalance + 0.0001) {
                        throw new DomainException("Lô {$row['batch']} của {$catalogItem->name} không đủ tồn để xuất (tồn {$lotBalance}).");
                    }

                    $key = $row['type'] . '|' . $row['id'];
                    $material = $materials[$key] ??= $lockedOrder->materials()->firstOrCreate(
                        ['material_type' => $row['type'], 'material_id' => $row['id']],
                        ['required_quantity' => 0, 'issued_quantity' => 0, 'unit' => $catalogItem->unit ?: 'kg'],
                    );
                    $material->lots()->create([
                        'batch_number' => $row['batch'] !== '' ? $row['batch'] : null,
                        'allocated_quantity' => $row['quantity'],
                        'issued_quantity' => $row['quantity'],
                        'issued_at' => now(),
                        'assigned_by' => auth()->id(),
                    ]);
                    \App\Models\MaterialStockMovement::create([
                        'material_type' => $row['type'],
                        'material_id' => $row['id'],
                        'movement_type' => 'ISSUE_PRODUCTION',
                        'direction' => 'out',
                        'quantity' => $row['quantity'],
                        'unit' => $material->unit,
                        'batch_number' => $row['batch'] !== '' ? $row['batch'] : null,
                        'user_id' => auth()->id(),
                        'note' => 'Xuất sản xuất theo lệnh ' . $lockedOrder->id,
                    ]);
                    $material->issued_quantity = (float) $material->issued_quantity + $row['quantity'];
                }

                foreach ($materials as $material) {
                    $material->update([
                        'issued_quantity' => $material->issued_quantity,
                        'required_quantity' => $material->issued_quantity,
                    ]);
                }

                $lockedOrder->update([
                    'status' => 'materials_issued',
                    'materials_issued_at' => now(),
                ]);
                if ($lockedOrder->order_id) {
                    $lockedOrder->order()->update(['status' => 'processing']);
                }
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã xuất nguyên liệu theo lô và ghi nhận giao dịch EXPORT_PRODUCTION.');
    }
    public function confirmProductionCompletion(Request $request, ProductionOrder $productionOrder, InventoryLedger $inventoryLedger)
    {
        abort_unless($this->userCanWorkInWarehouse(), 403);

        if ($productionOrder->status !== 'production_reported') {
            return back()->with('error', 'Chỉ được nhập thành phẩm sau khi nguyên liệu đã xuất cho sản xuất.');
        }

        $rules = [
            'returned_materials' => 'nullable|array',
            'returned_materials.*' => 'nullable|numeric|min:0',
        ];
        $validated = $request->validate($rules);

        try {
            DB::transaction(function () use ($productionOrder, $validated, $inventoryLedger) {
                $lockedOrder = ProductionOrder::whereKey($productionOrder->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'production_reported') {
                    throw new DomainException('Lệnh sản xuất không còn chờ nhập thành phẩm.');
                }
                $lockedOrder->load(['materials.product', 'materials.lots']);
                $actualOutputQuantity = (float) $lockedOrder->actual_quantity;
                $isMonthlyPlanOrder = $lockedOrder->monthlyPlanLine()->exists();
                $stagedBatches = [];
                if (! $isMonthlyPlanOrder) {
                    $outputBatches = $lockedOrder->finishedBatches()
                        ->where('status', 'pending_qa')
                        ->where('pending_warehouse_quantity', '>', 0)
                        ->lockForUpdate()
                        ->get();
                    foreach ($outputBatches as $outputBatch) {
                        $stagedBatches[] = [
                            'batch' => $outputBatch,
                            'quantity' => (float) $outputBatch->pending_warehouse_quantity,
                        ];
                    }
                }

                $issuedLotIds = $lockedOrder->materials
                    ->flatMap(fn ($material) => $material->lots)
                    ->filter(fn ($allocation) => (float) $allocation->issued_quantity > 0)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $submittedReturnIds = array_map('intval', array_keys($validated['returned_materials'] ?? []));
                if (array_diff($submittedReturnIds, $issuedLotIds)) {
                    throw new DomainException('Phiếu trả có lô không thuộc nguyên liệu đã xuất của lệnh này.');
                }

                foreach ($lockedOrder->materials as $material) {
                    $consumedTotal = 0.0;
                    foreach ($material->lots as $allocation) {
                        if ((float) $allocation->issued_quantity <= 0) {
                            continue;
                        }
                        $lockedAllocation = ProductionMaterialLot::whereKey($allocation->id)->lockForUpdate()->firstOrFail();
                        $returnedQuantity = (float) ($validated['returned_materials'][$lockedAllocation->id] ?? 0);
                        if ($returnedQuantity > (float) $lockedAllocation->issued_quantity + 0.0001) {
                            throw new DomainException('Lượng nguyên liệu trả về không được vượt lượng đã xuất.');
                        }

                        $consumedQuantity = max(0, (float) $lockedAllocation->issued_quantity - $returnedQuantity);
                        $lockedAllocation->update(['returned_quantity' => $returnedQuantity]);
                        $consumedTotal += $consumedQuantity;

                        if ($returnedQuantity > 0) {
                            if (! $lockedAllocation->supplier_batch_id && $material->material_type) {
                                \App\Models\MaterialStockMovement::create([
                                    'material_type' => $material->material_type,
                                    'material_id' => $material->material_id,
                                    'movement_type' => 'RETURN_PRODUCTION',
                                    'direction' => 'in',
                                    'quantity' => $returnedQuantity,
                                    'unit' => $material->unit,
                                    'batch_number' => $lockedAllocation->batch_number,
                                    'user_id' => auth()->id(),
                                    'note' => 'Trả về kho từ lệnh sản xuất ' . $lockedOrder->id,
                                ]);
                            } else {
                                if (! $lockedAllocation->supplier_batch_id) {
                                    throw new DomainException('Chỉ nhận trả về đúng lô NCC đã xuất cho lệnh.');
                                }
                                $supplierSourceBatch = SupplierBatch::whereKey($lockedAllocation->supplier_batch_id)->lockForUpdate()->firstOrFail();
                                $inventoryLedger->post(
                                    $supplierSourceBatch,
                                    'RETURN_PRODUCTION',
                                    'in',
                                    $returnedQuantity,
                                    $material->unit,
                                    ProductionOrder::class,
                                    $lockedOrder->id,
                                    auth()->id(),
                                );
                            }
                        }

                        if ($consumedQuantity > 0) {
                            $consumedInputs[] = [
                                'production_material_lot_id' => $lockedAllocation->id,
                                'consumed_quantity' => $consumedQuantity,
                                'unit' => $material->unit,
                            ];
                        }
                    }
                    $material->update(['consumed_quantity' => $consumedTotal]);
                }

                $totalStagedQuantity = array_sum(array_column($stagedBatches, 'quantity'));
                if (! $isMonthlyPlanOrder && $actualOutputQuantity > 0 && $totalStagedQuantity > 0) {
                    foreach ($consumedInputs ?? [] as $input) {
                        $remainingInput = round((float) $input['consumed_quantity'] * $totalStagedQuantity / $actualOutputQuantity, 4);
                        foreach ($stagedBatches as $index => $stagedBatch) {
                            $isLastBatch = $index === count($stagedBatches) - 1;
                            $inputQuantity = $isLastBatch
                                ? $remainingInput
                                : min($remainingInput, round((float) $input['consumed_quantity'] * $stagedBatch['quantity'] / $actualOutputQuantity, 4));
                            if ($inputQuantity > 0) {
                                $stagedBatch['batch']->inputs()->create([
                                    'production_material_lot_id' => $input['production_material_lot_id'],
                                    'consumed_quantity' => $inputQuantity,
                                    'unit' => $input['unit'],
                                ]);
                            }
                            $remainingInput -= $inputQuantity;
                        }
                    }
                }

                $lockedOrder->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'warehouse_confirmed_at' => now(),
                    'warehouse_confirmed_by' => auth()->id(),
                ]);

                if ($lockedOrder->order_id) {
                    $order = Order::with('items.lotAllocations.finishedBatch')
                        ->whereKey($lockedOrder->order_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $order->update([
                        'status' => 'pending_qa',
                        'qa_confirmed_at' => null,
                    ]);
                }
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('warehouse.production-batches')->with('success', 'Đã ghi nhận sản lượng và tiêu hao; thành phẩm đang chờ Kho nhập tồn. QC có thể bổ sung PKN trước khi xuất hàng.');
    }
}
