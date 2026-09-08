<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class TenantStorage
{
    public static function slug(): string
    {
        $slug = TenantContext::subdomain() ?: 'local';

        return preg_replace('/[^a-z0-9_-]/i', '', $slug) ?: 'local';
    }

    public static function rootPath(): string
    {
        return public_path('storage/'.self::slug());
    }

    public static function ensureRoot(): string
    {
        $root = self::rootPath();
        if (! is_dir($root)) {
            mkdir($root, 0755, true);
        }

        $htaccess = $root.DIRECTORY_SEPARATOR.'.htaccess';
        if (! is_file($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\n");
        }

        $backups = $root.DIRECTORY_SEPARATOR.'backups';
        if (! is_dir($backups)) {
            mkdir($backups, 0755, true);
        }

        $deny = $backups.DIRECTORY_SEPARATOR.'.htaccess';
        if (! is_file($deny)) {
            file_put_contents($deny, "Require all denied\nDeny from all\n");
        }

        return $root;
    }

    public static function configureDisk(): void
    {
        $slug = self::slug();
        $root = self::ensureRoot();
        $baseUrl = rtrim((string) config('app.url'), '/');

        config([
            'filesystems.disks.public.root' => $root,
            'filesystems.disks.public.url' => $baseUrl.'/storage/'.$slug,
        ]);

        // Reset cached disk instance nếu đã resolve
        Storage::forgetDisk('public');
    }

    public static function disk(): Filesystem
    {
        return Storage::disk('public');
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return asset('storage/'.self::slug().'/'.ltrim($path, '/'));
    }

    public static function backupsPath(): string
    {
        self::ensureRoot();

        return self::rootPath().DIRECTORY_SEPARATOR.'backups';
    }
}
