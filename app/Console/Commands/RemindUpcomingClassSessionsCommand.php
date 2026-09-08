<?php

namespace App\Console\Commands;

use App\Jobs\SendSessionReminderJob;
use App\Models\ClassSession;
use App\Models\NotificationTemplate;
use App\Services\SessionReminderNotificationService;
use App\Support\AppSettings;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class RemindUpcomingClassSessionsCommand extends Command
{
    protected $signature = 'crm:remind-upcoming-sessions
                            {--minutes=120 : Nhắc trước buổi bao nhiêu phút}
                            {--window=12 : Cửa sổ ± phút quanh mốc nhắc (tránh miss khi tick thưa)}';

    protected $description = 'Nhắc HV/PH (Email/Zalo) trước buổi học theo lịch (mặc định 2 tiếng)';

    public function handle(): int
    {
        $minutes = max(15, (int) $this->option('minutes'));
        $window = max(5, (int) $this->option('window'));

        $template = NotificationTemplate::findByCode(SessionReminderNotificationService::TEMPLATE_CODE);
        if (! $template || (! $template->is_active_email && ! $template->is_active_zalo)) {
            $this->info('Mẫu session_reminder chưa bật Email/Zalo — bỏ qua.');

            return self::SUCCESS;
        }

        if ($template->is_active_zalo
            && (! AppSettings::bool('zalo_notify_enabled') || ! AppSettings::bool('zalo_notify_schedule'))) {
            // Vẫn chạy nếu email đang bật; nếu chỉ Zalo mà setting tắt thì skip sớm
            if (! $template->is_active_email) {
                $this->info('Zalo nhắc lịch đang tắt trong Cài đặt — bỏ qua.');

                return self::SUCCESS;
            }
        }

        $from = now()->addMinutes($minutes - $window);
        $to = now()->addMinutes($minutes + $window);

        $dates = collect([
            $from->toDateString(),
            $to->toDateString(),
        ])->unique()->values()->all();

        $sessions = ClassSession::query()
            ->with(['courseClass.students', 'courseClass.branch', 'courseClass.teacher', 'teacher'])
            ->where('status', 'scheduled')
            ->whereDate('session_date', '>=', min($dates))
            ->whereDate('session_date', '<=', max($dates))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->get()
            ->filter(function (ClassSession $session) use ($from, $to) {
                $startsAt = $session->startsAt();

                return $startsAt && $startsAt->between($from, $to);
            });

        if ($sessions->isEmpty()) {
            $this->info("Không có buổi nào trong cửa sổ nhắc (~{$minutes} phút trước, ±{$window}).");

            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($sessions as $session) {
            SendSessionReminderJob::dispatch(
                $session->id,
                TenantContext::databaseName(),
                TenantContext::subdomain()
            );
            $dispatched++;
        }

        $this->info("Đã xếp hàng nhắc lịch cho {$dispatched} buổi học.");

        return self::SUCCESS;
    }
}
