<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'admin' => ['tasks.view', 'tasks.manage', 'tasks.assign', 'tasks.view_all'],
            'accountant' => ['tasks.view', 'tasks.manage'],
            'sales' => ['tasks.view', 'tasks.manage'],
            'training' => ['tasks.view', 'tasks.manage'],
            'teacher' => ['tasks.view', 'tasks.manage'],
        ];

        $now = now();
        foreach ($map as $role => $permissions) {
            foreach ($permissions as $permission) {
                $exists = DB::table('role_permissions')
                    ->where('role', $role)
                    ->where('permission', $permission)
                    ->exists();
                if (! $exists) {
                    DB::table('role_permissions')->insert([
                        'role' => $role,
                        'permission' => $permission,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
            Permissions::forgetCache($role);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->whereIn('permission', [
            'tasks.view', 'tasks.manage', 'tasks.assign', 'tasks.view_all',
        ])->delete();
        Permissions::forgetCache();
    }
};
