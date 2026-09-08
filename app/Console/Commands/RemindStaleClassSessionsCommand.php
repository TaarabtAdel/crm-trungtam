<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\User;
use App\Notifications\SessionStatusPendingNotification;
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

        $recipients = Notifier::recipientsForPermission('training.classes.manage');
        if ($recipients->isEmpty()) {
            $this->warn('Không có user nào có quyền training.classes.manage.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($sessions as $session) {
            foreach ($recipients as $user) {
                if ($this->alreadyNotifiedToday($user, $session->id)) {
                    continue;
                }

                $user->notify(new SessionStatusPendingNotification($session));
                $sent++;
            }
        }

        $this->info("Đã gửi {$sent} nhắc trạng thái buổi học ({$sessions->count()} buổi).");

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
