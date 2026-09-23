<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        if (! Schema::hasColumn('tasks', 'source_type')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('source_type', 64)->nullable()->after('published_at');
            });
        }

        if (! Schema::hasColumn('tasks', 'source_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            });
        }

        try {
            Schema::table('tasks', function (Blueprint $table) {
                $table->index(['source_type', 'source_id'], 'tasks_source_index');
            });
        } catch (\Throwable) {
            // index already exists
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        try {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_source_index');
            });
        } catch (\Throwable) {
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'source_id')) {
                $table->dropColumn('source_id');
            }
            if (Schema::hasColumn('tasks', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
