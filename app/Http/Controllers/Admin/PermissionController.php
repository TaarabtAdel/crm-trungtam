<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Permissions;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function edit()
    {
        $roles = Permissions::roles();
        unset($roles['super_admin']);

        $groups = Permissions::catalog();
        $matrix = [];
        foreach (array_keys($roles) as $role) {
            $matrix[$role] = Permissions::forRole($role);
        }

        return view('admin.system.permissions', compact('roles', 'groups', 'matrix'));
    }

    public function update(Request $request)
    {
        $roles = Permissions::roles();
        unset($roles['super_admin']);

        $payload = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'nullable|array',
            'permissions.*.*' => 'string',
        ]);

        $submitted = $payload['permissions'] ?? [];

        foreach (array_keys($roles) as $role) {
            Permissions::syncRole($role, $submitted[$role] ?? []);
        }

        return back()->with('success', 'Đã cập nhật phân quyền theo vai trò.');
    }
}
