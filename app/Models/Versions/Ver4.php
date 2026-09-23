<?php

namespace App\Models\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks: nháp / công bố.
 */
class Ver4
{
    /**
     * @return array{success:bool,message:string}
     */
    public static function doUpdate(): array
    {
        try {
            Schema::defaultStringLength(191);

            if (! Schema::hasTable('tasks')) {
                return ['success' => true, 'message' => ''];
            }

            $added = false;

            if (! Schema::hasColumn('tasks', 'is_published')) {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->boolean('is_published')->default(false);
                });
                $added = true;
            }

            if (! Schema::hasColumn('tasks', 'published_at')) {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->dateTime('published_at')->nullable();
                });
                $added = true;
            }

            if ($added) {
                DB::table('tasks')->update([
                    'is_published' => true,
                    'published_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]);
            }

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
