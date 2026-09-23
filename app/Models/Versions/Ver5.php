<?php

namespace App\Models\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tasks: source_type / source_id cho việc tự động từ nghiệp vụ.
 */
class Ver5
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

            if (! Schema::hasColumn('tasks', 'source_type')) {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->string('source_type', 64)->nullable();
                });
            }

            if (! Schema::hasColumn('tasks', 'source_id')) {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->unsignedBigInteger('source_id')->nullable();
                });
            }

            try {
                Schema::table('tasks', function (Blueprint $table) {
                    $table->index(['source_type', 'source_id'], 'tasks_source_index');
                });
            } catch (\Throwable) {
            }

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
