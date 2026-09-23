<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\User;
use App\Notifications\SessionStatusPendingNotification;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemindStaleClassSessionsCommand extends Command
{
    protected $signature = 'crm:remind-stale-sessions {--days=7 : Chỉ xét buổi trong N ngày gần đây}';

    protected $description = 'Nhắc cập nhật trạng thái buổi học đã kết thúc nhưng vẫn Đã lên lịch';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $from = now()->subDays($days)->toDateString();
        $today = now()->toDateString();

        $sessions = ClassSession::query()
            ->with(['courseClass', 'teacher'])
            ->where('status', 'scheduled')
            ->whereDate('session_date', '>=', $from)
            ->whereDate('session_date', '<=', $today)
            ->orderBy('session_date')
            ->get()
            ->filter(fn (ClassSession $session) => $session->hasEnded());

        if ($sessions->isEmpty()) {
            $this->info('Không có buổi nào cần nhắc.');

            return self::SUCCESS;
        }

        $sent = 0;
        $tasks = 0;
        $auto = app(AutoTaskService::class);
        $anyRecipients = false;

        foreach ($sessions as $session) {
            $branchId = $session->courseClass?->branch_id
                ? (int) $session->courseClass->branch_id
                : null;
            $recipients = Notifier::recipientsForPermission('training.classes.manage', [], $branchId);
            if ($recipients->isEmpty()) {
                continue;
            }
            $anyRecipients = true;

            foreach ($recipients as $user) {
                if ($this->alreadyNotifiedToday($user, $session->id)) {
                    continue;
                }

                $user->notify(new SessionStatusPendingNotification($session));
                $sent++;
            }

            try {
                $class = $session->courseClass;
                $date = optional($session->session_date)->format('d/m/Y');
                $created = $auto->ensureForUsers(
                    $recipients,
                    AutoTaskService::SOURCE_SESSION_STATUS,
                    (int) $session->id,
                    [
                        'title' => 'Cập nhật trạng thái buổi: '.($class?->name ?? 'Lớp').' · '.$date,
                        'description' => 'Buổi đã kết thúc nhưng vẫn Đã lên lịch — hãy đánh dấu Hoàn thành hoặc Hủy.'."\n"
                            .'Mở: '.route('admin.classes.show', [
                                'class' => $class?->id ?? $session->class_id,
                                'tab' => 'timetable',
                            ], absolute: false),
                        'priority' => 'high',
                        'due_date' => now()->endOfDay(),
                        'branch_id' => $class?->branch_id,
                        'status' => 'todo',
                    ]
                );
                $tasks += count($created);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $anyRecipients) {
            $this->warn('Không có user nào có quyền training.classes.manage.');
        }

        $this->info("Đã gửi {$sent} nhắc trạng thái buổi học ({$sessions->count()} buổi), {$tasks} việc trên board.");

        return self::SUCCESS;
    }

    protected function alreadyNotifiedToday(User $user, int $sessionId): bool
    {
        return DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->where('type', SessionStatusPendingNotification::class)
            ->whereDate('created_at', now()->toDateString())
            ->where('data', 'like', '%"session_id":'.$sessionId.'%')
            ->exists();
    }
}
