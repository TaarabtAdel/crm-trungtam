<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionStatusPendingNotification extends Notification
{
    use Queueable;

    public function __construct(public ClassSession $session) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $session = $this->session->loadMissing(['courseClass', 'teacher']);
        $class = $session->courseClass;
        $date = optional($session->session_date)->format('d/m/Y');
        $time = trim(($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
            .'–'
            .($session->end_time ? substr((string) $session->end_time, 0, 5) : ''), '–');

        $teacher = $session->teacher?->name;
        $body = ($class?->name ?? 'Lớp').' · '.$date.($time !== '' ? ' '.$time : '');
        if ($teacher) {
            $body .= ' · GV '.$teacher;
        }
        $body .= '. Buổi đã kết thúc nhưng vẫn đang Đã lên lịch — hãy đánh dấu Hoàn thành hoặc Hủy.';

        return [
            'title' => 'Buổi học chưa cập nhật trạng thái',
            'body' => $body,
            'url' => route('admin.classes.show', ['class' => $class?->id ?? $session->class_id, 'tab' => 'timetable']),
            'icon' => 'bi-calendar-x',
            'session_id' => $session->id,
            'class_id' => $session->class_id,
            'type' => 'session_status_pending',
        ];
    }
}
