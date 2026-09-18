<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Hiển thị danh sách nhân viên
    public function index()
    {
        // Lấy danh sách nhân viên kèm theo thông tin phòng ban của họ
        $users = User::with('department')->get();
        return view('users.index', compact('users'));
    }

    // Form thêm nhân viên mới
    public function create()
    {
        $departments = Department::all();
        return view('users.create', compact('departments'));
    }

    // Lưu nhân viên mới vào database
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'employee_code' => 'nullable|string|max:50|unique:users',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100', // <-- Bổ sung validate position
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
            'position' => $request->position, // <-- Bổ sung dòng này để lưu chức vụ
        ]);

        return redirect()->route('users.index')->with('success', 'Thêm nhân viên thành công!');
    }

    // Form chỉnh sửa nhân viên
    public function edit(User $user)
    {
        $departments = Department::all();
        return view('users.edit', compact('user', 'departments'));
    }

    // Cập nhật thông tin nhân viên
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'employee_code' => 'nullable|string|max:50|unique:users,employee_code,' . $user->id,
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:100', // <-- Bổ sung validate position
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'employee_code' => $request->employee_code,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => $request->status,
            'department_id' => $request->department_id,
            'position' => $request->position, // <-- Bổ sung dòng này để cập nhật chức vụ
        ];

        // Nếu có nhập mật khẩu mới thì cập nhật
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6']);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Cập nhật thông tin nhân viên thành công!');
    }

    // Xóa nhân viên
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Đã xóa nhân viên!');
    }
}