<?php

namespace App\Http\Middleware;

use App\Support\AppSettings;
use App\Support\TenantContext;
use App\Support\TenantDatabase;
use App\Support\TenantHostResolver;
use App\Support\TenantStorage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantDatabase
{
    public function handle(Request $request, Closure $next): Response
    {
        $baseDomain = (string) config('tenant.base_domain');

        if (! config('tenant.tenant_resolve')) {
            $subdomain = TenantHostResolver::resolve($request, $baseDomain) ?? 'local';
            $databaseName = (string) config('database.connections.mysql.database');
            TenantContext::set($subdomain, $databaseName);
            $this->afterTenantReady();

            return $next($request);
        }

        $subdomain = TenantHostResolver::resolve($request, $baseDomain);

        if ($subdomain === null) {
            abort(
                403,
                'Không xác định được trung tâm từ tên miền. Truy cập qua subdomain, ví dụ: tpt-academy.'.config('tenant.base_domain')
            );
        }

        if (! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
            abort(403, 'Subdomain không hợp lệ: '.$subdomain);
        }

        $databaseName = config('tenant.database_prefix').$subdomain;

        if (! TenantDatabase::exists($databaseName)) {
            abort(
                403,
                'Cơ sở dữ liệu trung tâm không tồn tại hoặc tài khoản MySQL chưa có quyền truy cập: '.$databaseName
            );
        }

        TenantDatabase::connect($databaseName);
        TenantContext::set($subdomain, $databaseName);
        $this->afterTenantReady();

        return $next($request);
    }

    protected function afterTenantReady(): void
    {
        $slug = TenantContext::subdomain() ?: 'local';
        $prefix = rtrim((string) config('cache.prefix'), '_');
        config(['cache.prefix' => ($prefix !== '' ? $prefix.'_' : 'crm_').$slug.'_']);

        TenantStorage::configureDisk();

        try {
            AppSettings::applyMailConfig();
        } catch (\Throwable) {
            //
        }
    }
}
