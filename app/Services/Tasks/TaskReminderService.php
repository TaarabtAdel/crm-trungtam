<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TaskReminderLog;
use App\Notifications\TaskEventNotification;
use Carbon\Carbon;

class TaskReminderService
{
    public function run(): array
    {
        $sent = [
            'due_soon_1d' => 0,
            'due_soon_3h' => 0,
            'due_today' => 0,
            'overdue' => 0,
        ];

        $now = now();

        // Sắp đến hạn 1 ngày (còn ~24h, cửa sổ 1 giờ)
        $sent['due_soon_1d'] = $this->scanWindow(
            'due_soon_1d',
            $now->copy()->addDay()->subMinutes(30),
            $now->copy()->addDay()->addMinutes(30),
            'Công việc sắp đến hạn (1 ngày)',
            fn (Task $t) => $t->title.' · hạn '.$t->due_date?->format('d/m/Y H:i')
        );

        // Sắp đến hạn 3 giờ
        $sent['due_soon_3h'] = $this->scanWindow(
            'due_soon_3h',
            $now->copy()->addHours(3)->subMinutes(20),
            $now->copy()->addHours(3)->addMinutes(20),
            'Công việc sắp đến hạn (3 giờ)',
            fn (Task $t) => $t->title.' · hạn '.$t->due_date?->format('d/m/Y H:i')
        );

        // Đến hạn hôm nay — chỉ gửi khung 07:30–09:00
        if ($now->hour >= 7 && $now->hour < 9) {
            $sent['due_today'] = $this->scanDueToday($now);
        }

        // Quá hạn — mỗi ngày 1 lần (loại reminder_type theo ngày)
        $sent['overdue'] = $this->scanOverdue($now);

        return $sent;
    }

    protected function scanWindow(string $type, Carbon $from, Carbon $to, string $title, callable $bodyFn): int
    {
        $count = 0;
        Task::query()
            ->with('assignee')
            ->where('is_published', true)
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$from, $to])
            ->whereDoesntHave('reminderLogs', fn ($q) => $q->where('reminder_type', $type))
            ->get()
            ->each(function (Task $task) use ($type, $title, $bodyFn, &$count) {
                if (! $task->assignee) {
                    return;
                }
                $task->assignee->notify(new TaskEventNotification($task, $type, $title, $bodyFn($task)));
                TaskReminderLog::query()->create([
                    'task_id' => $task->id,
                    'reminder_type' => $type,
                    'sent_at' => now(),
                ]);
                $count++;
            });

        return $count;
    }

    protected function scanDueToday(Carbon $now): int
    {
        $type = 'due_today_'.$now->toDateString();
        $count = 0;

        Task::query()
            ->with('assignee')
            ->where('is_published', true)
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', $now->toDateString())
            ->whereDoesntHave('reminderLogs', fn ($q) => $q->where('reminder_type', $type))
            ->get()
            ->each(function (Task $task) use ($type, &$count) {
                if (! $task->assignee) {
                    return;
                }
                $task->assignee->notify(new TaskEventNotification(
                    $task,
                    'due_today',
                    'Công việc đến hạn hôm nay',
                    $task->title.' · hạn '.$task->due_date?->format('H:i')
                ));
                TaskReminderLog::query()->create([
                    'task_id' => $task->id,
                    'reminder_type' => $type,
                    'sent_at' => now(),
                ]);
                $count++;
            });

        return $count;
    }

    protected function scanOverdue(Carbon $now): int
    {
        $type = 'overdue_'.$now->toDateString();
        $count = 0;

        Task::query()
            ->with('assignee')
            ->where('is_published', true)
            ->whereNotIn('status', ['done'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now->copy()->startOfDay())
            ->whereDoesntHave('reminderLogs', fn ($q) => $q->where('reminder_type', $type))
            ->get()
            ->each(function (Task $task) use ($type, &$count) {
                if (! $task->assignee) {
                    return;
                }
                $task->assignee->notify(new TaskEventNotification(
                    $task,
                    'overdue',
                    'Công việc đã quá hạn',
                    $task->title.' · hạn '.$task->due_date?->format('d/m/Y H:i')
                ));
                TaskReminderLog::query()->create([
                    'task_id' => $task->id,
                    'reminder_type' => $type,
                    'sent_at' => now(),
                ]);
                $count++;
            });

        return $count;
    }
}
