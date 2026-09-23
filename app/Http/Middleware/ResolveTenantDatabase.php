<?php

namespace App\Http\Middleware;

use App\Support\AppSettings;
use App\Support\InstallState;
use App\Support\SmartCache;
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
        $purgeCache = $this->wantsPurgeCache($request);

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
            $this->afterTenantReady(purgeCache: $purgeCache);

            return $this->respond($request, $next, $purgeCache);
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
                $this->afterTenantReady(skipMail: true, purgeCache: $purgeCache);

                return $this->respond($request, $next, $purgeCache);
            }

            abort(
                403,
                'Cơ sở dữ liệu trung tâm không tồn tại hoặc tài khoản MySQL chưa có quyền truy cập: '.$databaseName
                .' — tạo DB trống trên cPanel rồi mở /install trên subdomain này.'
            );
        }

        TenantDatabase::connect($databaseName);
        $this->afterTenantReady(skipMail: $isInstall && ! InstallState::schemaReady(), purgeCache: $purgeCache);

        return $this->respond($request, $next, $purgeCache);
    }

    protected function wantsPurgeCache(Request $request): bool
    {
        $v = $request->query('remove_cache');

        return $v === '1' || $v === 1 || $v === true || $v === 'true';
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    protected function respond(Request $request, Closure $next, bool $purgeCache): Response
    {
        if ($purgeCache) {
            return redirect()->to($request->fullUrlWithoutQuery(['remove_cache']));
        }

        return $next($request);
    }

    protected function afterTenantReady(bool $skipMail = false, bool $purgeCache = false): void
    {
        $slug = TenantContext::subdomain() ?: 'local';
        $prefix = rtrim((string) config('cache.prefix'), '_');
        config(['cache.prefix' => ($prefix !== '' ? $prefix.'_' : 'crm_').$slug.'_']);

        TenantStorage::configureDisk();

        if ($purgeCache) {
            try {
                SmartCache::flushAll();
            } catch (\Throwable) {
                //
            }
        }

        if ($skipMail) {
            return;
        }

        try {
            $this->ensureSchemaUpToDate();
        } catch (\Throwable) {
            //
        }

        try {
            AppSettings::applyMailConfig();
        } catch (\Throwable) {
            //
        }
    }

    protected function ensureSchemaUpToDate(): void
    {
        $target = (string) config('app.schema_version', '1.0');

        // Đã xác nhận OK trong 5 phút → bỏ qua query settings / hasTable
        if (SmartCache::isSchemaUpToDateCached($target)) {
            return;
        }

        if (! InstallState::schemaReady()) {
            return;
        }

        $current = (string) \App\Models\Setting::get('app_schema_version', '0');
        if (version_compare($current, $target, '>=')) {
            SmartCache::markSchemaUpToDate($target);

            return;
        }

        $result = app(\App\Services\Install\SchemaUpdateService::class)->run();
        if (($result['success'] ?? false) === true) {
            SmartCache::markSchemaUpToDate($target);
        }
    }
}
