<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRegulatoryDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductRegulatoryDocumentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'document_type' => ['nullable', 'in:cong_bo,dang_ky,gpnk'],
        ]);
        $selectedProductId = $validated['product_id'] ?? null;
        $productOptions = Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'classification']);

        $documents = ProductRegulatoryDocument::with('product')
            ->when($selectedProductId, fn ($query) => $query->where('product_id', $selectedProductId))
            ->when(!empty($validated['search']), function ($query) use ($validated) {
                $search = trim($validated['search']);
                $query->where(function ($query) use ($search) {
                    $query->where('document_number', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->when(!empty($validated['document_type']), fn ($query) => $query->where('document_type', $validated['document_type']))
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('product-regulatory-documents.index', compact('documents', 'productOptions', 'selectedProductId'));
    }

    public function create(Request $request)
    {
        $this->authorizeQa();

        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'classification', 'origin']);
        $classifications = $products->pluck('classification')->filter()->unique()->sort()->values();
        $selectedProductId = $request->integer('product_id') ?: null;

        return view('product-regulatory-documents.create', compact('products', 'classifications', 'selectedProductId'));
    }

    public function store(Request $request)
    {
        $this->authorizeQa();

        $validated = $request->validate($this->rules());
        ProductRegulatoryDocument::create($validated);

        return redirect()->route('product-regulatory-documents.index', ['product_id' => $validated['product_id']])
            ->with('success', 'Đã thêm hồ sơ công bố / đăng ký.');
    }

    public function edit(ProductRegulatoryDocument $productRegulatoryDocument)
    {
        $this->authorizeQa();

        $products = Product::orderBy('name')->get(['id', 'name', 'sku', 'classification', 'origin']);
        $classifications = $products->pluck('classification')->filter()->unique()->sort()->values();

        return view('product-regulatory-documents.edit', compact('productRegulatoryDocument', 'products', 'classifications'));
    }

    public function update(Request $request, ProductRegulatoryDocument $productRegulatoryDocument)
    {
        $this->authorizeQa();

        $validated = $request->validate($this->rules($productRegulatoryDocument));
        $productRegulatoryDocument->update($validated);

        return redirect()->route('product-regulatory-documents.index', ['product_id' => $validated['product_id']])
            ->with('success', 'Đã cập nhật hồ sơ sản phẩm.');
    }

    public function destroy(ProductRegulatoryDocument $productRegulatoryDocument)
    {
        $this->authorizeQa();
        $productId = $productRegulatoryDocument->product_id;
        $productRegulatoryDocument->delete();

        return redirect()->route('product-regulatory-documents.index', ['product_id' => $productId])
            ->with('success', 'Đã xóa hồ sơ sản phẩm.');
    }

    private function rules(?ProductRegulatoryDocument $document = null): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'document_type' => ['required', 'in:cong_bo,dang_ky,gpnk'],
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_regulatory_documents', 'document_number')
                    ->where(fn ($query) => $query->where('document_type', request('document_type')))
                    ->ignore($document?->id),
            ],
            'document_date' => ['required', 'date'],
            'document_form' => ['required', 'string', 'max:80'],
            'note' => ['nullable', 'string'],
        ];
    }

    private function authorizeQa(): void
    {
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            abort(403, 'Chỉ bộ phận QA hoặc IT mới có quyền quản lý hồ sơ pháp lý của sản phẩm.');
        }
    }
}
