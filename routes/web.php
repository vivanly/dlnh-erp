<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PpcbController; // <-- Thêm dòng này để import PpcbController
use App\Models\Department; 
use App\Models\User;         
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $totalDepartments = Department::count();
    $totalUsers = User::count();
    $workingUsers = User::where('status', 'working')->orWhereNull('status')->count();
    
    return view('dashboard', compact('totalDepartments', 'totalUsers', 'workingUsers'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Hồ sơ nhân viên / Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Trang đổi mật khẩu riêng biệt
    Route::get('/profile/password', function () {
        return view('profile.change-password');
    })->name('password.edit');

    // Quản lý Phòng ban
    Route::resource('departments', DepartmentController::class);

    // Quản lý Nhân viên
    Route::resource('users', UserController::class);

    // Quản lý PPCB (Thêm mới)
    Route::resource('ppcb', PpcbController::class);

    // 👇 ĐẶT ROUTE IMPORT LÊN TRƯỚC RESOURCE PRODUCTS
    Route::get('/products/import', [ProductController::class, 'importForm'])->name('products.import.form');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

    // Quản lý Sản phẩm dược liệu
    Route::resource('products', ProductController::class);
});

require __DIR__.'/auth.php';