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

            if (Schema::hasColumn('class_student', 'enrolled_from')
                && Schema::hasColumn('class_student', 'created_at')) {
                DB::table('class_student')
                    ->whereNull('enrolled_from')
                    ->orderBy('id')
                    ->chunkById(200, function ($rows) {
                        foreach ($rows as $row) {
                            $from = $row->created_at
                                ? \Carbon\Carbon::parse($row->created_at)->toDateString()
                                : now()->toDateString();
                            DB::table('class_student')->where('id', $row->id)->update(['enrolled_from' => $from]);
                        }
                    });
            }

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
