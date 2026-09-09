<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NotificationTemplate::query()->updateOrCreate(
            ['code' => 'attendance_alert'],
            [
                'title' => 'Thông báo điểm danh (vắng / muộn)',
                'email_subject' => '[{{center_name}}] Điểm danh {{class_name}} — {{student_name}} {{attendance_status}}',
                'content_email' => "Xin chào {{recipient_name}},\n\n"
                    ."Trung tâm {{center_name}} thông báo điểm danh:\n"
                    ."- Học viên: {{student_name}}\n"
                    ."- Lớp: {{class_name}}\n"
                    ."- Ngày: {{session_date}}\n"
                    ."- Trạng thái: {{attendance_status}}\n"
                    ."- Chi nhánh: {{branch_name}}\n\n"
                    ."Vui lòng liên hệ trung tâm nếu có thắc mắc.",
                'zalo_template_id' => null,
                'params_mapping' => [
                    'customer_name' => '{{recipient_name}}',
                    'student_name' => '{{student_name}}',
                    'course_name' => '{{class_name}}',
                    'date' => '{{session_date}}',
                    'status' => '{{attendance_status}}',
                ],
                'is_active_email' => false,
                'is_active_zalo' => false,
                'notes' => 'Gửi PH khi lưu điểm danh Vắng/Muộn. Bật Cài đặt → Zalo điểm danh + Template ID.',
            ]
        );
    }

    public function down(): void
    {
        NotificationTemplate::query()->where('code', 'attendance_alert')->delete();
    }
};
