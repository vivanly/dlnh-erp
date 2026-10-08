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
            ->whereIn('status', ['pending_director_approval', 'released', 'materials_issued'])
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

        $batches = ProductionFinishedBatch::with(['product', 'productionOrder.order', 'ppcb'])
            ->where('status', 'active')
            ->whereNotNull('warehouse_received_at')
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

        return view('qa.internal-lots.create', compact('products', 'ppcbs', 'sourceProductionOrder'));
    }

    public function internalLotsIndex(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $canManageLots = $this->userCanManageBom();

        $batches = ProductionFinishedBatch::with(['product', 'productionOrder', 'ppcb'])
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
                        ->orWhereHas('productionOrder', fn ($order) => $order->where('production_code', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        foreach ($batches as $batch) {
            $batch->setAttribute('can_delete', $this->canDeleteInternalLot($batch));
        }

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

        return view('qa.internal-lots.index', compact('batches', 'lotsToClose', 'canManageLots'));
    }

    public function editInternalLot(ProductionFinishedBatch $productionFinishedBatch)
    {
        abort_unless($this->userCanManageBom(), 403);

        $productionFinishedBatch->load(['product', 'productionOrder']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'unit', 'classification', 'origin']);
        $ppcbs = Ppcb::query()->orderBy('ten_ppcb')->get(['id', 'ma', 'ten_ppcb']);
        $canEditDefinition = $this->canEditInternalLotDefinition($productionFinishedBatch);

        return view('qa.internal-lots.edit', compact('productionFinishedBatch', 'products', 'ppcbs', 'canEditDefinition'));
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
        ]);

        try {
            DB::transaction(function () use ($productionFinishedBatch, $validated) {
                $batch = ProductionFinishedBatch::whereKey($productionFinishedBatch->id)->lockForUpdate()->firstOrFail();
                $canEditDefinition = $this->canEditInternalLotDefinition($batch);
                $newCode = trim($validated['batch_number']);
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
            && ! $batch->warehouse_received_at
            && (float) $batch->current_quantity <= 0.000001
            && ! $batch->salesOrderAllocations()->exists()
            && ! $batch->inventoryMovements()->exists()
            && ! $batch->inputs()->exists();
    }

    private function canDeleteInternalLot(ProductionFinishedBatch $batch): bool
    {
        return ! $batch->production_order_id
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
            'production_order_id' => ['prohibited'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $product = Product::findOrFail($validated['product_id']);
                $capacity = (float) $validated['planned_quantity'];
                ProductionFinishedBatch::create([
                    'product_id' => $product->id,
                    'origin' => $product->origin,
                    'ppcb_id' => $validated['ppcb_id'] ?? null,
                    'license_number' => $validated['license_number'] ?? null,
                    'batch_number' => trim($validated['batch_number']),
                    'provisional_batch_number' => trim($validated['batch_number']),
                    'planned_quantity' => $capacity,
                    'initial_quantity' => 0,
                    'current_quantity' => 0,
                    'pending_warehouse_quantity' => $capacity,
                    'unit' => $product->unit,
                    'mfg_date' => $validated['mfg_date'] ?? null,
                    'exp_date' => $validated['exp_date'] ?? null,
                    'status' => 'pending_qa',
                ]);
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('qa.internal-lots.index')
            ->with('success', 'Đã tạo mã lô độc lập với sản lượng/lệnh sản xuất. QA có thể phân bổ lượng dự kiến ngay; Kho nhập số lượng thực tế theo từng lần, không vượt lượng dự kiến.');
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

    public function show(ProductionOrder $productionOrder, SupplierBatchAvailability $availability)
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
            'monthlyPlanLine.plan',
        ]);

        $supplierBatches = SupplierBatch::with('product')
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where(function ($query) {
                $query->whereNull('exp_date')->orWhereDate('exp_date', '>=', today());
            })
            ->orderByRaw('CASE WHEN exp_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('exp_date')
            ->orderBy('id')
            ->get();
        $availability->addTo($supplierBatches);
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
            'supplierBatches' => $supplierBatches,
            'materialLots' => $materialLots,
            'canIssueMaterials' => $this->userCanWorkInWarehouse(),
            'canReceiveFinishedBatch' => $this->userCanWorkInWarehouse(),
            'canManageLots' => $this->userCanManageBom(),
            'canWorkInProduction' => $this->userCanWorkInProduction(),
        ]);
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
    public function receiveFinishedBatch(Request $request, ProductionOrder $productionOrder, InventoryLedger $inventoryLedger)
    {
        abort_unless($this->userCanWorkInWarehouse(), 403);

        if ($productionOrder->status !== 'materials_issued') {
            return back()->with('error', 'Chỉ được nhập thành phẩm sau khi nguyên liệu đã xuất cho sản xuất.');
        }

        $rules = [
            'returned_materials' => 'nullable|array',
            'returned_materials.*' => 'nullable|numeric|min:0',
            'actual_quantity' => ['required', 'numeric', 'min:0.0001'],
            'mfg_date' => 'nullable|date',
        ];
        $validated = $request->validate($rules);

        try {
            DB::transaction(function () use ($productionOrder, $validated, $inventoryLedger) {
                $lockedOrder = ProductionOrder::whereKey($productionOrder->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'materials_issued') {
                    throw new DomainException('Lệnh sản xuất không còn chờ nhập thành phẩm.');
                }
                $lockedOrder->load(['materials.product', 'materials.lots']);
                $actualOutputQuantity = (float) $validated['actual_quantity'];
                $remainingOutput = $actualOutputQuantity;
                $stagedBatches = [];
                $isMonthlyPlanOrder = $lockedOrder->monthlyPlanLine()->exists();
                if (! $isMonthlyPlanOrder) {
                    $outputBatches = $lockedOrder->finishedBatches()
                        ->where('status', 'planned')
                        ->lockForUpdate()
                        ->get();
                    foreach ($outputBatches as $outputBatch) {
                        $capacity = max(0, (float) $outputBatch->planned_quantity
                            - (float) $outputBatch->initial_quantity);
                        $stagedQuantity = min($remainingOutput, $capacity);
                        if ($stagedQuantity <= 0) {
                            continue;
                        }

                        $outputBatch->update([
                            'initial_quantity' => (float) $outputBatch->initial_quantity + $stagedQuantity,
                            'pending_warehouse_quantity' => (float) $outputBatch->pending_warehouse_quantity + $stagedQuantity,
                            'mfg_date' => $validated['mfg_date'] ?? today(),
                            'status' => 'pending_qa',
                            'received_by' => auth()->id(),
                        ]);
                        $stagedBatches[] = ['batch' => $outputBatch, 'quantity' => $stagedQuantity];
                        $remainingOutput -= $stagedQuantity;
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
                    'actual_quantity' => $actualOutputQuantity,
                    'pending_finished_quantity' => $remainingOutput,
                    'status' => 'completed',
                    'completed_at' => now(),
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
