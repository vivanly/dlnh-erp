<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use App\Models\Ppcb;
use App\Models\ProductionFinishedBatch;
use App\Models\ProductionMonthlyPlan;
use Carbon\Carbon;
use App\Models\ProductionMaterialLot;
use App\Models\SalesOrderLotAllocation;
use App\Models\SupplierBatch;
use App\Services\SalesOrderPlanner;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    private function isItOrAdmin($user): bool
    {
        return $user && (
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Order::with('customer', 'items.lotAllocations')->latest();

        $query = Order::with(['customer', 'items.lotAllocations', 'productionOrders'])->latest();
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.lotAllocations.supplierBatch', function ($sub) use ($search) {
                        $sub->where('batch_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('items.lotAllocations.finishedBatch', function ($sub) use ($search) {
                        $sub->where('batch_number', 'like', "%{$search}%");
                    });
            });
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('order_type') && in_array($request->input('order_type'), ['DL', 'VT', 'NL'], true)) {
            $query->where('order_type', $request->input('order_type'));
        }

        $orders = $query->paginate($this->perPage($request))->withQueryString();

        $canManageLockedOrders = $this->isItOrAdmin(auth()->user());
        $user = auth()->user();
        $canManageOrderDrafts = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            $this->isItOrAdmin($user)
        );

        return view('orders.index', compact('orders', 'canManageLockedOrders', 'canManageOrderDrafts'));
    }

    private function canApproveSalesOrder($user): bool
    {
        return $user && $user->canApproveSalesOrder();
    }

    public function salesApprovalQueue(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $orders = Order::with(['customer', 'items.product', 'items.rawMaterial'])
            ->where('status', 'pending_sales_approval')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $canApproveSalesOrders = $this->canApproveSalesOrder(auth()->user());

        return view('orders.sales-approval-queue', compact('orders', 'canApproveSalesOrders'));
    }

    public function approveSalesOrder(Order $order)
    {
        abort_unless($this->canApproveSalesOrder(auth()->user()), 403);

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_sales_approval') {
                    throw new DomainException('Đơn hàng không còn chờ Giám đốc Kinh doanh duyệt.');
                }
                if ($lockedOrder->items()->count() === 0) {
                    throw new DomainException('Không thể duyệt đơn hàng chưa có dòng sản phẩm.');
                }

                $lockedOrder->update([
                    'status' => $lockedOrder->order_type === 'NL' ? 'pending_material_issue' : 'pending_warehouse_check',
                    'sales_approved_at' => now(),
                    'sales_approved_by' => auth()->id(),
                    'sales_rejection_reason' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $order->order_type === 'NL'
            ? 'Đã duyệt đơn nguyên liệu thô; đơn được chuyển sang Kho xuất hàng.'
            : 'Đã duyệt đơn bán; đơn được chuyển sang Kho xác nhận tồn.');
    }

    public function rejectSalesOrder(Request $request, Order $order)
    {
        abort_unless($this->canApproveSalesOrder(auth()->user()), 403);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_sales_approval') {
                    throw new DomainException('Đơn hàng không còn chờ Giám đốc Kinh doanh duyệt.');
                }

                $lockedOrder->update([
                    'status' => 'sales_rejected',
                    'sales_rejection_reason' => trim($validated['reason']),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã từ chối đơn bán và lưu lý do.');
    }

    public function qaBatchQueue(Request $request)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $orders = Order::with(['customer', 'items.product', 'items.ppcb', 'warehouseStockCheckedBy'])
            ->where('status', 'pending_qa')
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

        $canManageQaLots = $user && ((method_exists($user, 'isQADepartment') && $user->isQADepartment()) || $this->isItOrAdmin($user));

        return view('qa.order-batches', compact('orders', 'canManageQaLots'));
    }

    public function productionPlanningIndex(Request $request)
    {
        $user = auth()->user();
        $canPlan = $user && (
            (method_exists($user, 'isPlanningDepartment') && $user->isPlanningDepartment()) ||
            $this->isItOrAdmin($user)
        );
        abort_unless($user, 403);

        $orders = Order::with(['customer', 'items.product', 'items.lotAllocations'])
            ->where('status', 'pending_planning')
            ->whereNotNull('qa_confirmed_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $orders->getCollection()->each(function (Order $order) {
            foreach ($order->items as $item) {
                $selectedQuantity = (float) $item->lotAllocations
                    ->where('status', 'reserved')
                    ->sum(fn ($allocation) => max(0, (float) $allocation->reserved_quantity - (float) $allocation->shipped_quantity));
                $item->setAttribute('qa_selected_quantity', $selectedQuantity);
                $item->setAttribute('production_shortage', max(0, (float) $item->quantity - $selectedQuantity));
            }
        });

        return view('production-planning.index', compact('orders', 'canPlan'));
    }

    public function productionPlanningHistory(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
        $month = $validated['month'] ?? null;
        $status = $validated['status'] ?? null;
        $start = $month ? Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay() : null;

        $orders = Order::with(['customer', 'productionOrders.product.ppcb', 'productionOrders.orderItem.ppcb'])
            ->whereHas('productionOrders', function ($query) use ($start) {
                if ($start) {
                    $query->whereBetween('created_at', [$start, $start->copy()->endOfMonth()]);
                }
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($this->perPage($request), ['*'], 'orders_page')
            ->withQueryString();

        $monthlyPlans = ProductionMonthlyPlan::with(['lines.product.ppcb', 'lines.ppcb'])
            ->when($start, fn ($query) => $query->whereDate('plan_month', $start->toDateString()))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('plan_month')
            ->paginate(20, ['*'], 'monthly_page')
            ->withQueryString();

        return view('production-planning.history', compact('orders', 'monthlyPlans', 'month', 'status'));
    }

    private function canApproveProductionPlan($user): bool
    {
        return $user && $user->canApproveProductionPlan();
    }

    public function productionApprovalQueue(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $orders = Order::with(['customer', 'items.product', 'productionOrders.product', 'productionOrders.materials.product'])
            ->where('status', 'pending_production_approval')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('order_code', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        $canApproveProductionPlan = $this->canApproveProductionPlan(auth()->user());

        return view('production-planning.approval-queue', compact('orders', 'canApproveProductionPlan'));
    }

    public function approveProductionPlan(Order $order)
    {
        abort_unless($this->canApproveProductionPlan(auth()->user()), 403);

        try {
            DB::transaction(function () use ($order) {
                $lockedOrder = Order::with('productionOrders')->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_production_approval' || $lockedOrder->productionOrders->isEmpty()) {
                    throw new DomainException('Kế hoạch không còn chờ giám đốc duyệt hoặc chưa có lệnh sản xuất.');
                }
                if ($lockedOrder->productionOrders->contains(fn ($productionOrder) => $productionOrder->status !== 'pending_director_approval')) {
                    throw new DomainException('Các lệnh sản xuất trong kế hoạch đã thay đổi; không thể duyệt.');
                }

                foreach ($lockedOrder->productionOrders as $productionOrder) {
                    $productionOrder->update(['status' => 'released']);
                }
                $lockedOrder->update([
                    'status' => 'waiting_production',
                    'production_plan_approved_at' => now(),
                    'production_plan_approved_by' => auth()->id(),
                    'production_plan_rejection_reason' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã duyệt kế hoạch; Kho có thể lập phiếu xuất nguyên liệu FIFO.');
    }

    public function rejectProductionPlan(Request $request, Order $order)
    {
        abort_unless($this->canApproveProductionPlan(auth()->user()), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::with('productionOrders')->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($lockedOrder->status !== 'pending_production_approval') {
                    throw new DomainException('Kế hoạch không còn chờ duyệt.');
                }
                $lockedOrder->productionOrders()->where('status', 'pending_director_approval')->delete();
                $lockedOrder->update([
                    'status' => 'pending_planning',
                    'production_plan_approved_at' => null,
                    'production_plan_approved_by' => null,
                    'production_plan_rejection_reason' => trim($validated['reason']),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã trả kế hoạch về Kế hoạch để điều chỉnh.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        abort_unless($user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            $this->isItOrAdmin($user)
        ), 403);

        // Gợi ý mã đơn hàng tự động theo định dạng ĐH-YYYYMMDD-XXXX
        $orderType = request('order_type', 'DL');
        $orderDate = request('order_date', now()->toDateString());
        $suggestedCode = $this->suggestOrderCode($orderType, $orderDate);

        return view('orders.create', compact('suggestedCode', 'orderType', 'orderDate'));
    }

    public function suggestedCode(Request $request)
    {
        $validated = $request->validate([
            'order_type' => ['required', 'in:DL,VT,NL'],
            'order_date' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json(['code' => $this->suggestOrderCode($validated['order_type'], $validated['order_date'])]);
    }

    private function suggestOrderCode(string $orderType, string $orderDate): string
    {
        abort_unless(in_array($orderType, ['DL', 'VT', 'NL'], true), 422);
        $prefix = $orderType.'-'.Carbon::parse($orderDate)->format('Ymd').'-';
        $next = Order::where('order_code', 'like', $prefix.'%')
            ->pluck('order_code')
            ->map(fn ($code) => preg_match('/^'.preg_quote($prefix, '/').'([0-9]+)$/', $code, $matches) ? (int) $matches[1] : 0)
            ->max() + 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created resource in storage.
     */
    private function normalizeSaleItems(Request $request): void
    {
        $items = collect($request->input('items', []))->map(function ($item) {
            if (is_array($item) && empty($item['item_id']) && !empty($item['product_id'])) {
                $item['item_id'] = $item['product_id'];
            }

            return $item;
        })->all();

        $request->merge(['items' => $items]);
    }

    public function store(Request $request)
    {
        $this->normalizeSaleItems($request);
        $isRawMaterialOrder = $request->input('order_type') === 'NL';

        $validated = $request->validate([
            'order_code' => 'required|unique:orders,order_code',
            'customer_id' => [
                'required',
                'exists:customers,id',
                function ($attribute, $value, $fail) {
                    if (! Customer::whereKey($value)->where('is_active', true)->exists()) {
                        $fail('Không thể tạo đơn mới cho khách hàng đang không hoạt động.');
                    }
                },
            ],
            'order_type' => 'required|string',
            'order_date' => 'required|date',
            'delivery_date' => 'required|date',
            'province_city' => 'required|string',
            'contact_person' => 'nullable|string|max:255',
            'notes' => 'nullable|string',

            // Validate cấu trúc mảng items gửi từ form tạo đơn
            'items' => 'required|array|min:1',
            'items.*.item_id' => ['required', 'integer', $isRawMaterialOrder ? 'exists:raw_materials,id' : 'exists:products,id'],
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.packaging_spec' => 'nullable|string|max:255',
            'items.*.finished_quantity' => 'nullable|numeric|min:0',
            'items.*.ppcb_id' => 'nullable|exists:ppcb,id',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        abort_unless($user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            $this->isItOrAdmin($user)
        ), 403);

        $validated['status'] = 'pending_sales_approval';

        DB::beginTransaction();
        try {
            // Tạo đơn hàng chính
            $order = Order::create([
                'order_code' => $validated['order_code'],
                'customer_id' => $validated['customer_id'],
                'order_type' => $validated['order_type'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'],
                'province_city' => $validated['province_city'],
                'contact_person' => $validated['contact_person'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
            ]);

            // Tạo các dòng vị thuốc dược liệu chi tiết
            foreach ($validated['items'] as $itemData) {
                $itemId = $itemData['item_id'];
                unset($itemData['item_id']);
                $order->items()->create($itemData + ($isRawMaterialOrder
                    ? ['raw_material_id' => $itemId, 'ppcb_id' => null]
                    : ['product_id' => $itemId]));
            }

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Đã tạo đơn hàng; đơn đang chờ Giám đốc Kinh doanh duyệt.');
        } catch (DomainException $e) {
            DB::rollBack();

            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo đơn hàng: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = Order::with(['items.product', 'items.rawMaterial', 'items.ppcb', 'items.lotAllocations.supplierBatch', 'items.lotAllocations.finishedBatch', 'customer', 'warehouseStockCheckedBy'])->findOrFail($id);
        $canManageLockedOrders = $this->isItOrAdmin(auth()->user());
        $user = auth()->user();
        $canManageOrderLots = $user && method_exists($user, 'isQADepartment') && $user->isQADepartment();

        return view('orders.show', compact('order', 'canManageLockedOrders', 'canManageOrderLots'));
    }

    public function sendToProduction(Request $request, Order $order, SalesOrderPlanner $planner)
    {
        $user = auth()->user();
        $canSendToProduction = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            (method_exists($user, 'isPlanningDepartment') && $user->isPlanningDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );

        abort_unless($canSendToProduction, 403);
        $validated = $request->validate([
            'planned_quantities' => 'nullable|array',
            'planned_quantities.*' => 'nullable|numeric|min:0',
        ]);

        try {
            $hasProductionShortage = $planner->plan($order, $user->id, $validated['planned_quantities'] ?? []);
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $order->refresh();
        $message = match (true) {
            $hasProductionShortage => 'Đã kiểm tồn theo các lô QA chọn và lập lệnh sản xuất cho phần còn thiếu.',
            $order->status === 'waiting_finished_goods_receipt' => 'QA đã phân bổ lô sản xuất vào đơn; đơn chờ Kho nhập đủ số lượng lô trước khi đóng gói.',
            default => 'Các lô QA chọn đã đủ số lượng; đơn sẵn sàng chuyển Kho đóng hàng.',
        };

        return back()->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $order = Order::with('items.product', 'customer', 'items.ppcb', 'items.lotAllocations')->findOrFail($id);
        if ($order->order_type === 'NL') {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn nguyên liệu thô chưa hỗ trợ chỉnh sửa; hãy xóa và tạo lại đơn.');
        }
        if ($order->isEditLockedAfterQa()) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã được QA chốt số lô, không thể chỉnh sửa.');
        }
        $user = auth()->user();
        $isQA = $user && method_exists($user, 'isQADepartment') && $user->isQADepartment();
        $isSales = $user && method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment();
        $canManageQaLots = ($isQA || $this->isItOrAdmin($user)) && $order->status === 'pending_qa';
        $canEditDraft = ($isSales || $this->isItOrAdmin($user))
            && in_array($order->status, ['draft', 'sales_rejected'], true);
        abort_unless($canManageQaLots || $canEditDraft || $this->isItOrAdmin($user), 403);

        if (in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) && ! $this->isItOrAdmin($user)) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được chỉnh sửa.');
        }

        // Lấy danh sách PPCB/YCBC để truyền sang form
        $ppcbList = Ppcb::all();
        $finishedBatchesByProduct = ProductionFinishedBatch::query()
            ->whereIn('product_id', $order->items->pluck('product_id'))
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->get()
            ->groupBy('product_id');
        $qaLotsByProduct = [];
        if ($canManageQaLots) {
            $lotSources = $order->order_type === 'DL'
                ? [
                    'supplier' => [SupplierBatch::class, 'supplier_batch_id', 'batch_number'],
                    'finished' => [ProductionFinishedBatch::class, 'production_finished_batch_id', 'batch_number'],
                ]
                : ['finished' => [ProductionFinishedBatch::class, 'production_finished_batch_id', 'batch_number']];

            foreach ($order->items as $item) {
                $qaLotsByProduct[$item->id] = collect();
                foreach ($lotSources as $lotType => [$model, $allocationColumn, $codeColumn]) {
                    $batches = $model::query()
                        ->when($model === ProductionFinishedBatch::class, fn ($query) => $query->with('ppcb'))
                        ->where('product_id', $item->product_id)
                        ->when($model === ProductionFinishedBatch::class, fn ($query) => $query->where(function ($query) {
                            $query->where(function ($activeQuery) {
                                $activeQuery->where('status', 'active')->where('current_quantity', '>', 0);
                            })->orWhere(function ($pendingQuery) {
                                $pendingQuery->where('status', 'pending_qa')
                                    ->where('pending_warehouse_quantity', '>', 0);
                            });
                        }), fn ($query) => $query->where('status', 'active')->where('current_quantity', '>', 0))
                        ->when($model === ProductionFinishedBatch::class, fn ($query) => $query->where(function ($qcQuery) {
                            $qcQuery->whereNull('qc_result')->orWhere('qc_result', '!=', 'failed');
                        }))
                        ->where(function ($query) {
                            $query->whereNull('exp_date')->orWhereDate('exp_date', '>=', today());
                        })
                        ->orderBy('exp_date')
                        ->limit(50)
                        ->get();

                    foreach ($batches as $batch) {
                        $lotQuantity = (float) $batch->current_quantity
                            + ($batch instanceof ProductionFinishedBatch ? (float) $batch->pending_warehouse_quantity : 0);
                        $reservedQuantity = $item->lotAllocations
                            ->filter(fn ($allocation) => $allocation->status === 'reserved'
                                && (int) $allocation->{$allocationColumn} === (int) $batch->id)
                            ->sum(fn ($allocation) => (float) $allocation->reserved_quantity);
                        $otherSalesReservations = (float) SalesOrderLotAllocation::query()
                            ->where($allocationColumn, $batch->id)
                            ->where('order_item_id', '!=', $item->id)
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
                        $qaLotsByProduct[$item->id]->push([
                            'lot' => $lotType.':'.$batch->id,
                            'code' => $batch->{$codeColumn},
                            'ppcb' => $batch instanceof ProductionFinishedBatch
                                ? ($batch->ppcb ? $batch->ppcb->ma.' · '.$batch->ppcb->ten_ppcb : 'Chưa khai báo')
                                : null,
                            'edit_url' => $batch instanceof ProductionFinishedBatch
                                ? route('qa.internal-lots.edit', $batch)
                                : null,
                            'current_quantity' => $lotQuantity,
                            'available_quantity' => max(0, $lotQuantity - $otherSalesReservations - $productionReservations),
                            'pending_quantity' => $batch instanceof ProductionFinishedBatch ? (float) $batch->pending_warehouse_quantity : 0,
                            'reserved_quantity' => $reservedQuantity,
                        ]);
                    }
                }
            }
        }

        return view('orders.edit', compact('order', 'ppcbList', 'finishedBatchesByProduct', 'canManageQaLots', 'qaLotsByProduct'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        if ($order->order_type === 'NL') {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn nguyên liệu thô chưa hỗ trợ chỉnh sửa; hãy xóa và tạo lại đơn.');
        }
        if ($order->isEditLockedAfterQa()) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã được QA chốt số lô, không thể chỉnh sửa.');
        }
        $user = auth()->user();

        $isItOrAdmin = $this->isItOrAdmin($user);
        $isQA = $user && method_exists($user, 'isQADepartment') && $user->isQADepartment();
        $isSales = $user && method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment();
        $canManageQaLots = ($isQA || $isItOrAdmin) && $order->status === 'pending_qa';
        $canEditDraft = ($isSales || $isItOrAdmin) && in_array($order->status, ['draft', 'sales_rejected'], true);
        abort_unless($canManageQaLots || $canEditDraft || $isItOrAdmin, 403);

        if (in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) && ! $isItOrAdmin && ! $canManageQaLots) {
            return redirect()->route('orders.show', $order->id)->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được chỉnh sửa.');
        }

        $salesResubmission = $order->status === 'sales_rejected'
            && $user
            && (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment());

        $canManageBatch = $isItOrAdmin || $isQA;

        DB::beginTransaction();
        try {
            // ==========================================
            // LUỒNG 1: TÀI KHOẢN LÀ QA (Chỉ cập nhật số lô)
            // ==========================================
            if ($canManageQaLots) {
                $validatedQA = $request->validate([
                    'items' => 'nullable|array',
                    'items.*.id' => 'required|exists:order_items,id',
                    'items.*.ppcb_id' => 'nullable|exists:ppcb,id',
                    'items.*.allocations' => 'nullable|array',
                    'items.*.allocations.*.lot' => 'required|string',
                ]);

                if (! $canManageQaLots) {
                    throw new DomainException('QA chỉ được chốt lô khi đơn đang chờ QA.');
                }

                $lockedOrder = Order::with('items.lotAllocations')
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($lockedOrder->status !== 'pending_qa') {
                    throw new DomainException('Đơn hàng không còn chờ QA chốt lô.');
                }
                if (count($validatedQA['items'] ?? []) !== $lockedOrder->items->count()) {
                    throw new DomainException('QA cần chốt số lô cho tất cả dòng sản phẩm.');
                }

                foreach ($lockedOrder->items as $item) {
                    $itemData = collect($validatedQA['items'])->firstWhere('id', $item->id);
                    if (! $itemData) {
                        throw new DomainException('Danh sách sản phẩm cần QA chốt lô không hợp lệ.');
                    }

                    if (array_key_exists('ppcb_id', $itemData)) {
                        $item->update(['ppcb_id' => $itemData['ppcb_id'] ?: null]);
                    }

                    $this->assignQaSalesOrderLots($lockedOrder, $item, $itemData['allocations'] ?? []);
                }

                $lockedOrder->update([
                    'qa_confirmed_at' => now(),
                    'status' => 'pending_planning',
                ]);
                DB::commit();

                return redirect()->route('orders.show', $order->id)
                    ->with('success', 'QA đã chốt lô xuất bán cho toàn bộ sản phẩm.');
            }

            // ==========================================
            // LUỒNG 2: ADMIN / IT / SALES (Cập nhật toàn bộ)
            // ==========================================
            $validated = $request->validate([
                'order_code' => 'required|unique:orders,order_code,'.$order->id,
                'customer_id' => [
                    'required',
                    'exists:customers,id',
                    function ($attribute, $value, $fail) use ($order) {
                        $isSameCustomer = (int) $value === (int) $order->customer_id;
                        if (! $isSameCustomer && ! Customer::whereKey($value)->where('is_active', true)->exists()) {
                            $fail('Không thể chuyển đơn sang khách hàng đang không hoạt động.');
                        }
                    },
                ],
                'order_type' => 'required|string',
                'order_date' => 'required|date',
                'delivery_date' => 'required|date',
                'province_city' => 'required|string',
                'contact_person' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'status' => 'required|string',

                'items' => 'nullable|array',
                'items.*.id' => 'nullable|exists:order_items,id',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.packaging_spec' => 'required|numeric|min:0',
                'items.*.finished_quantity' => 'nullable|numeric|min:0',
                'items.*.ppcb_id' => 'nullable|exists:ppcb,id',
                'items.*.notes' => 'nullable|string|max:500',
            ]);

            // 1. Cập nhật thông tin chung đơn hàng
            $order->update([
                'order_code' => $validated['order_code'],
                'customer_id' => $validated['customer_id'],
                'order_type' => $validated['order_type'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'],
                'province_city' => $validated['province_city'],
                'contact_person' => $validated['contact_person'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => $salesResubmission ? 'pending_sales_approval' : $validated['status'],
                'sales_rejection_reason' => $salesResubmission ? null : $order->sales_rejection_reason,
            ]);

            // 2. Xử lý cập nhật / thêm mới danh sách vị thuốc (items)
            $submittedItemIds = [];

            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    $payload = [
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'packaging_spec' => $itemData['packaging_spec'],
                        'finished_quantity' => $itemData['finished_quantity'],
                        'ppcb_id' => $itemData['ppcb_id'] ?? null,
                        'notes' => $itemData['notes'] ?? null,
                    ];

                    if (! empty($itemData['id'])) {
                        $item = $order->items()->where('id', $itemData['id'])->first();
                        if ($item) {
                            $item->update($payload);
                            $submittedItemIds[] = $item->id;
                        }
                    } else {
                        $newItem = $order->items()->create($payload);
                        $submittedItemIds[] = $newItem->id;
                    }
                }
            }

            // 3. Xóa các dòng item cũ bị loại bỏ trên giao diện
            $removedItems = $order->items()->whereNotIn('id', $submittedItemIds)->get();
            foreach ($removedItems as $removedItem) {
                if ($removedItem->labelPrints()->exists()) {
                    throw new DomainException('Không thể xóa dòng đơn đã có lịch sử in nhãn.');
                }
                $removedItem->delete();
            }
            $order->update(['qa_confirmed_at' => null]);

            DB::commit();

            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Cập nhật thông tin đơn hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    private function assignQaSalesOrderLots(Order $order, $item, array $allocationRows): void
    {
        if ((float) $item->actual_quantity > 0) {
            throw new DomainException("Không thể đổi lô {$item->product->name} sau khi đã giao một phần.");
        }

        $definitions = $order->order_type === 'DL'
            ? [
                'supplier' => [SupplierBatch::class, 'supplier_batch_id'],
                'finished' => [ProductionFinishedBatch::class, 'production_finished_batch_id'],
            ]
            : ['finished' => [ProductionFinishedBatch::class, 'production_finished_batch_id']];
        $newAllocations = [];
        $reservedByLot = [];
        $allocatedQuantity = 0.0;
        $remainingDemand = (float) $item->quantity;

        foreach ($allocationRows as $row) {
            if ($remainingDemand <= 0.0001) {
                continue;
            }

            [$type, $id] = array_pad(explode(':', $row['lot'], 2), 2, null);
            if (! isset($definitions[$type]) || ! ctype_digit((string) $id)) {
                throw new DomainException("Số lô chọn cho {$item->product->name} không hợp lệ.");
            }

            [$model, $allocationColumn] = $definitions[$type];
            $batchQuery = $model::query()
                ->whereKey($id)
                ->where('product_id', $item->product_id)
                ->when($model === ProductionFinishedBatch::class, fn ($query) => $query->where(function ($query) {
                    $query->where('status', 'active')
                        ->orWhere(function ($pendingQuery) {
                            $pendingQuery->where('status', 'pending_qa')
                                ->where('pending_warehouse_quantity', '>', 0);
                        });
                }), fn ($query) => $query->where('status', 'active'))
                ->where(function ($query) {
                    $query->whereNull('exp_date')->orWhereDate('exp_date', '>=', today());
                })
                ->when($model === ProductionFinishedBatch::class, fn ($query) => $query->where(function ($qcQuery) {
                    $qcQuery->whereNull('qc_result')->orWhere('qc_result', '!=', 'failed');
                }))
                ->lockForUpdate();
            $batch = $batchQuery->first();

            if (! $batch) {
                throw new DomainException("Lô chọn cho {$item->product->name} không còn hoạt động, đã hết hạn hoặc không đúng sản phẩm.");
            }

            if ($batch instanceof ProductionFinishedBatch
                && (int) ($batch->ppcb_id ?? 0) !== (int) ($item->ppcb_id ?? 0)) {
                throw new DomainException(
                    "PPCB đơn hàng của {$item->product->name} không khớp PPCB lô {$batch->batch_number}. "
                    .'Hãy sửa PPCB đơn hàng hoặc PPCB lô nội bộ trước khi chốt.'
                );
            }

            $key = $type.':'.$batch->id;
            $salesReservations = (float) SalesOrderLotAllocation::query()
                ->where($allocationColumn, $batch->id)
                ->where('order_item_id', '!=', $item->id)
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

            $batchQuantity = (float) $batch->current_quantity
                + ($batch instanceof ProductionFinishedBatch ? (float) $batch->pending_warehouse_quantity : 0);
            $availableQuantity = max(0, $batchQuantity - $salesReservations - $productionReservations);
            $quantity = min($remainingDemand, max(0, $availableQuantity - ($reservedByLot[$key] ?? 0)));
            if ($quantity <= 0.0001) {
                throw new DomainException("Lô {$batch->batch_number} không còn lượng khả dụng để phân bổ.");
            }
            $reservedByLot[$key] = ($reservedByLot[$key] ?? 0) + $quantity;
            if ($reservedByLot[$key] > $availableQuantity + 0.0001) {
                throw new DomainException("Lô {$batch->batch_number} không đủ tồn khả dụng cho {$item->product->name}.");
            }

            $newAllocations[] = [
                'supplier_batch_id' => $type === 'supplier' ? $batch->id : null,
                'production_finished_batch_id' => $type === 'finished' ? $batch->id : null,
                'reserved_quantity' => $quantity,
                'status' => 'reserved',
            ];
            $allocatedQuantity += $quantity;
            $remainingDemand -= $quantity;
        }

        if ($allocatedQuantity - (float) $item->quantity > 0.0001) {
            throw new DomainException("Tổng số lượng lô đã chọn cho {$item->product->name} không được vượt số lượng đơn {$item->quantity}.");
        }

        if ($item->lotAllocations()->where('status', 'reserved')->whereHas('labelPrints')->exists()) {
            throw new DomainException("Đã in nhãn cho {$item->product->name}; không thể đổi lô để bảo toàn lịch sử tem.");
        }

        $item->lotAllocations()->where('status', 'reserved')->update([
            'status' => 'replaced',
        ]);
        foreach ($newAllocations as $allocation) {
            $item->lotAllocations()->create($allocation);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);
        $user = auth()->user();
        $canManageDraftOrder = $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            $this->isItOrAdmin($user)
        );
        abort_unless($canManageDraftOrder, 403);
        if ($order->isApproved()) {
            return redirect()->route('orders.index')->with('error', 'Đơn hàng đã được duyệt, không thể xóa.');
        }
        if ($order->items()->whereHas('labelPrints')->exists()) {
            return redirect()->route('orders.index')->with('error', 'Đơn đã có lịch sử in nhãn, không thể xóa để bảo toàn đối soát tem.');
        }
        if (in_array($order->status, ['pending_sales_approval', 'pending_warehouse_check', 'pending_planning', 'waiting_finished_goods_receipt', 'waiting_production', 'processing', 'ready_to_ship', 'completed'], true) && ! $this->isItOrAdmin(auth()->user())) {
            return redirect()->route('orders.index')->with('error', 'Đơn đã gửi sản xuất, chỉ IT mới được xóa.');
        }

        DB::beginTransaction();
        try {
            $order->items()->delete();
            $order->delete();

            DB::commit();

            return redirect()->route('orders.index')->with('success', 'Đã xóa đơn hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Không thể xóa đơn hàng: '.$e->getMessage());
        }
    }
}
