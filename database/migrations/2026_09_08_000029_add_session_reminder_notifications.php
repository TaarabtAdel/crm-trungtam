<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->foreignId('class_session_id')
                ->nullable()
                ->after('student_id')
                ->constrained('class_sessions')
                ->nullOnDelete();
        });

        NotificationTemplate::query()->updateOrCreate(
            ['code' => 'session_reminder'],
            [
                'title' => 'Nhắc lịch học (trước buổi 2 tiếng)',
                'email_subject' => '[{{center_name}}] Nhắc lịch học {{class_name}} — {{session_date}} {{start_time}}',
                'content_email' => "Xin chào {{recipient_name}},\n\n"
                    ."Trung tâm {{center_name}} xin nhắc lịch học sắp tới:\n"
                    ."- Học viên: {{student_name}}\n"
                    ."- Lớp: {{class_name}}\n"
                    ."- Ngày: {{session_date}}\n"
                    ."- Giờ: {{start_time}} – {{end_time}}\n"
                    ."- Phòng: {{room}}\n"
                    ."- Giáo viên: {{teacher_name}}\n"
                    ."- Chi nhánh: {{branch_name}}\n\n"
                    ."Vui lòng đến đúng giờ. Cảm ơn quý phụ huynh / học viên.",
                'zalo_template_id' => null,
                'params_mapping' => [
                    'customer_name' => '{{recipient_name}}',
                    'student_name' => '{{student_name}}',
                    'course_name' => '{{class_name}}',
                    'date' => '{{session_date}}',
                    'time' => '{{start_time}}',
                    'teacher_name' => '{{teacher_name}}',
                    'room' => '{{room}}',
                ],
                'is_active_email' => false,
                'is_active_zalo' => false,
                'notes' => 'Gửi HV + PH trước buổi học ~2 tiếng. Bật Zalo tại Cài đặt (Nhắc lịch học) + điền Zalo Template ID.',
            ]
        );
    }

    public function down(): void
    {
        NotificationTemplate::query()->where('code', 'session_reminder')->delete();

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_session_id');
        });
    }
};
