<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Imports\ProductsImport; 
use Maatwebsite\Excel\Facades\Excel; 

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Xử lý tìm kiếm theo tên, SKU hoặc GTIN
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('gtin', 'like', "%{$search}%");
            });
        }

        // Lấy danh sách kèm phân trang và giữ lại từ khóa tìm kiếm khi chuyển trang
        $products = $query->latest()->paginate(10)->withQueryString();

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku',
            'gtin' => 'nullable|string|unique:products,gtin',
            'part_used' => 'nullable|string',
            'origin' => 'nullable|string',
            'unit' => 'nullable|string',
            'classification' => 'nullable|string',
            'scientific_name' => 'nullable|string',
            'scientific_name_reference' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        // Tự động tạo slug từ tên sản phẩm
        $validated['slug'] = Str::slug($request->name);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Thêm sản phẩm thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku,' . $product->id,
            'gtin' => 'nullable|string|unique:products,gtin,' . $product->id,
            'part_used' => 'nullable|string',
            'origin' => 'nullable|string',
            'unit' => 'nullable|string',
            'classification' => 'nullable|string',
            'scientific_name' => 'nullable|string',
            'scientific_name_reference' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($request->name);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Cập nhật sản phẩm thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Xóa sản phẩm thành công!');
    }

    /**
     * Hiển thị giao diện form chọn file Excel để import
     */
    public function importForm()
    {
        return view('products.import');
    }

    /**
     * Xử lý file Excel upload lên hệ thống
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải có định dạng: xlsx, xls, csv.',
            'file.max' => 'Dung lượng file không được vượt quá 2MB.'
        ]);

        try {
            Excel::import(new ProductsImport, $request->file('file'));
            return redirect()->route('products.index')->with('success', 'Import danh mục dược liệu thành công!');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi import file: ' . $e->getMessage());
        }
    }
}