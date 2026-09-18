<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    // Hiển thị danh sách phòng ban
    public function index()
    {
        $departments = Department::all();
        return view('departments.index', compact('departments'));
    }

    // Form tạo phòng ban mới
    public function create()
    {
        return view('departments.create');
    }

    // Lưu phòng ban mới vào database
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments',
        ]);

        Department::create($request->all());

        return redirect()->route('departments.index')->with('success', 'Thêm phòng ban thành công!');
    }

    // Form chỉnh sửa phòng ban
    public function edit(Department $department)
    {
        return view('departments.edit', compact('department'));
    }

    // Cập nhật thông tin phòng ban
    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,' . $department->id,
        ]);

        $department->update($request->all());

        return redirect()->route('departments.index')->with('success', 'Cập nhật phòng ban thành công!');
    }

    // Xóa phòng ban
    public function destroy(Department $department)
    {
        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Đã xóa phòng ban!');
    }
}