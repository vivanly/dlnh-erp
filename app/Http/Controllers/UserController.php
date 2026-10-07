<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Imports\UsersImport;
use App\Exports\UsersExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller implements HasMiddleware
{
    // Chỉ IT được xem hoặc quản lý hồ sơ nhân sự.
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!auth()->check() || !auth()->user()->isITDepartment()) {
                    abort(403, 'Bạn không có quyền thực hiện hành động này. Chỉ bộ phận IT mới được phép.');
                }
                return $next($request);
            }),
        ];
    }

    // Hiển thị danh sách nhân viên kèm tìm kiếm & lọc (Chỉ IT)
    public function index(Request $request)
    {
        $query = $this->usersQuery($request);

        $perPage = $this->perPage($request, 100);

        // Phân trang và giữ lại query string khi chuyển trang
        $users = $query->paginate($perPage)->withQueryString();
        
        // Lấy danh sách phòng ban để đổ vào thẻ <select> lọc trên giao diện
        $departments = Department::all();

        return view('users.index', compact('users', 'departments'));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new UsersExport($this->usersQuery($request)),
            'nhan-su.xlsx'
        );
    }

    private function usersQuery(Request $request)
    {
        $query = User::with('department');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    // Form thêm nhân viên mới (Chỉ IT)
    public function create()
    {
        $departments = Department::all();
        return view('users.create', compact('departments'));
    }

    // Hiển thị form import nhân viên (Chỉ IT)
    public function importForm()
    {
        return view('users.import');
    }

    // Import nhân viên từ file Excel (Chỉ IT)
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File phải có định dạng: xlsx, xls, csv.',
            'file.max' => 'Dung lượng file không được vượt quá 2MB.',
        ]);

        $import = new UsersImport();
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();
        if ($failures->isNotEmpty()) {
            $errorsByRow = $failures->groupBy(fn ($failure) => $failure->row());
            $errors = $errorsByRow->take(10)->map(fn ($rowFailures, $row) => [
                'row' => $row,
                'messages' => $rowFailures->flatMap(fn ($failure) => $failure->errors())->unique()->values()->all(),
            ])->values()->all();

            return redirect()->route('users.import.form')
                ->with('warning', sprintf(
                    'Đã import %d nhân viên; bỏ qua %d dòng không hợp lệ.',
                    $import->importedCount(),
                    $errorsByRow->count()
                ))
                ->with('import_errors', $errors);
        }

        return redirect()->route('users.index')->with(
            'success',
            sprintf('Import thành công %d nhân viên!', $import->importedCount())
        );
    }

    // Lưu nhân viên mới vào database (Chỉ IT)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'employee_code' => 'nullable|string|max:50|unique:users',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'employee_code' => $request->employee_code,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => $request->status ?? 'working',
            'department_id' => $request->department_id,
            'position' => $request->position,
        ]);

        return redirect()->route('users.index')->with('success', 'Thêm nhân viên thành công!');
    }

    // Form chỉnh sửa nhân viên (Chỉ IT)
    public function edit(User $user)
    {
        $departments = Department::all();
        return view('users.edit', compact('user', 'departments'));
    }

    // Cập nhật thông tin nhân viên (Chỉ IT)
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'employee_code' => 'nullable|string|max:50|unique:users,employee_code,' . $user->id,
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'employee_code' => $request->employee_code,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => $request->status,
            'department_id' => $request->department_id,
            'position' => $request->position,
        ];

        // Nếu có nhập mật khẩu mới thì cập nhật
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6']);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Cập nhật thông tin nhân viên thành công!');
    }

    // Xóa nhân viên (Chỉ IT)
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Đã xóa nhân viên!');
    }
}