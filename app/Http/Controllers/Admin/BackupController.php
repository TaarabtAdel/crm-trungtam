<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Support\TenantContext;
use App\Support\TenantStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(protected DatabaseBackupService $backups) {}

    public function index()
    {
        $files = $this->backups->list();
        $tenant = TenantStorage::slug();
        $database = TenantContext::databaseName() ?: config('database.connections.mysql.database');

        return view('admin.system.backups', compact('files', 'tenant', 'database'));
    }

    public function store()
    {
        try {
            $result = $this->backups->create();
        } catch (\Throwable $e) {
            return back()->with('error', 'Không tạo được backup: '.$e->getMessage());
        }

        return back()->with(
            'success',
            'Đã tạo backup: '.$result['filename'].' ('.$this->humanSize($result['size']).').'
        );
    }

    public function download(string $filename): BinaryFileResponse
    {
        $path = $this->backups->absolutePath($filename);
        abort_unless($path, 404);

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function destroy(Request $request, string $filename)
    {
        if (! $this->backups->delete($filename)) {
            return back()->with('error', 'Không tìm thấy file backup.');
        }

        return back()->with('success', 'Đã xóa file backup.');
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }
}
