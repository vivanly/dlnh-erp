<?php

namespace App\Http\Controllers;

use App\Models\Ppcb;
use App\Models\Product;
use App\Models\ProductionMonthlyPlan;
use App\Models\ProductionOrder;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionMonthlyPlanController extends Controller
{
    private function canManagePlans(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isPlanningDepartment') && $user->isPlanningDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    private function canViewPlans(): bool
    {
        $user = auth()->user();

        return (bool) $user;
    }

    private function canApprovePlans(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isGeneralDirector') && $user->isGeneralDirector()) ||
            ($user->position ?? null) === 'Giám Đốc' ||
            in_array($user->role ?? '', ['director', 'sales_director', 'general_director'], true)
        );
    }

    public function index()
    {
        abort_unless($this->canViewPlans(), 403);

        $plans = ProductionMonthlyPlan::with(['lines.product', 'lines.productionOrder', 'creator'])
            ->latest('plan_month')
            ->paginate(20);
        $canManagePlans = $this->canManagePlans();
        $ppcbs = $canManagePlans ? Ppcb::query()->orderBy('ma')->get(['id', 'ma', 'ten_ppcb']) : collect();
        $products = $canManagePlans
            ? Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'unit'])
            : collect();

        return view('production-monthly-plans.index', compact('plans', 'products', 'ppcbs', 'canManagePlans'));
    }

    public function edit(ProductionMonthlyPlan $plan)
    {
        abort_unless($this->canManagePlans(), 403);
        abort_unless(in_array($plan->status, ['draft', 'rejected'], true), 403);

        $plan->load('lines.product');
        $ppcbs = Ppcb::query()->orderBy('ma')->get(['id', 'ma', 'ten_ppcb']);
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'unit']);

        return view('production-monthly-plans.edit', compact('plan', 'products', 'ppcbs'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManagePlans(), 403);
        $validated = $this->validatePlan($request);

        try {
            DB::transaction(function () use ($validated) {
                $plan = ProductionMonthlyPlan::create([
                    'plan_month' => $validated['plan_month'].'-01',
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
                $this->syncLines($plan, $validated['lines']);
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('production-monthly-plans.index')->with('success', 'Đã lưu dự thảo kế hoạch sản xuất tháng.');
    }

    public function update(Request $request, ProductionMonthlyPlan $plan)
    {
        abort_unless($this->canManagePlans(), 403);
        abort_unless(in_array($plan->status, ['draft', 'rejected'], true), 403);
        $validated = $this->validatePlan($request, $plan);

        try {
            DB::transaction(function () use ($plan, $validated) {
                $lockedPlan = ProductionMonthlyPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
                if (! in_array($lockedPlan->status, ['draft', 'rejected'], true) || $lockedPlan->lines()->whereHas('productionOrder')->exists()) {
                    throw new DomainException('Kế hoạch đã được duyệt hoặc đã tạo lệnh sản xuất nên không thể sửa.');
                }
                $lockedPlan->update([
                    'plan_month' => $validated['plan_month'].'-01',
                    'status' => 'draft',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejection_reason' => null,
                ]);
                $this->syncLines($lockedPlan, $validated['lines']);
            });
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('production-monthly-plans.index')->with('success', 'Đã cập nhật dự thảo kế hoạch tháng.');
    }

    public function submit(ProductionMonthlyPlan $plan)
    {
        abort_unless($this->canManagePlans(), 403);

        try {
            DB::transaction(function () use ($plan) {
                $lockedPlan = ProductionMonthlyPlan::with('lines')
                    ->whereKey($plan->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if (! in_array($lockedPlan->status, ['draft', 'rejected'], true) || $lockedPlan->lines->isEmpty()) {
                    throw new DomainException('Chỉ gửi duyệt kế hoạch tháng có ít nhất một dòng ở trạng thái dự thảo.');
                }
                $lockedPlan->update(['status' => 'pending_approval', 'rejection_reason' => null]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã gửi kế hoạch tháng lên Ban Giám Đốc duyệt.');
    }

    public function approvals()
    {
        abort_unless(auth()->check(), 403);

        $plans = ProductionMonthlyPlan::with(['lines.product', 'lines.ppcb'])
            ->where('status', 'pending_approval')
            ->orderBy('plan_month')
            ->paginate(20);

        $canApprovePlans = $this->canApprovePlans();

        return view('production-monthly-plans.approvals', compact('plans', 'canApprovePlans'));
    }

    public function approve(ProductionMonthlyPlan $plan)
    {
        abort_unless($this->canApprovePlans(), 403);

        try {
            DB::transaction(function () use ($plan) {
                $lockedPlan = ProductionMonthlyPlan::with('lines.product')
                    ->whereKey($plan->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                if ($lockedPlan->status !== 'pending_approval' || $lockedPlan->lines->isEmpty()) {
                    throw new DomainException('Kế hoạch tháng không còn chờ duyệt hoặc chưa có dòng sản phẩm.');
                }

                foreach ($lockedPlan->lines as $line) {
                    if ($line->productionOrder()->exists()) {
                        throw new DomainException("Dòng {$line->product->name} đã có lệnh sản xuất.");
                    }

                    ProductionOrder::create([
                        'production_code' => 'MO-'.$lockedPlan->plan_month->format('Ym').'-MP'.$lockedPlan->id.'-'.$line->id,
                        'production_monthly_plan_line_id' => $line->id,
                        'product_id' => $line->product_id,
                        'planned_quantity' => $line->planned_quantity,
                        'unit' => $line->unit,
                        'yield_rate' => 1,
                        'status' => 'released',
                        'created_by' => $lockedPlan->created_by,
                        'notes' => $line->notes,
                    ]);
                }

                $lockedPlan->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã duyệt kế hoạch tháng và tạo các lệnh sản xuất độc lập với đơn bán.');
    }

    public function reject(Request $request, ProductionMonthlyPlan $plan)
    {
        abort_unless($this->canApprovePlans(), 403);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        try {
            DB::transaction(function () use ($plan, $validated) {
                $lockedPlan = ProductionMonthlyPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
                if ($lockedPlan->status !== 'pending_approval') {
                    throw new DomainException('Kế hoạch tháng không còn chờ duyệt.');
                }
                $lockedPlan->update([
                    'status' => 'rejected',
                    'rejection_reason' => trim($validated['reason']),
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã trả kế hoạch tháng cho Kế hoạch chỉnh sửa.');
    }

    private function validatePlan(Request $request, ?ProductionMonthlyPlan $plan = null): array
    {
        $validated = $request->validate([
            'plan_month' => ['required', 'date_format:Y-m'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.ppcb_id' => ['nullable', 'exists:ppcb,id'],
            'lines.*.planned_quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $productIds = array_map('intval', array_column($validated['lines'], 'product_id'));
        if (count($productIds) !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'lines' => 'Mỗi sản phẩm chỉ được xuất hiện một lần trong cùng kế hoạch tháng.',
            ]);
        }

        $month = $validated['plan_month'].'-01';
        $duplicate = ProductionMonthlyPlan::whereDate('plan_month', $month)
            ->when($plan, fn ($query) => $query->where('id', '!=', $plan->id))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages([
                'plan_month' => 'Đã có kế hoạch cho tháng này. Hãy mở kế hoạch hiện có để chỉnh sửa.',
            ]);
        }

        return $validated;
    }

    private function syncLines(ProductionMonthlyPlan $plan, array $lines): void
    {
        $plan->lines()->delete();
        $products = Product::whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');

        foreach ($lines as $line) {
            $product = $products->get((int) $line['product_id']);
            $plan->lines()->create([
                'product_id' => $product->id,
                'ppcb_id' => $line['ppcb_id'] ?? null,
                'planned_quantity' => $line['planned_quantity'],
                'unit' => $product->unit,
                'notes' => $line['notes'] ?? null,
            ]);
        }
    }
}
