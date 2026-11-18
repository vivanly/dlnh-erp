<?php

namespace App\Http\Controllers;

use App\Imports\ManualLabelProductsImport;
use App\Models\ManualLabelProduct;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ManualLabelProductController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizePrinting();
        $q = trim((string) $request->query('q'));
        $like = '%'.addcslashes($q, '%_\\').'%';

        $products = ManualLabelProduct::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('scientific_name', 'like', $like)->orWhere('registration', 'like', $like)))
            ->orderBy('name')->paginate($this->perPage($request))->withQueryString();

        return view('manual-labels.products', ['products' => $products, 'q' => $q]);
    }

    public function create()
    {
        $this->authorizePrinting();

        return view('manual-labels.product-form', ['product' => new ManualLabelProduct]);
    }

    public function store(Request $request)
    {
        $this->authorizePrinting();
        ManualLabelProduct::create($this->validated($request));

        return redirect()->route('manual-labels.products')->with('success', 'Đã thêm mã hàng.');
    }

    public function edit(ManualLabelProduct $product)
    {
        $this->authorizePrinting();

        return view('manual-labels.product-form', ['product' => $product]);
    }

    public function update(Request $request, ManualLabelProduct $product)
    {
        $this->authorizePrinting();
        $product->update($this->validated($request, $product));

        return redirect()->route('manual-labels.products')->with('success', 'Đã cập nhật mã hàng.');
    }

    public function destroy(ManualLabelProduct $product)
    {
        $this->authorizePrinting();
        $product->delete();

        return back()->with('success', 'Đã xóa mã hàng '.$product->name.'.');
    }

    public function importForm()
    {
        $this->authorizePrinting();

        return view('manual-labels.product-import');
    }

    public function import(Request $request)
    {
        $this->authorizePrinting();
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải có định dạng: xlsx, xls, csv.',
            'file.max' => 'Dung lượng file không được vượt quá 2MB.',
        ]);

        $import = new ManualLabelProductsImport;
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();
        if ($failures->isNotEmpty()) {
            $errorsByRow = $failures->groupBy(fn ($failure) => $failure->row());
            $errors = $errorsByRow->take(10)->map(fn ($rowFailures, $row) => [
                'row' => $row,
                'messages' => $rowFailures->flatMap(fn ($failure) => $failure->errors())->unique()->values()->all(),
            ])->values()->all();

            return redirect()->route('manual-labels.products.import.form')
                ->with('warning', sprintf(
                    'Đã import %d mã hàng; bỏ qua %d dòng không hợp lệ.',
                    $import->importedCount(),
                    $errorsByRow->count()
                ))
                ->with('import_errors', $errors);
        }

        return redirect()->route('manual-labels.products')
            ->with('success', sprintf('Import thành công %d mã hàng!', $import->importedCount()));
    }

    private function authorizePrinting(): void
    {
        abort_unless(auth()->user()?->canPrintLabels(), 403);
    }

    private function validated(Request $request, ?ManualLabelProduct $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'scientific_name' => ['nullable', 'string', 'max:255'],
            'standard' => ['nullable', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'part_used' => ['nullable', 'string', 'max:255'],
            'registration' => ['nullable', 'string', 'max:255'],
        ]);
        $data['name_key'] = ManualLabelProduct::key($data['name']);
        validator($data, ['name_key' => [Rule::unique('manual_label_products', 'name_key')->ignore($product?->id)]], ['name_key.unique' => 'Tên hàng này đã có.'])->validate();

        return $data;
    }
}
