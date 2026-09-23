<?php

namespace App\Models\Versions;

use App\Support\Permissions;

/**
 * Bổ sung quyền mặc định còn thiếu (tasks.* và các key mới trong config).
 * Ver3 có thể đã chạy trước khi seed permissions → hosting thiếu tasks.view → 403.
 */
class Ver6
{
    /**
     * @return array{success:bool,message:string}
     */
    public static function doUpdate(): array
    {
        try {
            Permissions::ensureDefaults();

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
