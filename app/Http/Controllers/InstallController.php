<?php

namespace App\Http\Controllers;

use App\Services\Install\AdminBootstrapper;
use App\Services\Install\DatabaseConfigurer;
use App\Services\Install\DatabaseTester;
use App\Services\Install\EnvWriter;
use App\Services\Install\SchemaUpdateService;
use App\Support\InstallState;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class InstallController extends Controller
{
    public function index()
    {
        $checks = [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'json' => extension_loaded('json'),
            'storage' => is_writable(storage_path()) && is_writable(storage_path('app')),
            'env_writable' => is_writable(base_path()) || (is_file(base_path('.env')) && is_writable(base_path('.env'))),
        ];

        $ready = ! in_array(false, $checks, true);
        $tenantDb = TenantContext::databaseName();

        return view('install.index', compact('checks', 'ready', 'tenantDb'));
    }

    public function showDatabase()
    {
        if (InstallState::schemaReady() && ! InstallState::isInstalled()) {
            return redirect()->route('install.admin')
                ->with('success', 'Cơ sở dữ liệu đã có bảng. Tiếp tục tạo tài khoản admin.');
        }

        $tenantMode = (bool) config('tenant.tenant_resolve');
        $tenantDb = TenantContext::databaseName();

        return view('install.database', [
            'tenantMode' => $tenantMode,
            'defaults' => [
                'host' => old('host', env('DB_HOST', '127.0.0.1')),
                'port' => old('port', env('DB_PORT', '3306')),
                'database' => old('database', $tenantDb ?: env('DB_DATABASE', '')),
                'username' => old('username', env('DB_USERNAME', '')),
                'app_url' => old('app_url', url('/')),
            ],
        ]);
    }

    public function storeDatabase(
        Request $request,
        DatabaseTester $tester,
        DatabaseConfigurer $configurer,
        EnvWriter $env,
        SchemaUpdateService $schema
    ) {
        $tenantMode = (bool) config('tenant.tenant_resolve');
        $tenantDb = TenantContext::databaseName();

        $data = $request->validate([
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string|max:64',
            'username' => 'required|string|max:64',
            'password' => 'nullable|string|max:255',
            'app_url' => 'required|url|max:255',
            'app_debug' => 'nullable|boolean',
        ]);

        if ($tenantMode && $tenantDb && $data['database'] !== $tenantDb) {
            return back()->withInput()->with(
                'error',
                'Multi-tenant: database phải là "'.$tenantDb.'" (theo subdomain hiện tại).'
            );
        }

        $test = $tester->test([
            'host' => $data['host'],
            'port' => $data['port'],
            'database' => $data['database'],
            'username' => $data['username'],
            'password' => $data['password'] ?? '',
        ]);

        if (! $test['ok']) {
            return back()->withInput()->with('error', $test['message']);
        }

        try {
            $configurer->apply([
                'host' => $data['host'],
                'port' => $data['port'],
                'database' => $data['database'],
                'username' => $data['username'],
                'password' => $data['password'] ?? '',
                'app_url' => $data['app_url'],
                'app_debug' => $request->boolean('app_debug'),
            ], $env);

            $result = $schema->run('1.0');
            if (! $result['success']) {
                return back()->withInput()->with('error', $result['message']);
            }
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Lỗi khi ghi cấu hình / tạo bảng: '.$e->getMessage());
        }

        return redirect()->route('install.admin')
            ->with('success', 'Đã kết nối DB và tạo bảng thành công.');
    }

    public function showAdmin()
    {
        if (! InstallState::schemaReady()) {
            return redirect()->route('install.database')
                ->with('error', 'Chưa tạo xong bảng. Vui lòng cấu hình database trước.');
        }

        return view('install.admin');
    }

    public function storeAdmin(Request $request, AdminBootstrapper $bootstrapper)
    {
        if (! InstallState::schemaReady()) {
            return redirect()->route('install.database');
        }

        $data = $request->validate([
            'center_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $bootstrapper->bootstrap($data);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Không tạo được tài khoản admin: '.$e->getMessage());
        }

        return redirect()->route('install.done');
    }

    public function done()
    {
        if (! InstallState::isInstalled()) {
            return redirect()->route('install.index');
        }

        return view('install.done');
    }
}
