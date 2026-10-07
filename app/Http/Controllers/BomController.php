<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBom;
use App\Models\ProductUnitConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\BomMaterialCalculator;
use DomainException;

class BomController extends Controller
{
    private function ensureBomAccess(): void
    {
        $user = auth()->user();
        $allowed = $user && (
            (method_exists($user, 'isPlanningDepartment') && $user->isPlanningDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );

        abort_unless($allowed, 403);
    }

    private function ensureBomViewAccess(): void
    {
        abort_unless(auth()->check(), 403);
    }

    private function ensureBomApprovalAccess(): void
    {
        abort_unless($this->canApproveBom(auth()->user()), 403);
    }

    private function canApproveBom($user): bool
    {
        return $user && (
            (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
            (method_exists($user, 'isGeneralDirector') && $user->isGeneralDirector()) ||
            (($user->position ?? '') === 'Giám Đốc') ||
            in_array($user->role ?? '', ['director', 'general_director'], true)
        );
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $canManageBoms = $user && (
            (method_exists($user, 'isPlanningDepartment') && $user->isPlanningDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
        $canApproveBoms = $this->canApproveBom($user);
        $this->ensureBomViewAccess();

        $boms = ProductBom::with(['product', 'items.componentProduct', 'submittedBy', 'approvedBy'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->input('search'));
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('product_id')
            ->orderByDesc('version')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('boms.index', compact('boms', 'canManageBoms', 'canApproveBoms'));
    }

    public function create()
    {
        $this->ensureBomAccess();

        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'unit', 'classification']);

        return view('boms.create', compact('products'));
    }

    public function plan(Request $request, BomMaterialCalculator $calculator)
    {
        $this->ensureBomViewAccess();
        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'unit']);
        $bom = null;
        $requirements = [];
        $error = null;

        if ($request->filled('product_id') || $request->filled('quantity')) {
            $validated = $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric|min:0.0001',
            ]);

            $bom = ProductBom::with(['product.unitConversions', 'items.componentProduct.unitConversions'])
                ->where('product_id', $validated['product_id'])
                ->where('status', 'approved')
                ->where('is_active', true)
                ->first();

            if (!$bom) {
                $error = 'Thành phẩm chưa có BOM đang áp dụng.';
            } else {
                $requirements = $calculator->calculate($bom, (float) $validated['quantity'], $bom->product->unit);
            }
        }

        return view('boms.plan', compact('products', 'bom', 'requirements', 'error'));
    }

    public function store(Request $request)
    {
        $this->ensureBomAccess();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'output_quantity' => 'required|numeric|min:0.0001',
            'output_unit' => 'required|string|max:32',
            'output_to_base_factor' => 'nullable|numeric|gt:0',
            'yield_percent' => 'required|numeric|gt:0|lte:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.component_product_id' => 'required|distinct|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string|max:32',
            'items.*.to_base_factor' => 'nullable|numeric|gt:0',
        ]);

        $outputProduct = Product::findOrFail($validated['product_id']);
        if (blank($outputProduct->unit)) {
            return back()->withErrors(['product_id' => 'Khai báo đơn vị gốc cho thành phẩm trước khi tạo BOM.'])->withInput();
        }

        if (
            strcasecmp($outputProduct->unit, $validated['output_unit']) !== 0 &&
            empty($validated['output_to_base_factor'])
        ) {
            return back()->withErrors(['output_unit' => 'Khai báo hệ số quy đổi sản lượng về đơn vị gốc của thành phẩm.'])->withInput();
        }

        $componentProducts = Product::whereIn(
            'id',
            collect($validated['items'])->pluck('component_product_id')->unique()
        )->get()->keyBy('id');

        foreach ($validated['items'] as $index => $item) {
            $component = $componentProducts->get($item['component_product_id']);
            if ($component->id === $outputProduct->id) {
                return back()->withErrors(["items.{$index}.component_product_id" => 'Thành phẩm không thể được khai báo là nguyên liệu của chính BOM này.'])->withInput();
            }
            if (blank($component->unit)) {
                return back()->withErrors(["items.{$index}.component_product_id" => "Khai báo đơn vị gốc cho {$component->name} trước khi thêm vào BOM."])->withInput();
            }
            if (
                strcasecmp($component->unit, $item['unit']) !== 0 &&
                empty($item['to_base_factor'])
            ) {
                return back()->withErrors([
                    "items.{$index}.unit" => "Khai báo hệ số quy đổi {$item['unit']} về {$component->unit} cho {$component->name}.",
                ])->withInput();
            }
        }

        $bom = DB::transaction(function () use ($validated, $outputProduct, $componentProducts) {
            Product::whereKey($outputProduct->id)->lockForUpdate()->firstOrFail();
            $version = ((int) ProductBom::where('product_id', $outputProduct->id)->max('version')) + 1;

            if (strcasecmp($outputProduct->unit, $validated['output_unit']) !== 0) {
                ProductUnitConversion::updateOrCreate(
                    ['product_id' => $outputProduct->id, 'unit' => $validated['output_unit']],
                    ['to_base_factor' => $validated['output_to_base_factor']]
                );
            }

            $bom = ProductBom::create([
                'product_id' => $outputProduct->id,
                'version' => $version,
                'output_quantity' => $validated['output_quantity'],
                'output_unit' => $validated['output_unit'],
                'yield_rate' => $validated['yield_percent'] / 100,
                'is_active' => false,
                'status' => 'pending_approval',
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $component = $componentProducts->get($item['component_product_id']);
                if (strcasecmp($component->unit, $item['unit']) !== 0) {
                    ProductUnitConversion::updateOrCreate(
                        ['product_id' => $component->id, 'unit' => $item['unit']],
                        ['to_base_factor' => $item['to_base_factor']]
                    );
                }

                $bom->items()->create([
                    'component_product_id' => $component->id,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                ]);
            }

            return $bom;
        });

        return redirect()->route('boms.index')->with('success', "Đã tạo BOM phiên bản {$bom->version} cho {$outputProduct->name} và gửi chờ duyệt.");
    }

    public function approve(ProductBom $productBom)
    {
        $this->ensureBomApprovalAccess();

        try {
            DB::transaction(function () use ($productBom) {
                $bom = ProductBom::whereKey($productBom->id)->lockForUpdate()->firstOrFail();
                if ($bom->status !== 'pending_approval') {
                    throw new DomainException('BOM không còn ở trạng thái chờ duyệt.');
                }

                ProductBom::where('product_id', $bom->product_id)
                    ->where('id', '!=', $bom->id)
                    ->update(['is_active' => false]);

                $bom->update([
                    'status' => 'approved',
                    'is_active' => true,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Đã duyệt BOM phiên bản {$productBom->version}.");
    }

    public function reject(Request $request, ProductBom $productBom)
    {
        $this->ensureBomApprovalAccess();
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        try {
            DB::transaction(function () use ($productBom, $validated) {
                $bom = ProductBom::whereKey($productBom->id)->lockForUpdate()->firstOrFail();
                if ($bom->status !== 'pending_approval') {
                    throw new DomainException('BOM không còn ở trạng thái chờ duyệt.');
                }

                $bom->update([
                    'status' => 'rejected',
                    'is_active' => false,
                    'rejection_reason' => trim($validated['reason']),
                    'approved_by' => null,
                    'approved_at' => null,
                ]);
            });
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Đã từ chối BOM phiên bản {$productBom->version}.");
    }
}