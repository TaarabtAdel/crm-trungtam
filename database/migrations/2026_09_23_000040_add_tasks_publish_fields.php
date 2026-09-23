<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        $added = false;

        if (! Schema::hasColumn('tasks', 'is_published')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->boolean('is_published')->default(false)->after('completed_at');
            });
            $added = true;
        }

        if (! Schema::hasColumn('tasks', 'published_at')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dateTime('published_at')->nullable()->after('is_published');
            });
            $added = true;
        }

        // Chỉ backfill khi lần đầu thêm cột: việc cũ coi như đã công bố
        if ($added) {
            DB::table('tasks')->update([
                'is_published' => true,
                'published_at' => DB::raw('COALESCE(created_at, NOW())'),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'published_at')) {
                $table->dropColumn('published_at');
            }
            if (Schema::hasColumn('tasks', 'is_published')) {
                $table->dropColumn('is_published');
            }
        });
    }
};
