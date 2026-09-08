<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'template_code',
        'channel',
        'recipient_type',
        'recipient_name',
        'recipient_contact',
        'payment_id',
        'student_id',
        'class_session_id',
        'status',
        'request_payload',
        'response_body',
        'error_message',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent' => 'Đã gửi',
            'failed' => 'Lỗi',
            'skipped' => 'Bỏ qua',
            default => 'Chờ',
        };
    }

    public function channelLabel(): string
    {
        return match ($this->channel) {
            'zalo' => 'Zalo ZNS',
            'email' => 'Email',
            default => $this->channel,
        };
    }
}
