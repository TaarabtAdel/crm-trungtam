<?php

namespace App\Models\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema trạng thái hiện tại — dùng cho wizard /install (cPanel, không cần artisan migrate).
 */
class Ver1
{
    public static function doUpdate(): bool
    {
        try {
            static::createLaravelSystemTables();
            static::createCoreTables();
            static::createCrmTables();
            static::createTrainingTables();
            static::createFinanceTables();
            static::createNotificationTables();
            static::createPayrollTables();

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    protected static function createLaravelSystemTables(): void
    {
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    protected static function createCoreTables(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('bank_bin', 20)->nullable();
                $table->string('bank_account_number', 50)->nullable();
                $table->string('bank_account_name', 120)->nullable();
                $table->string('bank_name', 120)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('admin');
                $table->string('phone')->nullable();
                $table->decimal('daily_rate', 12, 0)->default(0);
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('role', 50);
                $table->string('permission', 100);
                $table->timestamps();
                $table->unique(['role', 'permission']);
            });
        }

        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50);
                $table->timestamps();
                $table->unique(['user_id', 'role']);
                $table->index('role');
            });
        }
    }

    protected static function createCrmTables(): void
    {
        if (! Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->text('description')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('name');
                $table->date('dob')->nullable();
                $table->string('gender')->nullable();
                $table->string('phone', 30)->nullable();
                $table->string('email')->nullable();
                $table->string('parent_phone')->nullable();
                $table->string('parent_name')->nullable();
                $table->string('parent_email')->nullable();
                $table->string('status')->default('studying');
                $table->text('address')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('related_name')->nullable();
                $table->string('related_phone', 30)->nullable();
                $table->string('related_email')->nullable();
                $table->string('source')->nullable();
                $table->foreignId('interest_subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('interest_level', 50)->nullable();
                $table->string('interest_note')->nullable();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->decimal('expected_revenue', 14, 0)->default(0);
                $table->foreignId('assigned_sales_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->string('status')->default('new');
                $table->date('follow_up_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('interactions')) {
            Schema::create('interactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('type');
                $table->dateTime('scheduled_at')->nullable();
                $table->string('status')->default('upcoming');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    protected static function createTrainingTables(): void
    {
        if (! Schema::hasTable('teachers')) {
            Schema::create('teachers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('specialty')->nullable();
                $table->string('qualification')->nullable();
                $table->decimal('hourly_rate', 12, 0)->default(0);
                $table->date('joined_at')->nullable();
                $table->text('notes')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('classes')) {
            Schema::create('classes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
                $table->decimal('teacher_hourly_rate', 12, 0)->nullable();
                $table->json('schedule_days')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('room')->nullable();
                $table->unsignedInteger('max_students')->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('tuition_fee', 12, 0)->default(0);
                $table->string('tuition_type')->default('monthly');
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_student')) {
            Schema::create('class_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['class_id', 'student_id']);
            });
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->date('session_date');
                $table->string('status')->default('present');
                $table->string('note')->nullable();
                $table->timestamps();
                $table->unique(['class_id', 'student_id', 'session_date']);
            });
        }

        if (! Schema::hasTable('class_sessions')) {
            Schema::create('class_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
                $table->date('session_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('status')->default('scheduled');
                $table->text('notes')->nullable();
                $table->foreignId('makeup_of_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_session_journals')) {
            Schema::create('class_session_journals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_session_id')->unique()->constrained('class_sessions')->cascadeOnDelete();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->date('session_date');
                $table->string('class_name');
                $table->unsignedInteger('enrollment_count')->default(0);
                $table->unsignedInteger('present_count')->default(0);
                $table->unsignedInteger('absent_count')->default(0);
                $table->unsignedInteger('excused_count')->default(0);
                $table->unsignedInteger('late_count')->default(0);
                $table->string('lesson_title')->nullable();
                $table->text('content')->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('filled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('filled_at')->nullable();
                $table->timestamps();
                $table->index(['class_id', 'session_date']);
            });
        }

        if (! Schema::hasTable('placement_tests')) {
            Schema::create('placement_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignId('interaction_id')->nullable()->constrained('interactions')->nullOnDelete();
                $table->foreignId('recommended_class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('score', 8, 2)->nullable();
                $table->string('level', 50)->nullable();
                $table->date('tested_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['lead_id', 'tested_at']);
                $table->index(['student_id', 'tested_at']);
            });
        }
    }

    protected static function createFinanceTables(): void
    {
        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->nullable()->unique();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('sales_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('amount', 14, 0)->default(0);
                $table->decimal('gross_amount', 14, 0)->nullable();
                $table->decimal('discount_amount', 14, 0)->default(0);
                $table->string('discount_reason')->nullable();
                $table->decimal('paid_amount', 14, 0)->default(0);
                $table->decimal('remaining_amount', 14, 0)->default(0);
                $table->string('billing_month', 7)->nullable();
                $table->unsignedInteger('sessions_count')->nullable();
                $table->json('billed_session_ids')->nullable();
                $table->string('fee_type')->nullable();
                $table->unsignedTinyInteger('installment_count')->default(1);
                $table->string('status')->default('unpaid');
                $table->date('due_date')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('payment_installments')) {
            Schema::create('payment_installments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->unsignedSmallInteger('sequence')->default(1);
                $table->decimal('amount', 14, 0)->default(0);
                $table->decimal('paid_amount', 14, 0)->default(0);
                $table->date('due_date')->nullable();
                $table->string('status')->default('unpaid');
                $table->timestamps();
                $table->unique(['invoice_id', 'sequence']);
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('installment_id')->nullable()->constrained('payment_installments')->nullOnDelete();
                $table->decimal('amount', 14, 0);
                $table->string('method')->default('cash');
                $table->dateTime('paid_at');
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->string('receipt_path')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->decimal('amount', 14, 0);
                $table->text('reason')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending');
                $table->dateTime('processed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('billing_month', 7)->nullable();
                $table->string('category')->default('other');
                $table->decimal('amount', 14, 0);
                $table->date('expense_date');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending');
                $table->text('note')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commission_rules')) {
            Schema::create('commission_rules', function (Blueprint $table) {
                $table->id();
                $table->string('scope')->default('global');
                $table->foreignId('class_id')->nullable()->constrained('classes')->cascadeOnDelete();
                $table->decimal('percent', 5, 2)->default(0);
                $table->decimal('tier_min_revenue', 14, 0)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('commissions')) {
            Schema::create('commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->decimal('percent', 5, 2)->default(0);
                $table->decimal('amount', 14, 0)->default(0);
                $table->string('status')->default('unpaid');
                $table->dateTime('paid_at')->nullable();
                $table->timestamps();
                $table->unique(['sales_id', 'invoice_id']);
            });
        }

        if (! Schema::hasTable('debt_reminders')) {
            Schema::create('debt_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->string('channel');
                $table->dateTime('sent_at');
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    protected static function createNotificationTables(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notification_templates')) {
            Schema::create('notification_templates', function (Blueprint $table) {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('title');
                $table->text('content_email')->nullable();
                $table->string('email_subject')->nullable();
                $table->string('zalo_template_id', 64)->nullable();
                $table->json('params_mapping')->nullable();
                $table->boolean('is_active_email')->default(true);
                $table->boolean('is_active_zalo')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->string('template_code', 64)->nullable()->index();
                $table->string('channel', 20);
                $table->string('recipient_type', 20)->nullable();
                $table->string('recipient_name')->nullable();
                $table->string('recipient_contact')->nullable();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
                $table->foreignId('class_session_id')->nullable()->constrained('class_sessions')->nullOnDelete();
                $table->string('status', 20)->default('pending');
                $table->text('request_payload')->nullable();
                $table->text('response_body')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }
    }

    protected static function createPayrollTables(): void
    {
        if (! Schema::hasTable('staff_attendances')) {
            Schema::create('staff_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->date('work_date');
                $table->string('status', 20)->default('present');
                $table->string('note')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'work_date']);
                $table->index(['work_date', 'status']);
            });
        }

        if (! Schema::hasTable('payroll_adjustments')) {
            Schema::create('payroll_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('scope', 16);
                $table->foreignId('teacher_id')->nullable()->constrained('teachers')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('billing_month', 7);
                $table->string('type', 16);
                $table->decimal('amount', 15, 0);
                $table->text('note');
                $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
                $table->uuid('batch_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['scope', 'billing_month']);
                $table->index(['teacher_id', 'billing_month']);
                $table->index(['user_id', 'billing_month']);
            });
        }
    }
}
