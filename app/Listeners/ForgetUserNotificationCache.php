<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\SmartCache;
use Illuminate\Notifications\Events\NotificationSent;

class ForgetUserNotificationCache
{
    public function handle(NotificationSent $event): void
    {
        $notifiable = $event->notifiable;
        if ($notifiable instanceof User) {
            SmartCache::forgetNotifications($notifiable);
        }
    }
}
