<?php

namespace App\Models\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm cột bài tập về nhà vào nhật ký buổi học.
 */
class Ver2
{
    /**
     * @return array{success:bool,message:string}
     */
    public static function doUpdate(): array
    {
        try {
            if (Schema::hasTable('class_session_journals')
                && ! Schema::hasColumn('class_session_journals', 'homework')) {
                Schema::table('class_session_journals', function (Blueprint $table) {
                    $table->text('homework')->nullable()->after('remarks');
                });
            }

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
