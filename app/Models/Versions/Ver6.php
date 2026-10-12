<?php

namespace App\Models\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * class_student: ngày vào / ngày nghỉ học (pro-rata học phí & tình hình học tập).
 */
class Ver6
{
    /**
     * @return array{success:bool,message:string}
     */
    public static function doUpdate(): array
    {
        try {
            Schema::defaultStringLength(191);

            if (! Schema::hasTable('class_student')) {
                return ['success' => true, 'message' => ''];
            }

            if (! Schema::hasColumn('class_student', 'enrolled_from')) {
                Schema::table('class_student', function (Blueprint $table) {
                    $table->date('enrolled_from')->nullable()->after('student_id');
                });
            }

            if (! Schema::hasColumn('class_student', 'enrolled_to')) {
                Schema::table('class_student', function (Blueprint $table) {
                    $table->date('enrolled_to')->nullable()->after('enrolled_from');
                });
            }

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
