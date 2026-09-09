<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
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
                'params_mapping' => [
                    'customer_name' => '{{recipient_name}}',
                    'student_name' => '{{student_name}}',
                    'amount' => '{{amount_formatted}}',
                    'course_name' => '{{class_name}}',
                    'payment_code' => '{{invoice_code}}',
                    'date' => '{{paid_at}}',
                ],
                'is_active_email' => true,
                'is_active_zalo' => false,
                'notes' => 'Gửi cho học viên và phụ huynh khi ghi nhận thanh toán thành công.',
            ],
            [
                'code' => 'debt_reminder',
                'title' => 'Nhắc học phí / công nợ',
                'email_subject' => '[{{center_name}}] Nhắc thanh toán học phí {{invoice_code}}',
                'content_email' => "Xin chào {{recipient_name}},\n\n"
                    ."Trung tâm {{center_name}} xin nhắc khoản học phí còn lại:\n"
                    ."- Học viên: {{student_name}}\n"
                    ."- Lớp: {{class_name}}\n"
                    ."- Mã hóa đơn: {{invoice_code}}\n"
                    ."- Còn nợ: {{remaining_amount}}\n\n"
                    ."Vui lòng thanh toán sớm. Cảm ơn quý phụ huynh / học viên.",
                'zalo_template_id' => null,
                'params_mapping' => [
                    'customer_name' => '{{recipient_name}}',
                    'student_name' => '{{student_name}}',
                    'course_name' => '{{class_name}}',
                    'payment_code' => '{{invoice_code}}',
                    'amount' => '{{remaining_amount}}',
                ],
                'is_active_email' => false,
                'is_active_zalo' => false,
                'notes' => 'Nhắc công nợ PH qua Email (Laravel mail) + Zalo nếu bật Cài đặt và template Zalo. Không còn SMS stub.',
            ],
            [
                'code' => 'session_reminder',
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
            ],
            [
                'code' => 'attendance_alert',
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
            ],
        ];

        foreach ($templates as $data) {
            NotificationTemplate::query()->updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}
