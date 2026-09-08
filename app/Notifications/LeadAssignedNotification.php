<?php

namespace App\Notifications;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public Lead $lead) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $lead = $this->lead->loadMissing('branch');

        return [
            'title' => 'Bạn được phân lead mới',
            'body' => $lead->name.' · '.($lead->phone ?: '—').($lead->branch ? ' · '.$lead->branch->name : ''),
            'url' => route('admin.leads.show', $lead),
            'icon' => 'bi-person-plus',
            'lead_id' => $lead->id,
            'type' => 'lead_assigned',
        ];
    }
}
