<?php

namespace App\Support;

use App\Models\ClassSession;
use App\Models\User;
use App\Notifications\SessionJournalReminderNotification;
use App\Services\Tasks\AutoTaskService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class Notifier
{
    /**
     * @param  array<int, int>|int|null  $exceptUserIds
     */
    public static function toPermission(string $permission, Notification $notification, array|int|null $exceptUserIds = null): void
    {
        $except = collect(is_array($exceptUserIds) ? $exceptUserIds : ($exceptUserIds ? [$exceptUserIds] : []))
            ->map(fn ($id) => (int) $id)
            ->all();

        self::recipientsForPermission($permission, $except)
            ->each(fn (User $user) => $user->notify($notification));
    }

    /**
     * @param  array<int, int>  $exceptUserIds
     * @return Collection<int, User>
     */
    public static function recipientsForPermission(string $permission, array $exceptUserIds = []): Collection
    {
        return User::query()
            ->with('roleAssignments')
            ->where('is_active', true)
            ->when($exceptUserIds !== [], fn ($q) => $q->whereNotIn('id', $exceptUserIds))
            ->get()
            ->filter(fn (User $user) => $user->hasPermission($permission))
            ->values();
    }

    /**
     * Nhắc giáo viên (và đào tạo nếu không map được GV) ghi nhật ký sau buổi hoàn thành.
     * Đồng thời tạo việc đã công bố trên board Công việc.
     *
     * @param  array<int, int>|int|null  $exceptUserIds
     */
    public static function remindSessionJournal(ClassSession $session, array|int|null $exceptUserIds = null): void
    {
        $except = collect(is_array($exceptUserIds) ? $exceptUserIds : ($exceptUserIds ? [$exceptUserIds] : []))
            ->map(fn ($id) => (int) $id)
            ->all();

        $session->loadMissing(['teacher', 'courseClass']);
        $notification = new SessionJournalReminderNotification($session);

        $recipients = collect();

        $teacherEmail = trim((string) ($session->teacher?->email ?? ''));
        if ($teacherEmail !== '') {
            $recipients = $recipients->merge(
                User::query()
                    ->with('roleAssignments')
                    ->where('is_active', true)
                    ->where('email', $teacherEmail)
                    ->when($except !== [], fn ($q) => $q->whereNotIn('id', $except))
                    ->get()
            );
        }

        // Nếu chưa map được user theo email GV: nhắc Đào tạo / Admin
        if ($recipients->isEmpty()) {
            $recipients = self::recipientsForPermission('training.journals.manage', $except)
                ->filter(fn (User $u) => $u->hasAnyRole('training', 'admin', 'super_admin', 'teacher'));
        }

        $recipients = $recipients->unique('id')->values();
        $recipients->each(fn (User $user) => $user->notify($notification));

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            $session->loadMissing(['courseClass', 'teacher']);
            $class = $session->courseClass;
            $date = optional($session->session_date)->format('d/m/Y');
            $time = trim(
                ($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
                .'–'
                .($session->end_time ? substr((string) $session->end_time, 0, 5) : ''),
                '–'
            );
            $title = 'Ghi nhật ký: '.($class?->name ?? 'Lớp').' · '.$date;
            $desc = 'Buổi học đã hoàn thành — vui lòng ghi nhật ký (tên bài, nội dung, nhận xét, BT về nhà).';
            if ($time !== '') {
                $desc .= "\nGiờ: ".$time;
            }
            if ($session->teacher?->name) {
                $desc .= "\nGV: ".$session->teacher->name;
            }
            $desc .= "\nMở: ".route('admin.classes.show', [
                'class' => $class?->id ?? $session->class_id,
                'tab' => 'journal',
                'month' => optional($session->session_date)->format('Y-m'),
                'open_journal' => $session->id,
            ], absolute: false);

            $creatorId = $except[0] ?? null;

            app(AutoTaskService::class)->ensureForUsers(
                $recipients,
                AutoTaskService::SOURCE_SESSION_JOURNAL,
                (int) $session->id,
                [
                    'title' => $title,
                    'description' => $desc,
                    'priority' => 'high',
                    'due_date' => now()->endOfDay(),
                    'branch_id' => $class?->branch_id,
                    'creator_id' => $creatorId,
                    'status' => 'todo',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
