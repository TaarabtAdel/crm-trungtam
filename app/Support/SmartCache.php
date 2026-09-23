<?php

namespace App\Support;

use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\TaskService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cache UI nóng (thông báo header, widget Việc hôm nay…) theo tenant.
 * Ghi dữ liệu liên quan → forget/bump → lần đọc sau tạo lại.
 */
class SmartCache
{
    public const TTL_NOTIFICATIONS = 600;

    public const TTL_BRANCHES = 300;

    public const TTL_SCHEMA = 300;

    /**
     * Key đã gắn prefix tenant.
     */
    public static function key(string $key): string
    {
        return TenantContext::cacheKey($key);
    }

    public static function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        return Cache::remember(static::key($key), $ttl, $callback);
    }

    public static function forget(string $key): void
    {
        Cache::forget(static::key($key));
    }

    // ── Thông báo header / home ───────────────────────────────────────────

    public static function notificationsKey(int $userId): string
    {
        return "ui:notifications:{$userId}";
    }

    /**
     * @return array{items: Collection, unread: int}
     */
    public static function headerNotifications(User $user): array
    {
        return static::remember(static::notificationsKey((int) $user->id), static::TTL_NOTIFICATIONS, function () use ($user) {
            return [
                'items' => $user->notifications()->latest()->limit(12)->get(),
                'unread' => (int) $user->unreadNotifications()->count(),
            ];
        });
    }

    public static function forgetNotifications(int|User $user): void
    {
        $id = $user instanceof User ? (int) $user->id : (int) $user;
        if ($id <= 0) {
            return;
        }

        static::forget(static::notificationsKey($id));
    }

    /**
     * @param  iterable<int, int|User>  $users
     */
    public static function forgetNotificationsFor(iterable $users): void
    {
        foreach ($users as $user) {
            static::forgetNotifications($user);
        }
    }

    // ── Widget Việc hôm nay + ngày có hạn (calendar) ──────────────────────

    public static function tasksHomeRevisionKey(): string
    {
        return 'ui:tasks_home:rev';
    }

    public static function tasksHomeRevision(): int
    {
        return (int) Cache::get(static::key(static::tasksHomeRevisionKey()), 0);
    }

    /**
     * Bump revision → mọi key widget Việc hôm nay cũ coi như hết hiệu lực.
     */
    public static function bumpTasksHome(): void
    {
        $key = static::key(static::tasksHomeRevisionKey());
        if (! Cache::has($key)) {
            Cache::forever($key, 1);

            return;
        }

        Cache::increment($key);
    }

    public static function tasksHomeKey(int $userId, string $day, int $revision): string
    {
        return "ui:tasks_home:{$userId}:{$day}:v{$revision}";
    }

    /**
     * @return array{today: Collection<int, Task>, due_dates: list<string>}
     */
    public static function homeTasks(User $user): array
    {
        if (! $user->hasPermission('tasks.view') && ! $user->isSuperAdmin()) {
            return [
                'today' => collect(),
                'due_dates' => [],
            ];
        }

        $day = now()->toDateString();
        $branchKey = CurrentBranch::id() ?? 0;
        $rev = static::tasksHomeRevision();
        $ttl = max(60, (int) now()->diffInSeconds(now()->endOfDay()));

        return static::remember(
            static::tasksHomeKey((int) $user->id, $day, $rev).':b'.$branchKey,
            $ttl,
            function () use ($user) {
                try {
                    $service = app(TaskService::class);
                    // Chỉ việc chưa hoàn thành (todo / doing / review), được giao cho tôi, đã công bố
                    $openStatuses = array_values(array_filter(
                        array_keys(Task::STATUSES),
                        fn (string $s) => $s !== 'done'
                    ));
                    $base = $service->visibleQuery($user)
                        ->where('is_published', true)
                        ->where('assignee_id', $user->id)
                        ->whereNotNull('due_date')
                        ->whereIn('status', $openStatuses);

                    // Hạn trong ngày hôm nay (kể cả 23:59) + quá hạn chưa làm
                    $endOfToday = now()->endOfDay();

                    $today = (clone $base)
                        ->with(['assignee'])
                        ->where('due_date', '<=', $endOfToday)
                        ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
                        ->orderBy('due_date')
                        ->limit(8)
                        ->get();

                    $dueDates = (clone $base)
                        ->whereBetween('due_date', [now()->subDays(40)->startOfDay(), now()->addDays(70)->endOfDay()])
                        ->pluck('due_date')
                        ->map(fn ($d) => $d->format('Y-m-d'))
                        ->unique()
                        ->values()
                        ->all();

                    return [
                        'today' => $today,
                        'due_dates' => $dueDates,
                    ];
                } catch (\Throwable) {
                    return [
                        'today' => collect(),
                        'due_dates' => [],
                    ];
                }
            }
        );
    }

    // ── Chi nhánh header (phụ) ────────────────────────────────────────────

    public static function activeBranchesKey(): string
    {
        return 'ui:active_branches';
    }

    /**
     * @return Collection<int, \App\Models\Branch>
     */
    public static function activeBranches(): Collection
    {
        return static::remember(static::activeBranchesKey(), static::TTL_BRANCHES, function () {
            return \App\Models\Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    public static function forgetActiveBranches(): void
    {
        static::forget(static::activeBranchesKey());
    }

    // ── Kiểm tra phiên bản schema ─────────────────────────────────────────

    public static function schemaUpToDateKey(string $targetVersion): string
    {
        return 'schema:uptodate:'.$targetVersion;
    }

    public static function isSchemaUpToDateCached(string $targetVersion): bool
    {
        return Cache::get(static::key(static::schemaUpToDateKey($targetVersion))) === 1;
    }

    public static function markSchemaUpToDate(string $targetVersion): void
    {
        Cache::put(static::key(static::schemaUpToDateKey($targetVersion)), 1, static::TTL_SCHEMA);
    }

    /**
     * Xoá toàn bộ cache store (dùng với ?remove_cache=1).
     */
    public static function flushAll(): void
    {
        Cache::flush();
    }
}
