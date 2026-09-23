<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\User;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateTodaySessionTasksCommand extends Command
{
    protected $signature = 'tasks:create-today-sessions
                            {--date= : Ngày Y-m-d (mặc định hôm nay)}';

    protected $description = 'Tạo việc Công việc cho các buổi học diễn ra trong ngày';

    public function handle(AutoTaskService $auto): int
    {
        $date = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->toDateString()
            : now()->toDateString();

        $sessions = ClassSession::query()
            ->with(['courseClass', 'teacher'])
            ->where('status', 'scheduled')
            ->whereDate('session_date', $date)
            ->orderBy('start_time')
            ->get();

        if ($sessions->isEmpty()) {
            $this->info("Không có buổi scheduled nào ngày {$date}.");

            return self::SUCCESS;
        }

        $managers = Notifier::recipientsForPermission('training.classes.manage');
        $tasks = 0;

        foreach ($sessions as $session) {
            $recipients = $this->recipientsForSession($session, $managers);
            if ($recipients->isEmpty()) {
                continue;
            }

            $class = $session->courseClass;
            $time = trim(
                ($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
                .'–'
                .($session->end_time ? substr((string) $session->end_time, 0, 5) : ''),
                '–'
            );
            $label = optional($session->session_date)->format('d/m/Y') ?: $date;
            $startsAt = $session->startsAt() ?: Carbon::parse($date)->setTime(18, 0);

            try {
                $created = $auto->ensureForUsers(
                    $recipients,
                    AutoTaskService::SOURCE_SESSION_TODAY,
                    (int) $session->id,
                    [
                        'title' => 'Buổi học hôm nay: '.($class?->name ?? 'Lớp')
                            .($time !== '' ? ' · '.$time : ''),
                        'description' => 'Ngày: '.$label
                            .($time !== '' ? "\nGiờ: ".$time : '')
                            .($session->teacher?->name ? "\nGV: ".$session->teacher->name : '')
                            ."\nMở: ".route('admin.classes.show', [
                                'class' => $class?->id ?? $session->class_id,
                                'tab' => 'timetable',
                                'month' => optional($session->session_date)->format('Y-m'),
                            ], absolute: false),
                        'priority' => 'high',
                        'due_date' => $startsAt,
                        'branch_id' => $class?->branch_id,
                        'status' => 'todo',
                    ]
                );
                $tasks += count($created);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->info("Ngày {$date}: {$sessions->count()} buổi → {$tasks} việc trên board.");

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $managers
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function recipientsForSession(ClassSession $session, $managers)
    {
        $recipients = collect();

        $teacherEmail = trim((string) ($session->teacher?->email ?? ''));
        if ($teacherEmail !== '') {
            $recipients = $recipients->merge(
                User::query()
                    ->where('is_active', true)
                    ->where('email', $teacherEmail)
                    ->get()
            );
        }

        if ($recipients->isEmpty()) {
            $recipients = $managers;
        } else {
            // GV nhận việc chính; Đào tạo theo dõi
            $watcherManagers = $managers->reject(
                fn (User $u) => $recipients->contains('id', $u->id)
            );
            // ensureForUsers không nhận watcher khác nhau theo user — gộp managers vào assignee luôn
            // để Đào tạo cũng thấy trên board (giống stale sessions).
            $recipients = $recipients->merge($watcherManagers)->unique('id')->values();
        }

        return $recipients->unique('id')->values();
    }
}
