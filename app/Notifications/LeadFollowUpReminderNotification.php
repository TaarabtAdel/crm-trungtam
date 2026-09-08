<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadFollowUpReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $lead = $this->lead;
        $due = optional($lead->follow_up_at)->format('d/m/Y');
        $overdue = $lead->follow_up_at && $lead->follow_up_at->isPast() && ! $lead->follow_up_at->isToday();

        return [
            'title' => $overdue ? 'Lead quá hạn xử lý' : 'Lead sắp tới hạn xử lý',
            'body' => $lead->name.' (trạng thái Mới) — hạn '.$due.'. Hãy cập nhật trạng thái khi đã xử lý.',
            'url' => route('admin.leads.show', $lead),
            'icon' => $overdue ? 'bi-exclamation-triangle' : 'bi-alarm',
            'lead_id' => $lead->id,
            'type' => 'lead_follow_up',
            'follow_up_at' => optional($lead->follow_up_at)->toDateString(),
        ];
    }
}
