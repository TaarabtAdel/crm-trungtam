<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\User;
use App\Support\SmartCache;
use Illuminate\Support\Facades\DB;

/**
 * Tạo việc đã công bố từ nghiệp vụ (thu học phí, nhật ký, buổi học…).
 * Không gửi TaskEventNotification — đã có thông báo nghiệp vụ riêng.
 */
class AutoTaskService
{
    public const SOURCE_SESSION_JOURNAL = 'session_journal';

    public const SOURCE_SESSION_STATUS = 'session_status';

    public const SOURCE_SESSION_TODAY = 'session_today';

    public const SOURCE_INVOICE_DEBT = 'invoice_debt';

    public const SOURCE_EXPENSE_APPROVE = 'expense_approve';

    public const SOURCE_EXPENSE_PAY = 'expense_pay';

    public const SOURCE_LEAD_FOLLOWUP = 'lead_followup';

    public const SOURCE_STAFF_ATTENDANCE = 'staff_attendance';

    public const SOURCE_REFUND_APPROVE = 'refund_approve';

    public const SOURCE_INTERACTION = 'interaction';

    public const SOURCE_TEACHER_PAYROLL = 'teacher_payroll';

    public const SOURCE_STAFF_PAYROLL = 'staff_payroll';

    public const SOURCE_MONTHLY_INVOICE = 'monthly_invoice';

    public const SOURCE_BACKUP = 'backup';

    public const SOURCE_COMMISSION = 'commission_month';

    /**
     * @param  array{
     *   title: string,
     *   description?: ?string,
     *   priority?: string,
     *   due_date?: mixed,
     *   branch_id?: ?int,
     *   creator_id?: ?int,
     *   status?: string,
     *   watcher_ids?: list<int>
     * }  $attrs
     */
    public function ensureForUser(
        User $assignee,
        string $sourceType,
        int $sourceId,
        array $attrs
    ): Task {
        $existing = Task::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('assignee_id', $assignee->id)
            ->where('status', '!=', 'done')
            ->first();

        if ($existing) {
            $existing->fill([
                'title' => $attrs['title'],
                'description' => $attrs['description'] ?? $existing->description,
                'priority' => $attrs['priority'] ?? $existing->priority,
                'due_date' => $attrs['due_date'] ?? $existing->due_date,
                'branch_id' => $attrs['branch_id'] ?? $existing->branch_id,
                'is_published' => true,
                'published_at' => $existing->published_at ?: now(),
            ]);
            $existing->save();

            return $existing;
        }

        return DB::transaction(function () use ($assignee, $sourceType, $sourceId, $attrs) {
            $status = $attrs['status'] ?? 'todo';
            $maxPos = (int) Task::query()->where('status', $status)->max('position');
            $creatorId = (int) ($attrs['creator_id'] ?? $assignee->id);

            $task = Task::query()->create([
                'title' => $attrs['title'],
                'description' => $attrs['description'] ?? null,
                'creator_id' => $creatorId,
                'assignee_id' => $assignee->id,
                'branch_id' => $attrs['branch_id'] ?? $assignee->branch_id,
                'status' => $status,
                'priority' => $attrs['priority'] ?? 'medium',
                'due_date' => $attrs['due_date'] ?? null,
                'position' => $maxPos + 1,
                'completed_at' => null,
                'is_published' => true,
                'published_at' => now(),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            $watcherIds = collect($attrs['watcher_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0 && $id !== $assignee->id)
                ->unique()
                ->values()
                ->all();
            if ($watcherIds !== []) {
                $task->watchers()->sync($watcherIds);
                SmartCache::bumpTasksHome();
            }

            TaskActivityLog::query()->create([
                'task_id' => $task->id,
                'user_id' => $creatorId,
                'action' => 'created',
                'meta' => [
                    'auto' => true,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'published' => true,
                ],
            ]);

            return $task;
        });
    }

    /**
     * @param  iterable<int, User>|iterable<int, int>  $assignees
     * @return list<Task>
     */
    public function ensureForUsers(iterable $assignees, string $sourceType, int $sourceId, array $attrs): array
    {
        $tasks = [];
        foreach ($assignees as $assignee) {
            $user = $assignee instanceof User
                ? $assignee
                : User::query()->find((int) $assignee);
            if (! $user || ! $user->is_active) {
                continue;
            }
            $tasks[] = $this->ensureForUser($user, $sourceType, $sourceId, $attrs);
        }

        return $tasks;
    }

    public function completeBySource(string $sourceType, int $sourceId, ?int $actorId = null): int
    {
        $tasks = Task::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', '!=', 'done')
            ->get();

        return $this->markTasksDone($tasks, $actorId, $sourceType, $sourceId);
    }

    /**
     * Đóng việc auto của đúng người được giao (assignee).
     */
    public function completeBySourceForAssignee(
        string $sourceType,
        int $sourceId,
        int $assigneeId,
        ?int $actorId = null
    ): int {
        $tasks = Task::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('assignee_id', $assigneeId)
            ->where('status', '!=', 'done')
            ->get();

        return $this->markTasksDone($tasks, $actorId ?? $assigneeId, $sourceType, $sourceId);
    }

    /**
     * Đóng mọi việc chấm công ngày Ymd của một user (mọi chi nhánh).
     * source_id = Ymd + 4 số branch_id.
     */
    public function completeStaffAttendanceForActorOnDate(string $ymd, int $assigneeId): int
    {
        $min = (int) ($ymd.'0000');
        $max = (int) ($ymd.'9999');

        $tasks = Task::query()
            ->where('source_type', self::SOURCE_STAFF_ATTENDANCE)
            ->where('assignee_id', $assigneeId)
            ->whereBetween('source_id', [$min, $max])
            ->where('status', '!=', 'done')
            ->get();

        return $this->markTasksDone($tasks, $assigneeId, self::SOURCE_STAFF_ATTENDANCE, null);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Task>|iterable<int, Task>  $tasks
     */
    protected function markTasksDone(iterable $tasks, ?int $actorId, string $sourceType, ?int $sourceId): int
    {
        $count = 0;
        foreach ($tasks as $task) {
            $from = $task->status;
            $task->status = 'done';
            $task->completed_at = now();
            $task->save();

            TaskActivityLog::query()->create([
                'task_id' => $task->id,
                'user_id' => $actorId,
                'action' => 'status_changed',
                'meta' => [
                    'from' => $from,
                    'to' => 'done',
                    'auto' => true,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId ?? $task->source_id,
                ],
            ]);
            $count++;
        }

        if ($count > 0) {
            SmartCache::bumpTasksHome();
        }

        return $count;
    }
}
