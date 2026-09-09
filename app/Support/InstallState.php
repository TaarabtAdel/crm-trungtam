<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trạng thái cài đặt theo database hiện tại (multi-tenant: mỗi subdomain một DB).
 * Không dùng file lock vì nhiều subdomain chung một thư mục code.
 */
class InstallState
{
    /**
     * Đã cài = đã có bảng users + settings và có ít nhất 1 user (admin).
     */
    public static function isInstalled(): bool
    {
        try {
            return Schema::hasTable('users')
                && Schema::hasTable('settings')
                && DB::table('users')->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Đã tạo schema (có thể chưa có admin).
     */
    public static function schemaReady(): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
