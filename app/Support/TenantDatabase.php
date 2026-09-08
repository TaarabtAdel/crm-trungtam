<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class TenantDatabase
{
    /**
     * Kiểm tra DB tenant có kết nối được không.
     * Dùng kết nối trực tiếp thay vì SHOW DATABASES (shared hosting thường chặn).
     */
    public static function exists(string $databaseName): bool
    {
        if ($databaseName === '') {
            return false;
        }

        $probeConnection = 'tenant_probe';

        try {
            $base = config('database.connections.mysql');

            config([
                "database.connections.{$probeConnection}" => array_merge($base, [
                    'database' => $databaseName,
                ]),
            ]);

            DB::connection($probeConnection)->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        } finally {
            DB::purge($probeConnection);
        }
    }

    public static function connect(string $databaseName): void
    {
        if ($databaseName === '') {
            return;
        }

        config(['database.connections.mysql.database' => $databaseName]);
        DB::purge('mysql');
        DB::reconnect('mysql');
    }
}
