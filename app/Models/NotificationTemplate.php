<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'code',
        'title',
        'email_subject',
        'content_email',
        'zalo_template_id',
        'params_mapping',
        'is_active_email',
        'is_active_zalo',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'params_mapping' => 'array',
            'is_active_email' => 'boolean',
            'is_active_zalo' => 'boolean',
        ];
    }

    public static function findByCode(string $code): ?self
    {
        return static::query()->where('code', $code)->first();
    }

    /**
     * @return array<string, string>
     */
    public function availablePlaceholders(): array
    {
        return [
            'recipient_name' => 'Tên người nhận (HV hoặc PH)',
            'student_name' => 'Tên học viên',
            'parent_name' => 'Tên phụ huynh',
            'class_name' => 'Tên lớp',
            'course_name' => 'Tên lớp (alias)',
            'invoice_code' => 'Mã hóa đơn',
            'payment_code' => 'Mã hóa đơn (alias)',
            'amount' => 'Số tiền (số)',
            'amount_formatted' => 'Số tiền đã format',
            'paid_at' => 'Ngày giờ thanh toán',
            'date' => 'Ngày thanh toán (d/m/Y)',
            'payment_method' => 'Phương thức thanh toán',
            'remaining_amount' => 'Còn nợ (format)',
            'branch_name' => 'Chi nhánh',
            'center_name' => 'Tên trung tâm',
            'session_date' => 'Ngày học (d/m/Y)',
            'start_time' => 'Giờ bắt đầu',
            'end_time' => 'Giờ kết thúc',
            'room' => 'Phòng học',
            'teacher_name' => 'Tên giáo viên',
        ];
    }
}
