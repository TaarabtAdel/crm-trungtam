<?php

namespace App\Providers;

use App\Support\TenantContext;
use App\Support\TenantDatabase;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;

/**
 * Multi-tenant: subdomain → database (xem config/tenant.php + ResolveTenantDatabase).
 * Class giữ helper tham chiếu giống quanlythietbitruonghoc.
 */
class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/admin';

    public function boot(): void
    {
        //
    }

    public function getDatabaseName(?Request $request = null): string
    {
        $request ??= request();
        $host = strtolower($request->getHost());
        $baseDomain = strtolower((string) config('tenant.base_domain'));

        if (str_ends_with($host, '.'.$baseDomain)) {
            $subdomain = explode('.', substr($host, 0, -strlen('.'.$baseDomain)))[0];
        } else {
            $subdomain = explode('.', $host)[0];
        }

        return config('tenant.database_prefix').$subdomain;
    }

    public function checkDatabaseExist(string $databaseName): bool
    {
        return TenantDatabase::exists($databaseName);
    }

    public function currentTenantSlug(): ?string
    {
        return TenantContext::subdomain();
    }
}
