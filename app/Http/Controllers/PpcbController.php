<?php

namespace App\Http\Controllers;

use App\Models\Ppcb;
use App\Imports\PpcbsImport;
use App\Exports\PpcbsExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;

class PpcbController extends Controller implements HasMiddleware
{
    /**
     * Middleware phân quyền.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {

                abort_unless(auth()->check(), 403, 'Vui lòng đăng nhập.');

                $user = auth()->user();

                $isQA =
                    (method_exists($user, 'isQADepartment') && $user->isQADepartment()) ||
                    (isset($user->department) && strtolower($user->department) === 'qa');

                $isIT =
                    (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                    (isset($user->department) && strtolower($user->department) === 'it') ||
                    (isset($user->role) && strtolower($user->role) === 'it');

                abort_unless(
                    $isQA || $isIT,
                    403,
                    'Chỉ QA hoặc IT mới có quyền thực hiện thao tác này.'
                );

                return $next($request);

            }, only: ['create', 'store', 'edit', 'update', 'destroy', 'importForm', 'import', 'export']),
        ];
    }

    /**
     * Danh sách PPCB.
     */
    public function index(Request $request)
    {
        $ppcbs = $this->ppcbsQuery($request)
            ->orderByDesc('created_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('ppcb.index', compact('ppcbs'));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new PpcbsExport($this->ppcbsQuery($request)->orderByDesc('created_at')),
            'phuong-phap-che-bien.xlsx'
        );
    }

    public function importForm()
    {
        return view('ppcb.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải có định dạng: xlsx, xls, csv.',
            'file.max' => 'Dung lượng file không được vượt quá 2MB.',
        ]);

        $import = new PpcbsImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();
        if ($failures->isNotEmpty()) {
            $errorsByRow = $failures->groupBy(fn ($failure) => $failure->row());
            $errors = $errorsByRow->take(10)->map(fn ($rowFailures, $row) => [
                'row' => $row,
                'messages' => $rowFailures->flatMap(fn ($failure) => $failure->errors())->unique()->values()->all(),
            ])->values()->all();

            return redirect()->route('ppcb.import.form')
                ->with('warning', sprintf(
                    'Đã import %d phương pháp chế biến; bỏ qua %d dòng không hợp lệ.',
                    $import->importedCount(),
                    $errorsByRow->count()
                ))
                ->with('import_errors', $errors);
        }

        return redirect()->route('ppcb.index')->with(
            'success',
            sprintf('Import thành công %d phương pháp chế biến!', $import->importedCount())
        );
    }

    private function ppcbsQuery(Request $request)
    {
        $search = trim((string) $request->search);

        return Ppcb::query()->when($search, function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ma', 'like', "%{$search}%")
                    ->orWhere('ten_ppcb', 'like', "%{$search}%")
                    ->orWhere('chi_tiet_ppcb', 'like', "%{$search}%");
            });
        });
    }

    /**
     * Form thêm.
     */
    public function create()
    {
        return view('ppcb.create');
    }

    /**
     * Lưu PPCB.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'ma' => 'required|string|max:50|unique:ppcb,ma',
            'ten_ppcb' => 'required|string|max:255',
            'chi_tiet_ppcb' => 'nullable|string',
            'ghi_chu' => 'nullable|string',
        ], [
            'ma.required' => 'Vui lòng nhập mã phương pháp chế biến.',
            'ma.unique' => 'Mã PPCB đã tồn tại.',
            'ten_ppcb.required' => 'Vui lòng nhập tên phương pháp chế biến.',
        ]);

        $validated['ma'] = trim($validated['ma']);
        $validated['ten_ppcb'] = trim($validated['ten_ppcb']);

        Ppcb::create($validated);

        return redirect()
            ->route('ppcb.index')
            ->with('success', 'Thêm phương pháp chế biến thành công.');
    }

    /**
     * Form sửa.
     */
    public function edit(Ppcb $ppcb)
    {
        return view('ppcb.edit', compact('ppcb'));
    }

    /**
     * Cập nhật.
     */
    public function update(Request $request, Ppcb $ppcb)
    {
        $validated = $request->validate([
            'ma' => 'required|string|max:50|unique:ppcb,ma,' . $ppcb->id,
            'ten_ppcb' => 'required|string|max:255',
            'chi_tiet_ppcb' => 'nullable|string',
            'ghi_chu' => 'nullable|string',
        ], [
            'ma.required' => 'Vui lòng nhập mã phương pháp chế biến.',
            'ma.unique' => 'Mã PPCB đã tồn tại.',
            'ten_ppcb.required' => 'Vui lòng nhập tên phương pháp chế biến.',
        ]);

        $validated['ma'] = trim($validated['ma']);
        $validated['ten_ppcb'] = trim($validated['ten_ppcb']);

        $ppcb->update($validated);

        return redirect()
            ->route('ppcb.index')
            ->with('success', 'Cập nhật phương pháp chế biến thành công.');
    }

    /**
     * Xóa.
     */
    public function destroy(Ppcb $ppcb)
    {
        $ppcb->delete();

        return redirect()
            ->route('ppcb.index')
            ->with('success', 'Xóa phương pháp chế biến thành công.');
    }

    /**
     * API tìm kiếm AJAX cho Select2.
     */
    public function searchAjax(Request $request)
    {
        $keyword = trim($request->q);

        $ppcbs = Ppcb::query()
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('ma', 'like', "%{$keyword}%")
                        ->orWhere('ten_ppcb', 'like', "%{$keyword}%")
                        ->orWhere('chi_tiet_ppcb', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('ten_ppcb')
            ->limit(20)
            ->get(['id', 'ma', 'ten_ppcb', 'chi_tiet_ppcb']);

        return response()->json(
            $ppcbs->map(function ($item) {
                return [
                    'id' => $item->id,
                    'text' => "{$item->ten_ppcb} [{$item->ma}]",
                    'ma' => $item->ma,
                    'ten_ppcb' => $item->ten_ppcb,
                    'chi_tiet_ppcb' => $item->chi_tiet_ppcb,
                ];
            })
        );
    }
}