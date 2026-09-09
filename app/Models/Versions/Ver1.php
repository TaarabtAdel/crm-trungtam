<?php

namespace App\Models\Versions;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema trạng thái hiện tại — dùng cho wizard /install (cPanel, không cần artisan migrate).
 *
 * Không tạo FOREIGN KEY DB-level: hosting MySQL/MariaDB thường MyISAM mặc định
 * hoặc thiếu quyền REFERENCES → errno 150. Cột *_id vẫn là unsignedBigInteger + index.
 */
class Ver1
{
    /**
     * @return array{success:bool,message:string}
     */
    public static function doUpdate(): array
    {
        try {
            Schema::defaultStringLength(191);

            static::createLaravelSystemTables();
            static::createCoreTables();
            static::createCrmTables();
            static::createTrainingTables();
            static::createFinanceTables();
            static::createNotificationTables();
            static::createPayrollTables();

            return ['success' => true, 'message' => ''];
        } catch (\Throwable $e) {
            report($e);

            return ['success' => false, 'message' => $e->getMessage()];
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

    protected static function createLaravelSystemTables(): void
    {
        static::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        static::create('sessions', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        static::create('cache', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        static::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 191)->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        static::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        static::create('job_batches', function (Blueprint $table) {
            $table->string('id', 191)->primary();
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

        static::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 191)->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    protected static function createCoreTables(): void
    {
        static::create('branches', function (Blueprint $table) {
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

        static::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->string('phone')->nullable();
            $table->decimal('daily_rate', 12, 0)->default(0);
            $table->foreignId('branch_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        static::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        static::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 50);
            $table->string('permission', 100);
            $table->timestamps();
            $table->unique(['role', 'permission']);
        });

        static::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('role', 50);
            $table->timestamps();
            $table->unique(['user_id', 'role']);
            $table->index('role');
        });
    }

    protected static function createCrmTables(): void
    {
        static::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('branch_id')->index();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        static::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index();
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

        static::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('related_name')->nullable();
            $table->string('related_phone', 30)->nullable();
            $table->string('related_email')->nullable();
            $table->string('source')->nullable();
            $table->foreignId('interest_subject_id')->nullable()->index();
            $table->string('interest_level', 50)->nullable();
            $table->string('interest_note')->nullable();
            $table->foreignId('branch_id')->index();
            $table->decimal('expected_revenue', 14, 0)->default(0);
            $table->foreignId('assigned_sales_id')->nullable()->index();
            $table->foreignId('student_id')->nullable()->index();
            $table->string('status')->default('new');
            $table->date('follow_up_at')->nullable();
            $table->timestamps();
        });

        static::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->index();
            $table->foreignId('sales_id')->nullable()->index();
            $table->foreignId('branch_id')->nullable()->index();
            $table->string('type');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('upcoming');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    protected static function createTrainingTables(): void
    {
        static::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->string('phone')->nullable();
            $table->string('specialty')->nullable();
            $table->string('qualification')->nullable();
            $table->decimal('hourly_rate', 12, 0)->default(0);
            $table->date('joined_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        static::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('subject_id')->nullable()->index();
            $table->foreignId('teacher_id')->nullable()->index();
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

        static::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->index();
            $table->foreignId('student_id')->index();
            $table->timestamps();
            $table->unique(['class_id', 'student_id']);
        });

        static::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->index();
            $table->foreignId('student_id')->index();
            $table->date('session_date');
            $table->string('status')->default('present');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['class_id', 'student_id', 'session_date']);
        });

        static::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->index();
            $table->foreignId('teacher_id')->nullable()->index();
            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->foreignId('makeup_of_session_id')->nullable()->index();
            $table->timestamps();
        });

        static::create('class_session_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->unique();
            $table->foreignId('class_id')->index();
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
            $table->foreignId('filled_by')->nullable()->index();
            $table->timestamp('filled_at')->nullable();
            $table->timestamps();
            $table->index(['class_id', 'session_date']);
        });

        static::create('placement_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->index();
            $table->foreignId('student_id')->nullable()->index();
            $table->foreignId('subject_id')->nullable()->index();
            $table->foreignId('interaction_id')->nullable()->index();
            $table->foreignId('recommended_class_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('level', 50)->nullable();
            $table->date('tested_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'tested_at']);
            $table->index(['student_id', 'tested_at']);
        });
    }

    protected static function createFinanceTables(): void
    {
        static::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->nullable()->unique();
            $table->foreignId('student_id')->index();
            $table->foreignId('class_id')->nullable()->index();
            $table->foreignId('branch_id')->nullable()->index();
            $table->foreignId('sales_id')->nullable()->index();
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

        static::create('payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->index();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->decimal('amount', 14, 0)->default(0);
            $table->decimal('paid_amount', 14, 0)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('unpaid');
            $table->timestamps();
            $table->unique(['invoice_id', 'sequence']);
        });

        static::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->index();
            $table->foreignId('installment_id')->nullable()->index();
            $table->decimal('amount', 14, 0);
            $table->string('method')->default('cash');
            $table->dateTime('paid_at');
            $table->foreignId('received_by')->nullable()->index();
            $table->text('note')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();
        });

        static::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->index();
            $table->decimal('amount', 14, 0);
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->index();
            $table->string('status')->default('pending');
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });

        static::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->index();
            $table->foreignId('teacher_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('billing_month', 7)->nullable();
            $table->string('category')->default('other');
            $table->decimal('amount', 14, 0);
            $table->date('expense_date');
            $table->foreignId('created_by')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->index();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        static::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->default('global');
            $table->foreignId('class_id')->nullable()->index();
            $table->decimal('percent', 5, 2)->default(0);
            $table->decimal('tier_min_revenue', 14, 0)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        static::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->index();
            $table->foreignId('invoice_id')->index();
            $table->decimal('percent', 5, 2)->default(0);
            $table->decimal('amount', 14, 0)->default(0);
            $table->string('status')->default('unpaid');
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['sales_id', 'invoice_id']);
        });

        static::create('debt_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->index();
            $table->string('channel');
            $table->dateTime('sent_at');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    protected static function createNotificationTables(): void
    {
        static::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        static::create('notification_templates', function (Blueprint $table) {
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

        static::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('template_code', 64)->nullable()->index();
            $table->string('channel', 20);
            $table->string('recipient_type', 20)->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_contact')->nullable();
            $table->foreignId('payment_id')->nullable()->index();
            $table->foreignId('student_id')->nullable()->index();
            $table->foreignId('class_session_id')->nullable()->index();
            $table->string('status', 20)->default('pending');
            $table->text('request_payload')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    protected static function createPayrollTables(): void
    {
        static::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->date('work_date');
            $table->string('status', 20)->default('present');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();
            $table->unique(['user_id', 'work_date']);
            $table->index(['work_date', 'status']);
        });

        static::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->index();
            $table->string('scope', 16);
            $table->foreignId('teacher_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('billing_month', 7);
            $table->string('type', 16);
            $table->decimal('amount', 15, 0);
            $table->text('note');
            $table->foreignId('expense_id')->nullable()->index();
            $table->uuid('batch_id')->nullable()->index();
            $table->foreignId('created_by')->nullable()->index();
            $table->timestamps();
            $table->index(['scope', 'billing_month']);
            $table->index(['teacher_id', 'billing_month']);
            $table->index(['user_id', 'billing_month']);
        });
    }
}
