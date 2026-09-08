<?php

use App\Support\Permissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permission = 'training.journals.manage';
        $now = now();

        foreach (['admin', 'training', 'teacher'] as $role) {
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

        Permissions::forgetCache();
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->where('permission', 'training.journals.manage')
            ->delete();
        Permissions::forgetCache();
    }
};
