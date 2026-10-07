<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SupplierController extends Controller implements HasMiddleware
{
    /**
     * Khai báo middleware phân quyền trực tiếp trong Controller (cho phép Sales, Admin và IT thao tác)
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!auth()->check()) {
                    abort(403, 'Vui lòng đăng nhập để thực hiện thao tác này.');
                }

                $user = auth()->user();

                // Kiểm tra điều kiện bộ phận kinh doanh (Sales)
                $isSales = (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
                           (isset($user->department) && strtolower($user->department) === 'sales');

                // Kiểm tra điều kiện bộ phận IT
                $isIT = (method_exists($user, 'isITDepartment') && $user->isITDepartment()) ||
                        (isset($user->department) && strtolower($user->department) === 'it') ||
                        (isset($user->role) && strtolower($user->role) === 'it');

                // Nếu không thuộc Sales hoặc IT thì chặn lại
                if (!$isSales && !$isIT) {
                    abort(403, 'Truy cập bị từ chối: Chỉ bộ phận kinh doanh hoặc IT mới được thêm, sửa, xóa nhà cung cấp.');
                }

                return $next($request);
            }, only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    /**
     * 1. Hiển thị danh sách nhà cung cấp (Tích hợp tìm kiếm thông minh đa trường)
     * Cho phép tất cả các bộ phận liên quan xem để làm việc.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $suppliers = Supplier::when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('code', 'like', "%{$search}%")
                             ->orWhere('phone', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->withQueryString(); // Giữ lại từ khóa tìm kiếm khi chuyển trang

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    /**
     * 2. Hiển thị form thêm mới nhà cung cấp (Được bảo vệ bởi phân quyền)
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * 3. Lưu dữ liệu nhà cung cấp mới vào CSDL (Được bảo vệ bởi phân quyền)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:suppliers,code|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        Supplier::create($request->all());

        return redirect()->route('suppliers.index')->with('success', 'Thêm nhà cung cấp thành công!');
    }

    /**
     * 5. Hiển thị form chỉnh sửa nhà cung cấp (Được bảo vệ bởi phân quyền)
     */
    public function edit(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * 6. Cập nhật thông tin nhà cung cấp (Được bảo vệ bởi phân quyền)
     */
    public function update(Request $request, string $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:suppliers,code,' . $supplier->id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $supplier->update($request->all());

        return redirect()->route('suppliers.index')->with('success', 'Cập nhật nhà cung cấp thành công!');
    }

    /**
     * 7. Xóa nhà cung cấp khỏi hệ thống (Được bảo vệ bởi phân quyền)
     */
    public function destroy(string $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Xóa nhà cung cấp thành công!');
    }

    /**
     * 8. API tìm kiếm Ajax phục vụ cho Select2 chọn nhà cung cấp ở các module khác (Kho, Kế toán...)
     * Mở quyền cho tất cả user đã đăng nhập để phục vụ nghiệp vụ hệ thống ERP.
     */
    public function searchAjax(Request $request)
    {
        $keyword = $request->get('q');

        $suppliers = Supplier::when($keyword, function ($query, $keyword) {
                return $query->where('name', 'like', "%{$keyword}%")
                             ->orWhere('code', 'like', "%{$keyword}%")
                             ->orWhere('phone', 'like', "%{$keyword}%");
            })
            ->limit(20)
            ->get();

        return response()->json($suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'code' => $supplier->code ?? '',
                'phone' => $supplier->phone ?? ''
            ];
        }));
    }
}