<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Imports\DepartmentsImport;
use App\Exports\DepartmentsExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentController extends Controller implements HasMiddleware
{
    // Khai báo middleware cho controller này
    public static function middleware(): array
    {
        return [
            // Chặn tất cả các action ngoại trừ 'index' đối với user không phải IT
            new Middleware(function ($request, $next) {
                if (!auth()->check() || !auth()->user()->isITDepartment()) {
                    abort(403, 'Bạn không có quyền thực hiện hành động này. Chỉ bộ phận IT mới được phép.');
                }
                return $next($request);
            }, except: ['index']),
        ];
    }

    public function index()
    {
        $departments = Department::all();
        return view('departments.index', compact('departments'));
    }

    public function export()
    {
        return Excel::download(new DepartmentsExport(), 'phong-ban.xlsx');
    }

    public function importForm()
    {
        return view('departments.import');
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

        $import = new DepartmentsImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();
        if ($failures->isNotEmpty()) {
            $errorsByRow = $failures->groupBy(fn ($failure) => $failure->row());
            $errors = $errorsByRow->take(10)->map(fn ($rowFailures, $row) => [
                'row' => $row,
                'messages' => $rowFailures->flatMap(fn ($failure) => $failure->errors())->unique()->values()->all(),
            ])->values()->all();

            return redirect()->route('departments.import.form')
                ->with('warning', sprintf(
                    'Đã import %d phòng ban; bỏ qua %d dòng không hợp lệ.',
                    $import->importedCount(),
                    $errorsByRow->count()
                ))
                ->with('import_errors', $errors);
        }

        return redirect()->route('departments.index')->with(
            'success',
            sprintf('Import thành công %d phòng ban!', $import->importedCount())
        );
    }

    public function create()
    {
        return view('departments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments',
        ]);

        Department::create($request->all());

        return redirect()->route('departments.index')->with('success', 'Thêm phòng ban thành công!');
    }

    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,' . $department->id,
        ]);

        $department->update($request->all());

        return redirect()->route('departments.index')->with('success', 'Cập nhật phòng ban thành công!');
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Đã xóa phòng ban!');
    }
}