<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $role = 'training';
        $permissions = config('permissions.defaults.training', []);

        DB::table('role_permissions')->where('role', $role)->delete();

        $now = now();
        $rows = collect($permissions)->map(fn (string $permission) => [
            'role' => $role,
            'permission' => $permission,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('role_permissions')->insert($rows);
        }

        Permissions::forgetCache($role);
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('role', 'training')->delete();
        Permissions::forgetCache('training');
    }
};
