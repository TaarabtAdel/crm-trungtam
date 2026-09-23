<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $eventType,
        public string $title,
        public string $body
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => route('admin.tasks.index', ['open' => $this->task->id]),
            'icon' => 'bi-check2-square',
            'task_id' => $this->task->id,
            'type' => 'task_'.$this->eventType,
        ];
    }
}
