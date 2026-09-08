<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('template_code', 64)->nullable()->index();
            $table->string('channel', 20); // email|zalo
            $table->string('recipient_type', 20)->nullable(); // student|parent
            $table->string('recipient_name')->nullable();
            $table->string('recipient_contact')->nullable(); // email or phone
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending|sent|failed|skipped
            $table->text('request_payload')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        DB::table('notification_templates')->insert([
            'code' => 'payment_success',
            'title' => 'Thanh toán học phí thành công',
            'email_subject' => '[{{center_name}}] Xác nhận thanh toán {{invoice_code}}',
            'content_email' => "Xin chào {{recipient_name}},\n\n"
                ."Trung tâm {{center_name}} xác nhận đã nhận thanh toán học phí:\n"
                ."- Học viên: {{student_name}}\n"
                ."- Lớp: {{class_name}}\n"
                ."- Mã hóa đơn: {{invoice_code}}\n"
                ."- Số tiền: {{amount_formatted}}\n"
                ."- Ngày thanh toán: {{paid_at}}\n"
                ."- Phương thức: {{payment_method}}\n\n"
                ."Cảm ơn quý phụ huynh / học viên.",
            'zalo_template_id' => null,
            'params_mapping' => json_encode([
                'customer_name' => 'recipient_name',
                'student_name' => 'student_name',
                'amount' => 'amount_formatted',
                'course_name' => 'class_name',
                'payment_code' => 'invoice_code',
                'date' => 'paid_at',
            ], JSON_UNESCAPED_UNICODE),
            'is_active_email' => true,
            'is_active_zalo' => false,
            'notes' => 'Gửi cho học viên và phụ huynh khi ghi nhận thanh toán thành công.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_templates');
    }
};
