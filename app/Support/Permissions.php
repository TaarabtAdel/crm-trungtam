<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Permissions
{
    public static function catalog(): array
    {
        return config('permissions.groups', []);
    }

    public static function allKeys(): array
    {
        $keys = [];
        foreach (self::catalog() as $group) {
            foreach (array_keys($group['permissions'] ?? []) as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public static function roles(): array
    {
        return config('permissions.roles', []);
    }

    public static function forRole(string $role): array
    {
        if ($role === 'super_admin') {
            return self::allKeys();
        }

        return Cache::remember("role_permissions:{$role}", 300, function () use ($role) {
            return DB::table('role_permissions')
                ->where('role', $role)
                ->pluck('permission')
                ->all();
        });
    }

    public static function forgetCache(?string $role = null): void
    {
        if ($role) {
            Cache::forget("role_permissions:{$role}");

            return;
        }

        foreach (array_keys(self::roles()) as $r) {
            Cache::forget("role_permissions:{$r}");
        }
    }

    public static function syncRole(string $role, array $permissions): void
    {
        if ($role === 'super_admin') {
            return;
        }

        $valid = array_intersect($permissions, self::allKeys());
        DB::table('role_permissions')->where('role', $role)->delete();

        $now = now();
        $rows = array_map(fn ($permission) => [
            'role' => $role,
            'permission' => $permission,
            'created_at' => $now,
            'updated_at' => $now,
        ], $valid);

        if ($rows !== []) {
            DB::table('role_permissions')->insert($rows);
        }

        self::forgetCache($role);
    }

    public static function roleHas(string $role, string $permission): bool
    {
        if ($role === 'super_admin') {
            return true;
        }

        return in_array($permission, self::forRole($role), true);
    }
}
