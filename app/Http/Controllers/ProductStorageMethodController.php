<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStorageMethod;
use Illuminate\Http\Request;

class ProductStorageMethodController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $canManage = $this->canManage();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);
        $storageMethods = ProductStorageMethod::query()
            ->with('product')
            ->when($filters['product_id'] ?? null, fn ($query, $productId) => $query->where('product_id', $productId))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($query) use ($search) {
                    $query->where('storage_method', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('product-storage-methods.index', compact('storageMethods', 'products', 'canManage'));
    }

    public function create()
    {
        $this->authorizeQa();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('product-storage-methods.form', ['storageMethod' => new ProductStorageMethod(), 'products' => $products]);
    }

    public function store(Request $request)
    {
        $this->authorizeQa();
        ProductStorageMethod::create($request->validate($this->rules()));

        return redirect()->route('product-storage-methods.index')->with('success', 'Đã thêm phương pháp bảo quản.');
    }

    public function edit(ProductStorageMethod $productStorageMethod)
    {
        $this->authorizeQa();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('product-storage-methods.form', ['storageMethod' => $productStorageMethod, 'products' => $products]);
    }

    public function update(Request $request, ProductStorageMethod $productStorageMethod)
    {
        $this->authorizeQa();
        $productStorageMethod->update($request->validate($this->rules()));

        return redirect()->route('product-storage-methods.index')->with('success', 'Đã cập nhật phương pháp bảo quản.');
    }

    public function destroy(ProductStorageMethod $productStorageMethod)
    {
        $this->authorizeQa();
        $productStorageMethod->delete();

        return redirect()->route('product-storage-methods.index')->with('success', 'Đã xóa phương pháp bảo quản.');
    }

    private function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'storage_method' => ['required', 'string', 'max:10000'],
            'note' => ['nullable', 'string', 'max:10000'],
        ];
    }

    private function canManage(): bool
    {
        $user = auth()->user();

        return $user->isQADepartment() || $user->isITDepartment();
    }

    private function authorizeQa(): void
    {
        abort_unless($this->canManage(), 403, 'Chỉ bộ phận QA hoặc IT mới có quyền quản lý phương pháp bảo quản.');
    }
}