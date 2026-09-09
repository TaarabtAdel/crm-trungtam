<?php

namespace App\Services\Install;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DatabaseConfigurer
{
    /**
     * Áp DB runtime + ghi .env (shared credentials).
     * Multi-tenant: không ghi đè DB_DATABASE / TENANT_RESOLVE (mỗi subdomain một DB riêng).
     *
     * @param  array{host:string,port:int|string,database:string,username:string,password?:string,app_url?:string,app_debug?:bool}  $config
     */
    public function apply(array $config, EnvWriter $env): void
    {
        $host = (string) $config['host'];
        $port = (int) $config['port'];
        $database = (string) $config['database'];
        $username = (string) $config['username'];
        $password = (string) ($config['password'] ?? '');
        $appUrl = (string) ($config['app_url'] ?? config('app.url'));
        $appDebug = (bool) ($config['app_debug'] ?? false);
        $tenantMode = (bool) config('tenant.tenant_resolve');

        Config::set('database.connections.mysql.host', $host);
        Config::set('database.connections.mysql.port', $port);
        Config::set('database.connections.mysql.database', $database);
        Config::set('database.connections.mysql.username', $username);
        Config::set('database.connections.mysql.password', $password);
        DB::purge('mysql');
        DB::reconnect('mysql');

        $payload = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'APP_URL' => $appUrl,
            'APP_DEBUG' => $appDebug ? 'true' : 'false',
            'APP_ENV' => $appDebug ? 'local' : 'production',
        ];

        if (! $tenantMode) {
            $payload['DB_DATABASE'] = $database;
            $payload['TENANT_RESOLVE'] = 'false';
        }

        $env->setMany($payload);

        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }
    }
}
