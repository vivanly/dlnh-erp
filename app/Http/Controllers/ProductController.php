<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Ppcb;
use App\Exports\ProductsExport;
use Illuminate\Http\Request;
use App\Imports\ProductsImport; 
use Maatwebsite\Excel\Facades\Excel; 

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $classifications = Product::query()
            ->whereNotNull('classification')
            ->where('classification', '<>', '')
            ->distinct()
            ->orderBy('classification')
            ->pluck('classification');

        $products = $this->productsQuery($request)
            ->with('ppcb')
            ->paginate($this->perPage($request, 100))
            ->withQueryString();

        return view('products.index', compact('products', 'classifications'));
    }

    public function export(Request $request)
    {
        if (!$this->canManageProducts()) {
            abort(403, 'Chỉ bộ phận QA hoặc IT mới có quyền xuất danh mục sản phẩm.');
        }

        return Excel::download(
            new ProductsExport($this->productsQuery($request)->with('ppcb')),
            'danh-muc-san-pham.xlsx'
        );
    }

    private function productsQuery(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'classification' => ['nullable', 'string', 'max:255'],
        ]);

        return Product::query()
            ->when(!empty($filters['classification']), fn ($query) => $query->where('classification', $filters['classification']))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('classification')
            ->orderBy('name')
            ->orderBy('id');
    }

    private function canManageProducts(): bool
    {
        return auth()->user()->isQADepartment() || auth()->user()->isITDepartment();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Kiểm tra quyền: Chỉ bộ phận QA hoặc IT/Admin mới được thêm sản phẩm
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            return redirect()->route('products.index')->with('error', 'Chỉ bộ phận QA mới có quyền thêm sản phẩm mới.');
        }

        $ppcbs = Ppcb::orderBy('ten_ppcb')->get(['id', 'ma', 'ten_ppcb']);

        return view('products.create', compact('ppcbs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            abort(403, 'Chỉ bộ phận QA mới có quyền thực hiện thao tác này.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'gtin' => 'nullable|string|unique:products,gtin',
            'part_used' => 'nullable|string',
            'ppcb_id' => 'nullable|integer|exists:ppcb,id',
            'origin' => 'nullable|string',
            'unit' => 'nullable|string',
            'classification' => 'nullable|string',
            'scientific_name' => 'nullable|string',
            'scientific_name_reference' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $validated['slug'] = Product::uniqueSlug($validated['name'], $validated['sku']);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        // Kiểm tra quyền: Chỉ bộ phận QA hoặc IT/Admin mới được sửa sản phẩm
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            return redirect()->route('products.index')->with('error', 'Chỉ bộ phận QA mới có quyền chỉnh sửa sản phẩm.');
        }

        $ppcbs = Ppcb::orderBy('ten_ppcb')->get(['id', 'ma', 'ten_ppcb']);

        return view('products.edit', compact('product', 'ppcbs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            abort(403, 'Chỉ bộ phận QA mới có quyền thực hiện thao tác này.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'gtin' => 'nullable|string|unique:products,gtin,' . $product->id,
            'part_used' => 'nullable|string',
            'ppcb_id' => 'nullable|integer|exists:ppcb,id',
            'origin' => 'nullable|string',
            'unit' => 'nullable|string',
            'classification' => 'nullable|string',
            'scientific_name' => 'nullable|string',
            'scientific_name_reference' => 'nullable|string',
            'note' => 'nullable|string',
            'return_search' => 'nullable|string|max:255',
            'return_classification' => 'nullable|string|max:255',
            'return_per_page' => 'nullable|integer|in:100,500,1000,5000,10000,50000',
            'return_page' => 'nullable|integer|min:1',
        ]);

        $returnQuery = [
            'search' => $validated['return_search'] ?? null,
            'classification' => $validated['return_classification'] ?? null,
            'per_page' => $validated['return_per_page'] ?? null,
            'page' => $validated['return_page'] ?? null,
        ];
        unset(
            $validated['return_search'],
            $validated['return_classification'],
            $validated['return_per_page'],
            $validated['return_page'],
        );

        $validated['slug'] = Product::uniqueSlug($validated['name'], $validated['sku'], $product->id);

        $product->update($validated);

        $returnQuery = array_filter($returnQuery, fn ($value) => $value !== null && $value !== '');

        return redirect()->to(route('products.index', $returnQuery) . '#product-' . $product->id)
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Kiểm tra quyền: Chỉ bộ phận QA hoặc IT/Admin mới được xóa sản phẩm
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            return redirect()->route('products.index')->with('error', 'Chỉ bộ phận QA mới có quyền xóa sản phẩm.');
        }

        $product->delete();
        return redirect()->route('products.index')->with('success', 'Xóa sản phẩm thành công!');
    }

    /**
     * Hiển thị giao diện form chọn file Excel để import
     */
    public function importForm()
    {
        if (!auth()->user()->isQADepartment() && !auth()->user()->isITDepartment()) {
            return redirect()->route('products.index')->with('error', 'Chỉ bộ phận QA mới có quyền truy cập chức năng import.');
        }

        return view('products.import');
    }

    /**
     * Xử lý file Excel upload lên hệ thống
     */
    public function import(Request $request)
    {
        if (!$this->canManageProducts()) {
            abort(403, 'Chỉ bộ phận QA mới có quyền thực hiện thao tác này.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải có định dạng: xlsx, xls, csv.',
            'file.max' => 'Dung lượng file không được vượt quá 2MB.'
        ]);

        $import = new ProductsImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();
        if ($failures->isNotEmpty()) {
            $errorsByRow = $failures->groupBy(fn ($failure) => $failure->row());
            $errors = $errorsByRow->take(10)->map(fn ($rowFailures, $row) => [
                'row' => $row,
                'messages' => $rowFailures->flatMap(fn ($failure) => $failure->errors())->unique()->values()->all(),
            ])->values()->all();

            return redirect()->route('products.import.form')
                ->with('warning', sprintf(
                    'Đã import %d sản phẩm; bỏ qua %d dòng không hợp lệ.',
                    $import->importedCount(),
                    $errorsByRow->count()
                ))
                ->with('import_errors', $errors);
        }

        return redirect()->route('products.index')->with(
            'success',
            sprintf('Import thành công %d sản phẩm!', $import->importedCount())
        );
    }

    /**
     * API tìm kiếm Ajax phục vụ cho Select2 chọn sản phẩm / vị thuốc
     */
    public function searchAjax(Request $request)
    {
        $keyword = $request->get('q');

        $products = Product::when($keyword, function ($query, $keyword) {
                return $query->where('name', 'like', "%{$keyword}%")
                            ->orWhere('sku', 'like', "%{$keyword}%")
                            ->orWhere('scientific_name', 'like', "%{$keyword}%");
            })
            ->limit(20)
            ->get();

        return response()->json($products->map(function ($product) {
            return [
                'id' => $product->id,
                'text' => $product->name . ' - SKU: ' . ($product->sku ?? 'N/A'), 
                'name' => $product->name,
                'sku' => $product->sku ?? '',
                'unit' => $product->unit ?? 'Kg',
                'classification' => $product->classification ?? '', // Đã bổ sung trường loại hàng
                'origin' => $product->origin ?? '',
                'purchase_price' => $product->purchase_price ?? 0
            ];
        }));
    }
}