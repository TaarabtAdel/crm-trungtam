<?php

namespace App\Notifications;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SessionJournalReminderNotification extends Notification
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
        $time = trim(
            ($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
            .'–'
            .($session->end_time ? substr((string) $session->end_time, 0, 5) : ''),
            '–'
        );

        $body = ($class?->name ?? 'Lớp').' · '.$date.($time !== '' ? ' '.$time : '');
        if ($session->teacher?->name) {
            $body .= ' · GV '.$session->teacher->name;
        }
        $body .= '. Buổi đã hoàn thành — vui lòng ghi nhật ký (tên bài, nội dung, nhận xét).';

        return [
            'title' => 'Nhắc ghi nhật ký buổi học',
            'body' => $body,
            'url' => route('admin.classes.show', [
                'class' => $class?->id ?? $session->class_id,
                'tab' => 'journal',
                'month' => optional($session->session_date)->format('Y-m'),
                'open_journal' => $session->id,
            ]),
            'icon' => 'bi-journal-text',
            'session_id' => $session->id,
            'class_id' => $session->class_id,
            'type' => 'session_journal_reminder',
        ];
    }
}
