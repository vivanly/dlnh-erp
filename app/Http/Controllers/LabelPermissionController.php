<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabelPermissionController extends Controller
{
    private function ensureItAccess(): void
    {
        abort_unless(auth()->user()?->isITDepartment(), 403);
    }

    public function index()
    {
        $this->ensureItAccess();
        $users = User::with('department')->orderBy('name')->get();

        return view('permissions.label-printing', compact('users'));
    }

    public function update(Request $request)
    {
        $this->ensureItAccess();
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'boolean'],
        ]);
        $permissions = $validated['permissions'] ?? [];

        DB::transaction(function () use ($permissions) {
            User::query()->select(['id', 'can_print_labels'])->lockForUpdate()->get()->each(function (User $user) use ($permissions) {
                $user->can_print_labels = $user->isITDepartment() || (bool) ($permissions[$user->id] ?? false);
                $user->save();
            });
        });

        return redirect()->route('permissions.label-printing.index')->with('success', 'Đã lưu quyền in nhãn.');
    }
}
