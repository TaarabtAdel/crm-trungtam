<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Services\Tasks\AutoTaskService;
use App\Support\Notifier;
use Illuminate\Console\Command;

class RemindEmptyJournalsCommand extends Command
{
    protected $signature = 'crm:remind-empty-journals {--days=14 : Chỉ xét buổi hoàn thành trong N ngày}';

    protected $description = 'Bù việc nhật ký trống cho buổi đã hoàn thành nhưng chưa ghi nhật ký';

    public function handle(AutoTaskService $auto): int
    {
        $days = max(1, (int) $this->option('days'));
        $from = now()->subDays($days)->toDateString();

        $sessions = ClassSession::query()
            ->with(['courseClass', 'teacher', 'journal'])
            ->where('status', 'completed')
            ->whereDate('session_date', '>=', $from)
            ->whereDate('session_date', '<=', now()->toDateString())
            ->orderByDesc('session_date')
            ->get()
            ->filter(function (ClassSession $session) {
                $journal = $session->journal;
                if (! $journal) {
                    return true;
                }

                return $journal->filled_at === null
                    && blank($journal->lesson_title)
                    && blank($journal->content)
                    && blank($journal->remarks)
                    && blank($journal->homework);
            });

        if ($sessions->isEmpty()) {
            $this->info('Không có nhật ký trống cần nhắc.');

            return self::SUCCESS;
        }

        $tasks = 0;
        foreach ($sessions as $session) {
            $recipients = collect();
            $teacherEmail = trim((string) ($session->teacher?->email ?? ''));
            if ($teacherEmail !== '') {
                $recipients = \App\Models\User::query()
                    ->where('is_active', true)
                    ->where('email', $teacherEmail)
                    ->get();
            }
            if ($recipients->isEmpty()) {
                $branchId = $session->courseClass?->branch_id
                    ? (int) $session->courseClass->branch_id
                    : null;
                $recipients = Notifier::recipientsForPermission('training.journals.manage', [], $branchId)
                    ->filter(fn ($u) => $u->hasAnyRole('training', 'admin', 'super_admin', 'teacher'))
                    ->values();
            }
            if ($recipients->isEmpty()) {
                continue;
            }

            $class = $session->courseClass;
            $date = optional($session->session_date)->format('d/m/Y');

            try {
                $created = $auto->ensureForUsers(
                    $recipients,
                    AutoTaskService::SOURCE_SESSION_JOURNAL,
                    (int) $session->id,
                    [
                        'title' => 'Ghi nhật ký: '.($class?->name ?? 'Lớp').' · '.$date,
                        'description' => 'Buổi đã hoàn thành — chưa có nhật ký.'."\n"
                            .'Mở: '.route('admin.classes.show', [
                                'class' => $class?->id ?? $session->class_id,
                                'tab' => 'journal',
                                'month' => optional($session->session_date)->format('Y-m'),
                                'open_journal' => $session->id,
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

        $this->info("Đã tạo/cập nhật {$tasks} việc nhật ký trống ({$sessions->count()} buổi).");

        return self::SUCCESS;
    }
}
