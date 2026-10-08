<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class CatalogController extends Controller
{
    abstract protected function modelClass(): string;

    abstract protected function routePrefix(): string;

    abstract protected function labels(): array;

    private function authorizeManage(): void
    {
        $user = auth()->user();
        abort_unless($user && ($user->isQADepartment() || $user->isITDepartment()), 403, 'Chỉ bộ phận QA hoặc IT mới có quyền thực hiện thao tác này.');
    }

    private function table(): string
    {
        return (new ($this->modelClass()))->getTable();
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'classification' => ['nullable', 'string', 'max:255'],
        ]);

        $model = $this->modelClass();

        $classifications = $model::query()
            ->whereNotNull('classification')
            ->where('classification', '<>', '')
            ->distinct()
            ->orderBy('classification')
            ->pluck('classification');

        $items = $model::query()
            ->when(!empty($filters['classification']), fn ($query) => $query->where('classification', $filters['classification']))
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(fn ($sub) => $sub->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            })
            ->orderBy('classification')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($this->perPage($request, 100))
            ->withQueryString();

        return view('catalog.index', [
            'items' => $items,
            'classifications' => $classifications,
            'prefix' => $this->routePrefix(),
            'labels' => $this->labels(),
            'canManage' => auth()->user()->isQADepartment() || auth()->user()->isITDepartment(),
        ]);
    }

    public function create()
    {
        $this->authorizeManage();

        return view('catalog.form', [
            'item' => null,
            'prefix' => $this->routePrefix(),
            'labels' => $this->labels(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $validated = $this->validated($request);
        $model = $this->modelClass();
        $validated['slug'] = $model::uniqueSlug($validated['name'], $validated['sku']);
        $model::create($validated);

        return redirect()->route($this->routePrefix() . '.index')->with('success', $this->labels()['created']);
    }

    public function edit($id)
    {
        $this->authorizeManage();

        $model = $this->modelClass();

        return view('catalog.form', [
            'item' => $model::findOrFail($id),
            'prefix' => $this->routePrefix(),
            'labels' => $this->labels(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManage();

        $model = $this->modelClass();
        $item = $model::findOrFail($id);
        $validated = $this->validated($request, $item->id);
        $validated['slug'] = $model::uniqueSlug($validated['name'], $validated['sku'], $item->id);
        $item->update($validated);

        return redirect()->to(route($this->routePrefix() . '.index') . '#item-' . $item->id)->with('success', $this->labels()['updated']);
    }

    public function destroy($id)
    {
        $this->authorizeManage();

        $model = $this->modelClass();
        $item = $model::findOrFail($id);

        $inUse = \App\Models\PurchaseOrderItem::where('material_type', $this->materialType())->where('material_id', $item->id)->exists();
        if ($inUse) {
            return redirect()->route($this->routePrefix() . '.index')->with('error', 'Mục này đã được dùng trong đơn mua hàng nên không thể xóa.');
        }

        $item->delete();

        return redirect()->route($this->routePrefix() . '.index')->with('success', $this->labels()['deleted']);
    }

    public function searchAjax(Request $request)
    {
        $keyword = trim((string) $request->get('q'));
        $model = $this->modelClass();

        $items = $model::query()
            ->when($keyword !== '', fn ($query) => $query->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('sku', 'like', "%{$keyword}%")
                    ->orWhere('scientific_name', 'like', "%{$keyword}%");
            }))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($items->map(fn ($item) => [
            'id' => $item->id,
            'text' => $item->name . ' - SKU: ' . $item->sku,
            'name' => $item->name,
            'sku' => $item->sku,
            'unit' => $item->unit ?: 'Kg',
            'classification' => $item->classification ?? '',
            'origin' => $item->origin ?? '',
        ]));
    }

    abstract protected function materialType(): string;

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:' . $this->table() . ',sku' . ($ignoreId ? ',' . $ignoreId : ''),
            'part_used' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'classification' => 'nullable|string|max:255',
            'scientific_name' => 'nullable|string|max:255',
            'scientific_name_reference' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);
    }
}
