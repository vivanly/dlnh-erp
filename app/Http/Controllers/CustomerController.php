<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private function canManageCustomers(): bool
    {
        $user = auth()->user();

        return $user && (
            (method_exists($user, 'isSalesDepartment') && $user->isSalesDepartment()) ||
            (method_exists($user, 'isITDepartment') && $user->isITDepartment())
        );
    }

    // 1. Hiển thị danh sách khách hàng (Tìm kiếm nội bộ trên trang)
    public function index(Request $request)
    {
        $query = Customer::query();
        $status = $request->input('status', 'all');
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }
        $query->latest();

        // Xử lý tìm kiếm nội bộ theo từ khóa (Mã, Tên, Số điện thoại, Email)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $customers = $query->paginate($this->perPage($request))->withQueryString();

        // Nếu là request AJAX từ trang index (tải lại bảng nội bộ không giật trang)
        if ($request->ajax()) {
            return view('customers.partials.table', compact('customers'))->render();
        }

        $canManageCustomers = $this->canManageCustomers();

        return view('customers.index', compact('customers', 'canManageCustomers', 'status'));
    }

    // 2. Hiển thị form thêm mới
    public function create()
    {
        abort_unless($this->canManageCustomers(), 403);

        return view('customers.create');
    }

    // 3. Lưu dữ liệu thêm mới vào database
    public function store(Request $request)
    {
        abort_unless($this->canManageCustomers(), 403);

        $validated = $request->validate([
            'code' => 'required|unique:customers,code',
            'name' => 'required|string|max:255',
            'type' => 'required|string',
        ], [
            'code.required' => 'Vui lòng nhập mã khách hàng.',
            'code.unique' => 'Mã khách hàng này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên khách hàng.',
            'type.required' => 'Vui lòng chọn phân loại.',
        ]);

        Customer::create($validated + ['is_active' => true]);

        return redirect()->route('customers.index')->with('success', 'Thêm khách hàng thành công!');
    }

    // 4. Hiển thị form chỉnh sửa
    public function edit(Customer $customer)
    {
        abort_unless($this->canManageCustomers(), 403);

        return view('customers.edit', compact('customer'));
    }

    // 5. Cập nhật dữ liệu
    public function update(Request $request, Customer $customer)
    {
        abort_unless($this->canManageCustomers(), 403);

        $validated = $request->validate([
            'code' => 'required|unique:customers,code,' . $customer->id,
            'name' => 'required|string|max:255',
            'type' => 'required|string',
        ], [
            'code.required' => 'Vui lòng nhập mã khách hàng.',
            'code.unique' => 'Mã khách hàng này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên khách hàng.',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Cập nhật khách hàng thành công!');
    }

    public function toggleStatus(Customer $customer)
    {
        abort_unless($this->canManageCustomers(), 403);

        $customer->update(['is_active' => ! $customer->is_active]);

        return redirect()->route('customers.index')->with(
            'success',
            $customer->is_active ? 'Đã kích hoạt khách hàng.' : 'Đã đánh dấu khách hàng không hoạt động.'
        );
    }

    // 7. Tìm kiếm liên thông (API AJAX trả về JSON cho các form bên ngoài gọi vào)
    public function searchAjax(Request $request)
    {
        $keyword = $request->input('q', '');
        $query = Customer::where('is_active', true);

        if (!empty($keyword)) {
            $query->where(function($q) use ($keyword) {
                $q->where('code', 'LIKE', "%{$keyword}%")
                ->orWhere('name', 'LIKE', "%{$keyword}%")
                ->orWhere('phone', 'LIKE', "%{$keyword}%")
                ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }

        $customers = $query->limit(10)->get();

        // Thêm ->values() để ép kết quả trả về dạng mảng chuẩn JSON [...]
        return response()->json($customers->map(function ($customer) {
            return [
                'id' => $customer->id,
                'text' => $customer->name . ' | ' . ($customer->address ?? 'N/A') . ' ',
                'code' => $customer->code ?? '',
                'address' => $customer->address ?? ''
            ];
        })->values());
    }
}
