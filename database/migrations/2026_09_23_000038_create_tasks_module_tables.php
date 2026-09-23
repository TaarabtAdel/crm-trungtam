<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
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
        }

        if (! Schema::hasTable('task_watchers')) {
            Schema::create('task_watchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['task_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('task_checklist_items')) {
            Schema::create('task_checklist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->string('title');
                $table->boolean('is_done')->default(false);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_subtasks')) {
            Schema::create('task_subtasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->string('title');
                $table->boolean('is_done')->default(false);
                $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_comments')) {
            Schema::create('task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->json('mentions')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_attachments')) {
            Schema::create('task_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('path');
                $table->string('original_name');
                $table->string('mime', 120)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('task_activity_logs')) {
            Schema::create('task_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 64);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->index(['task_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('task_reminder_logs')) {
            Schema::create('task_reminder_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->string('reminder_type', 32);
                $table->timestamp('sent_at');
                $table->unique(['task_id', 'reminder_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminder_logs');
        Schema::dropIfExists('task_activity_logs');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_subtasks');
        Schema::dropIfExists('task_checklist_items');
        Schema::dropIfExists('task_watchers');
        Schema::dropIfExists('tasks');
    }
};
