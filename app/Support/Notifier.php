<?php

namespace App\Support;

use App\Models\ClassSession;
use App\Models\User;
use App\Notifications\SessionJournalReminderNotification;
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

        $recipients->unique('id')->each(fn (User $user) => $user->notify($notification));
    }
}
