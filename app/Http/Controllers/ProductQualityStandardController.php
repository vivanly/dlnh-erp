<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductQualityStandard;
use Illuminate\Http\Request;

class ProductQualityStandardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $canManage = $this->canManage();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $standards = ProductQualityStandard::query()
            ->with('product')
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('product_id', $productId))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search) {
                    $query->where('standard_type', 'like', "%{$search}%")
                        ->orWhere('indicator', 'like', "%{$search}%")
                        ->orWhere('requirement', 'like', "%{$search}%")
                        ->orWhere('method', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('product-quality-standards.index', compact('standards', 'products', 'canManage'));
    }

    public function create()
    {
        $this->authorizeQc();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('product-quality-standards.form', ['standard' => new ProductQualityStandard(), 'products' => $products]);
    }

    public function store(Request $request)
    {
        $this->authorizeQc();
        $validated = $request->validate($this->rules());
        ProductQualityStandard::create($validated);

        return redirect()->route('product-quality-standards.index')->with('success', 'Đã thêm tiêu chuẩn chất lượng.');
    }

    public function edit(ProductQualityStandard $productQualityStandard)
    {
        $this->authorizeQc();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('product-quality-standards.form', ['standard' => $productQualityStandard, 'products' => $products]);
    }

    public function update(Request $request, ProductQualityStandard $productQualityStandard)
    {
        $this->authorizeQc();
        $productQualityStandard->update($request->validate($this->rules()));

        return redirect()->route('product-quality-standards.index')->with('success', 'Đã cập nhật tiêu chuẩn chất lượng.');
    }

    public function destroy(ProductQualityStandard $productQualityStandard)
    {
        $this->authorizeQc();
        $productQualityStandard->delete();

        return redirect()->route('product-quality-standards.index')->with('success', 'Đã xóa tiêu chuẩn chất lượng.');
    }

    private function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'standard_type' => ['required', 'string', 'max:255'],
            'indicator' => ['required', 'string', 'max:255'],
            'requirement' => ['required', 'string', 'max:10000'],
            'method' => ['required', 'string', 'max:10000'],
            'note' => ['nullable', 'string', 'max:10000'],
        ];
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->isQCDepartment() || $user->isITDepartment();
    }

    private function authorizeQc(): void
    {
        abort_unless($this->canManage(), 403, 'Chỉ bộ phận QC hoặc IT mới có quyền quản lý tiêu chuẩn chất lượng.');
    }
}