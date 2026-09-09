<?php

namespace App\Http\Middleware;

use App\Support\AppSettings;
use App\Support\InstallState;
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
        $isInstall = $request->is('install') || $request->is('install/*');

        // .env mặc định SESSION/CACHE=database, nhưng lúc /install bảng chưa có.
        if ($isInstall) {
            config([
                'session.driver' => 'file',
                'cache.default' => 'file',
            ]);
        }

        if (! config('tenant.tenant_resolve')) {
            $subdomain = TenantHostResolver::resolve($request, $baseDomain) ?? 'local';
            $databaseName = (string) config('database.connections.mysql.database');
            TenantContext::set($subdomain, $databaseName);
            $this->afterTenantReady();

            return $next($request);
        }

        $subdomain = TenantHostResolver::resolve($request, $baseDomain);

        if ($subdomain === null) {
            if ($isInstall) {
                // Cho phép /install trên domain gốc khi chưa biết tenant (hiếm)
                return $next($request);
            }

            abort(
                403,
                'Không xác định được trung tâm từ tên miền. Truy cập qua subdomain, ví dụ: tpt-academy.'.config('tenant.base_domain')
            );
        }

        if (! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $subdomain)) {
            abort(403, 'Subdomain không hợp lệ: '.$subdomain);
        }

        $databaseName = config('tenant.database_prefix').$subdomain;
        TenantContext::set($subdomain, $databaseName);

        if (! TenantDatabase::exists($databaseName)) {
            if ($isInstall) {
                // DB chưa tạo trên panel — wizard vẫn mở, form sẽ báo khi test kết nối
                $this->afterTenantReady(skipMail: true);

                return $next($request);
            }

            abort(
                403,
                'Cơ sở dữ liệu trung tâm không tồn tại hoặc tài khoản MySQL chưa có quyền truy cập: '.$databaseName
                .' — tạo DB trống trên cPanel rồi mở /install trên subdomain này.'
            );
        }

        TenantDatabase::connect($databaseName);
        $this->afterTenantReady(skipMail: $isInstall && ! InstallState::schemaReady());

        return $next($request);
    }

    protected function afterTenantReady(bool $skipMail = false): void
    {
        $slug = TenantContext::subdomain() ?: 'local';
        $prefix = rtrim((string) config('cache.prefix'), '_');
        config(['cache.prefix' => ($prefix !== '' ? $prefix.'_' : 'crm_').$slug.'_']);

        TenantStorage::configureDisk();

        if ($skipMail) {
            return;
        }

        try {
            AppSettings::applyMailConfig();
        } catch (\Throwable) {
            //
        }
    }
}
