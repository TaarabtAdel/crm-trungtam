<?php

namespace App\Models\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Công việc (tasks).
 */
class Ver3
{
    /**
     * @return array{success:bool,message:string}
     */
    public static function doUpdate(): array
    {
        try {
            Schema::defaultStringLength(191);

            static::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('creator_id')->index();
                $table->foreignId('assignee_id')->nullable()->index();
                $table->foreignId('branch_id')->nullable()->index();
                $table->string('status', 32)->default('todo')->index();
                $table->string('priority', 16)->default('medium')->index();
                $table->date('start_date')->nullable();
                $table->dateTime('due_date')->nullable()->index();
                $table->unsignedInteger('position')->default(0);
                $table->dateTime('completed_at')->nullable();
                $table->boolean('is_published')->default(false);
                $table->dateTime('published_at')->nullable();
                $table->timestamps();
                $table->index(['assignee_id', 'status']);
                $table->index(['creator_id', 'status']);
            });

            static::create('task_watchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->index();
                $table->foreignId('user_id')->index();
                $table->timestamps();
                $table->unique(['task_id', 'user_id']);
            });

            static::create('task_checklist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->index();
                $table->string('title');
                $table->boolean('is_done')->default(false);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });

            static::create('task_subtasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->index();
                $table->string('title');
                $table->boolean('is_done')->default(false);
                $table->foreignId('assignee_id')->nullable()->index();
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });

            static::create('task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->index();
                $table->foreignId('user_id')->index();
                $table->text('body');
                $table->json('mentions')->nullable();
                $table->timestamps();
            });

            static::create('task_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->index();
                $table->foreignId('user_id')->index();
                $table->string('path');
                $table->string('original_name');
                $table->string('mime', 120)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->timestamps();
            });

            static::create('task_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->index();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('action', 64);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['task_id', 'created_at']);
            });

            static::create('task_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->index();
                $table->string('reminder_type', 32);
                $table->timestamp('sent_at');
                $table->unique(['task_id', 'reminder_type']);
            });

            static::seedPermissions();

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected static function seedPermissions(): void
    {
        if (class_exists(\App\Support\Permissions::class)) {
            \App\Support\Permissions::ensureDefaults();
        }
    }

    protected static function create(string $table, callable $callback): void
    {
        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $blueprint) use ($callback) {
            $blueprint->engine = 'InnoDB';
            $callback($blueprint);
        });
    }
}
