<?php

namespace App\Services\Tasks;

use App\Models\Task;
use App\Models\TaskActivityLog;
use App\Models\TaskAttachment;
use App\Models\TaskChecklistItem;
use App\Models\TaskComment;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Notifications\TaskEventNotification;
use App\Support\CurrentBranch;
use App\Support\SmartCache;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskService
{
    public function visibleQuery(User $user)
    {
        $q = Task::query();
        CurrentBranch::apply($q);

        if ($user->hasPermission('tasks.view_all') || $user->isSuperAdmin()) {
            return $q;
        }

        // Role thường: chỉ việc của mình (được giao / tự tạo / đang theo dõi)
        return $q->where(function ($inner) use ($user) {
            $inner->where('assignee_id', $user->id)
                ->orWhere('creator_id', $user->id)
                ->orWhere(function ($published) use ($user) {
                    $published->where('is_published', true)
                        ->whereHas('watchers', fn ($w) => $w->where('users.id', $user->id));
                });
        });
    }

    /**
     * Admin / super admin → toàn quyền trên mọi công việc.
     */
    public function isTaskAdmin(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasAnyRole('admin');
    }

    /**
     * Chỉ Admin được lọc theo người thực hiện.
     */
    public function canFilterAssignees(User $user): bool
    {
        return $this->isTaskAdmin($user);
    }

    public function canView(User $user, Task $task): bool
    {
        if (! CurrentBranch::allows($task->branch_id !== null ? (int) $task->branch_id : null)) {
            return false;
        }

        if ($this->isTaskAdmin($user) || $user->hasPermission('tasks.view_all')) {
            return true;
        }

        if ((int) $task->creator_id === (int) $user->id) {
            return true;
        }

        if (! $task->is_published) {
            return false;
        }

        return (int) $task->assignee_id === (int) $user->id
            || $task->watchers()->where('users.id', $user->id)->exists();
    }

    /**
     * Sửa thông tin gốc (tên, hạn, ưu tiên, giao việc, watcher, công bố, xóa…).
     * → Admin hoặc người tạo.
     */
    public function canEdit(User $user, Task $task): bool
    {
        if (! CurrentBranch::allows($task->branch_id !== null ? (int) $task->branch_id : null)) {
            return false;
        }

        if ($this->isTaskAdmin($user)) {
            return true;
        }

        if (! $user->hasPermission('tasks.manage')) {
            return false;
        }

        return (int) $task->creator_id === (int) $user->id;
    }

    /**
     * Làm việc trên task (đổi trạng thái, checklist, subtask, đính kèm).
     * → Admin, người tạo, hoặc người được giao.
     */
    public function canWork(User $user, Task $task): bool
    {
        if (! CurrentBranch::allows($task->branch_id !== null ? (int) $task->branch_id : null)) {
            return false;
        }

        if ($this->isTaskAdmin($user)) {
            return true;
        }

        if (! $user->hasPermission('tasks.manage')) {
            return false;
        }

        return (int) $task->creator_id === (int) $user->id
            || (int) $task->assignee_id === (int) $user->id;
    }

    /**
     * @deprecated Dùng canWork / canEdit tùy ngữ cảnh.
     */
    public function canManage(User $user, Task $task): bool
    {
        return $this->canWork($user, $task);
    }

    public function canDelete(User $user, Task $task): bool
    {
        if (! CurrentBranch::allows($task->branch_id !== null ? (int) $task->branch_id : null)) {
            return false;
        }

        if ($this->isTaskAdmin($user)) {
            return true;
        }

        if (! $user->hasPermission('tasks.manage')) {
            return false;
        }

        // Người tạo: xóa được bản nháp; việc đã công bố chỉ Admin xóa
        if ((int) $task->creator_id === (int) $user->id) {
            return ! $task->is_published;
        }

        return false;
    }

    public function canPublish(User $user, Task $task): bool
    {
        return $this->canEdit($user, $task) && ! $task->is_published;
    }

    public function canComment(User $user, Task $task): bool
    {
        if (! $this->canView($user, $task)) {
            return false;
        }

        return $this->isTaskAdmin($user) || $user->hasPermission('tasks.manage');
    }

    public function canAssign(User $user): bool
    {
        return $this->isTaskAdmin($user) || $user->hasPermission('tasks.assign');
    }

    /**
     * Quyền trả về cho UI modal.
     *
     * @return array<string, bool>
     */
    public function abilities(User $user, Task $task): array
    {
        return [
            'can_edit' => $this->canEdit($user, $task),
            'can_work' => $this->canWork($user, $task),
            'can_manage' => $this->canWork($user, $task),
            'can_delete' => $this->canDelete($user, $task),
            'can_publish' => $this->canPublish($user, $task),
            'can_assign' => $this->canAssign($user) && $this->canEdit($user, $task),
            'can_comment' => $this->canComment($user, $task),
            'can_duplicate' => $user->hasPermission('tasks.manage') || $this->isTaskAdmin($user),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $watcherIds
     * @param  list<string>  $checklistTitles
     */
    public function create(User $actor, array $data, array $watcherIds = [], array $checklistTitles = []): Task
    {
        return DB::transaction(function () use ($actor, $data, $watcherIds, $checklistTitles) {
            $assigneeId = $this->canAssign($actor)
                ? ($data['assignee_id'] ?? $actor->id)
                : $actor->id;

            $status = $data['status'] ?? 'todo';
            $maxPos = (int) Task::query()->where('status', $status)->max('position');

            $branchData = CurrentBranch::constrainPayload([
                'branch_id' => $data['branch_id'] ?? $actor->branch_id ?? CurrentBranch::id(),
            ]);

            $task = Task::query()->create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'creator_id' => $actor->id,
                'assignee_id' => $assigneeId,
                'branch_id' => $branchData['branch_id'] ?? null,
                'status' => $status,
                'priority' => $data['priority'] ?? 'medium',
                'start_date' => $data['start_date'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'position' => $maxPos + 1,
                'completed_at' => $status === 'done' ? now() : null,
                'is_published' => false,
                'published_at' => null,
            ]);

            $this->syncWatchers($task, $watcherIds, $actor, notify: false);
            $this->replaceChecklist($task, $checklistTitles);
            $this->log($task, $actor, 'created', ['title' => $task->title, 'draft' => true]);

            return $task->load(['assignee', 'creator', 'watchers', 'checklistItems']);
        });
    }

    public function publish(User $actor, Task $task): Task
    {
        abort_unless($this->canEdit($actor, $task), 403);

        if ($task->is_published) {
            return $task->fresh([
                'assignee', 'creator', 'watchers', 'checklistItems', 'subtasks.assignee',
                'comments.user', 'attachments.user', 'activityLogs.user',
            ]);
        }

        $task->is_published = true;
        $task->published_at = now();
        $task->save();

        $this->log($task, $actor, 'published', ['title' => $task->title]);

        $task->load(['assignee', 'creator', 'watchers']);
        $this->notifyTeam(
            $task,
            $actor,
            'published',
            'Công việc mới được công bố',
            $task->title
        );

        return $task->fresh([
            'assignee', 'creator', 'watchers', 'checklistItems', 'subtasks.assignee',
            'comments.user', 'attachments.user', 'activityLogs.user',
        ]);
    }

    public function duplicate(User $actor, Task $source): Task
    {
        abort_unless($this->canView($actor, $source), 403);
        abort_unless($actor->hasPermission('tasks.manage') || $actor->isSuperAdmin(), 403);

        $source->loadMissing(['checklistItems', 'subtasks', 'watchers']);

        return DB::transaction(function () use ($actor, $source) {
            $status = 'todo';
            $maxPos = (int) Task::query()->where('status', $status)->max('position');

            $copy = Task::query()->create([
                'title' => Str::limit($source->title.' (bản sao)', 255, ''),
                'description' => $source->description,
                'creator_id' => $actor->id,
                'assignee_id' => $this->canAssign($actor) ? $source->assignee_id : $actor->id,
                'branch_id' => CurrentBranch::constrainPayload([
                    'branch_id' => $source->branch_id ?? $actor->branch_id ?? CurrentBranch::id(),
                ])['branch_id'] ?? null,
                'status' => $status,
                'priority' => $source->priority,
                'start_date' => $source->start_date,
                'due_date' => $source->due_date,
                'position' => $maxPos + 1,
                'completed_at' => null,
                'is_published' => false,
                'published_at' => null,
            ]);

            $watcherIds = $source->watchers->pluck('id')->all();
            $this->syncWatchers($copy, $watcherIds, $actor, notify: false);

            foreach ($source->checklistItems as $item) {
                $copy->checklistItems()->create([
                    'title' => $item->title,
                    'is_done' => false,
                    'position' => $item->position,
                ]);
            }

            foreach ($source->subtasks as $sub) {
                $copy->subtasks()->create([
                    'title' => $sub->title,
                    'is_done' => false,
                    'assignee_id' => $sub->assignee_id ?: $copy->assignee_id,
                    'position' => $sub->position,
                ]);
            }

            $this->log($copy, $actor, 'created', [
                'title' => $copy->title,
                'duplicated_from' => $source->id,
                'draft' => true,
            ]);

            return $copy->load(['assignee', 'creator', 'watchers', 'checklistItems', 'subtasks']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>|null  $watcherIds
     * @param  list<string>|null  $checklistTitles
     */
    public function update(User $actor, Task $task, array $data, ?array $watcherIds = null, ?array $checklistTitles = null): Task
    {
        return DB::transaction(function () use ($actor, $task, $data, $watcherIds, $checklistTitles) {
            $before = $task->only(['title', 'description', 'assignee_id', 'status', 'priority', 'start_date', 'due_date']);
            $oldWatcherIds = $task->watchers()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

            if (array_key_exists('assignee_id', $data) && ! $this->canAssign($actor)) {
                unset($data['assignee_id']);
            }

            if (($data['status'] ?? null) === 'done' && $task->status !== 'done') {
                $data['completed_at'] = now();
            } elseif (isset($data['status']) && $data['status'] !== 'done') {
                $data['completed_at'] = null;
            }

            if (array_key_exists('branch_id', $data) || CurrentBranch::forcedId()) {
                $data = CurrentBranch::constrainPayload($data);
            }

            $task->fill(collect($data)->only([
                'title', 'description', 'assignee_id', 'branch_id', 'status', 'priority', 'start_date', 'due_date',
            ])->all());
            $task->save();

            if (is_array($watcherIds)) {
                $this->syncWatchers($task, $watcherIds, $actor, notify: false);
            }
            if (is_array($checklistTitles)) {
                $this->replaceChecklist($task, $checklistTitles);
            }

            $changes = [];
            foreach ($before as $key => $old) {
                $new = $task->{$key};
                $oldVal = $old instanceof \Carbon\CarbonInterface ? $old->toDateTimeString() : $old;
                $newVal = $new instanceof \Carbon\CarbonInterface ? $new->toDateTimeString() : $new;
                if ((string) $oldVal !== (string) $newVal) {
                    $changes[$key] = ['from' => $oldVal, 'to' => $newVal];
                }
            }

            if ($changes !== []) {
                $this->log($task, $actor, 'updated', $changes);
            }

            $task->load(['assignee', 'creator', 'watchers']);

            if ($task->is_published) {
                if (isset($changes['assignee_id'])) {
                    $this->notifyTeam(
                        $task,
                        $actor,
                        'assigned',
                        'Công việc được gán người thực hiện',
                        $task->title.' → '.($task->assignee?->name ?? '—')
                    );
                }

                if (isset($changes['status'])) {
                    $this->notifyTeam(
                        $task,
                        $actor,
                        'status_changed',
                        'Trạng thái công việc đã đổi',
                        $task->title.' → '.$task->statusLabel()
                    );
                }

                if (is_array($watcherIds)) {
                    $newIds = collect($watcherIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
                    $oldSorted = $oldWatcherIds;
                    $newSorted = $newIds;
                    sort($oldSorted);
                    sort($newSorted);
                    if ($oldSorted !== $newSorted) {
                        $this->notifyTeam(
                            $task,
                            $actor,
                            'watchers_updated',
                            'Công việc cập nhật người liên quan',
                            $task->title
                        );
                    }
                }
            }

            return $task->fresh([
                'assignee', 'creator', 'watchers', 'checklistItems', 'subtasks.assignee',
                'comments.user', 'attachments.user', 'activityLogs.user',
            ]);
        });
    }

    public function moveStatus(User $actor, Task $task, string $status, ?int $position = null): Task
    {
        abort_unless(array_key_exists($status, Task::STATUSES), 422, 'Trạng thái không hợp lệ.');

        $old = $task->status;
        if ($old === $status && $position === null) {
            return $task;
        }

        $task->status = $status;
        if ($status === 'done') {
            $task->completed_at = $task->completed_at ?: now();
        } else {
            $task->completed_at = null;
        }

        if ($position !== null) {
            $task->position = $position;
        } else {
            $task->position = ((int) Task::query()->where('status', $status)->where('id', '!=', $task->id)->max('position')) + 1;
        }
        $task->save();

        if ($old !== $status) {
            $this->log($task, $actor, 'status_changed', ['from' => $old, 'to' => $status]);
            if ($task->is_published) {
                $this->notifyTeam(
                    $task->fresh(['assignee', 'creator', 'watchers']),
                    $actor,
                    'status_changed',
                    'Trạng thái công việc đã đổi',
                    $task->title.' → '.$task->statusLabel()
                );
            }
        }

        return $task;
    }

    public function reorderColumn(string $status, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Task::query()->where('id', $id)->where('status', $status)->update(['position' => $index + 1]);
        }
    }

    public function delete(User $actor, Task $task): void
    {
        $task->loadMissing(['attachments', 'assignee', 'creator', 'watchers']);

        if ($task->is_published) {
            $this->notifyTeam(
                $task,
                $actor,
                'deleted',
                'Công việc đã bị xóa',
                $task->title
            );
        }

        foreach ($task->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
        }
        $this->log($task, $actor, 'deleted', ['title' => $task->title]);
        $task->delete();
    }

    /**
     * @param  list<int>  $watcherIds
     */
    public function syncWatchers(Task $task, array $watcherIds, ?User $actor = null, bool $notify = false): void
    {
        $ids = collect($watcherIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $before = $task->watchers()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $task->watchers()->sync($ids);

        // Pivot không fire TaskObserver — bump widget Việc hôm nay
        $sortedBefore = $before;
        $sortedAfter = $ids;
        sort($sortedBefore);
        sort($sortedAfter);
        if ($sortedBefore !== $sortedAfter) {
            SmartCache::bumpTasksHome();
        }

        if ($actor) {
            $this->log($task, $actor, 'watchers_updated', ['watcher_ids' => $ids]);
        }

        if ($notify && $actor && $task->is_published) {
            $added = array_values(array_diff($ids, $before));
            foreach ($added as $uid) {
                if ((int) $uid === (int) $task->creator_id || (int) $uid === (int) $actor->id) {
                    continue;
                }
                $user = User::query()->find($uid);
                if ($user && $user->is_active) {
                    $user->notify(new TaskEventNotification(
                        $task,
                        'watcher_added',
                        'Bạn được thêm vào công việc',
                        $task->title
                    ));
                }
            }
        }
    }

    /**
     * @param  list<string>  $titles
     */
    public function replaceChecklist(Task $task, array $titles): void
    {
        $keep = [];
        $pos = 0;
        foreach ($titles as $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }
            $pos++;
            $item = $task->checklistItems()->create([
                'title' => $title,
                'is_done' => false,
                'position' => $pos,
            ]);
            $keep[] = $item->id;
        }
        $task->checklistItems()->whereNotIn('id', $keep)->delete();
    }

    public function addChecklistItem(Task $task, string $title): TaskChecklistItem
    {
        $pos = ((int) $task->checklistItems()->max('position')) + 1;

        return $task->checklistItems()->create([
            'title' => trim($title),
            'is_done' => false,
            'position' => $pos,
        ]);
    }

    public function toggleChecklistItem(TaskChecklistItem $item, bool $done): TaskChecklistItem
    {
        $item->update(['is_done' => $done]);

        return $item;
    }

    public function addSubtask(Task $task, string $title, ?int $assigneeId = null): TaskSubtask
    {
        $pos = ((int) $task->subtasks()->max('position')) + 1;

        return $task->subtasks()->create([
            'title' => trim($title),
            'is_done' => false,
            'assignee_id' => $assigneeId ?: $task->assignee_id,
            'position' => $pos,
        ]);
    }

    public function toggleSubtask(TaskSubtask $subtask, bool $done): TaskSubtask
    {
        $subtask->update(['is_done' => $done]);

        return $subtask;
    }

    public function addComment(User $actor, Task $task, string $body): TaskComment
    {
        $mentions = $this->extractMentions($body);
        $comment = $task->comments()->create([
            'user_id' => $actor->id,
            'body' => $body,
            'mentions' => $mentions,
        ]);

        if ($mentions !== []) {
            $task->watchers()->syncWithoutDetaching($mentions);
            $this->log($task, $actor, 'mentioned', ['user_ids' => $mentions]);
        }

        $this->log($task, $actor, 'commented', ['comment_id' => $comment->id]);

        $task->loadMissing(['assignee', 'creator', 'watchers']);

        if ($task->is_published) {
            $this->notifyTeam(
                $task,
                $actor,
                'comment',
                'Bình luận mới trên công việc',
                Str::limit($body, 120)
            );

            foreach ($mentions as $uid) {
                if ((int) $uid === (int) $actor->id || (int) $uid === (int) $task->creator_id) {
                    continue;
                }
                // đã nằm trong notifyTeam nếu là assignee/watcher; mention vẫn gửi nếu chưa
                $already = in_array((int) $uid, $task->notifyRecipientIds(), true);
                if ($already) {
                    continue;
                }
                $user = User::query()->find($uid);
                if ($user && $user->is_active) {
                    $user->notify(new TaskEventNotification(
                        $task,
                        'mention',
                        'Bạn được nhắc đến trong công việc',
                        Str::limit($body, 120)
                    ));
                }
            }
        }

        return $comment->load('user');
    }

    public function addAttachment(User $actor, Task $task, UploadedFile $file): TaskAttachment
    {
        $path = $file->store('tasks/'.$task->id, 'public');
        $attachment = $task->attachments()->create([
            'user_id' => $actor->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
        ]);
        $this->log($task, $actor, 'attachment_added', ['name' => $attachment->original_name]);

        if ($task->is_published) {
            $task->loadMissing(['assignee', 'creator', 'watchers']);
            $this->notifyTeam(
                $task,
                $actor,
                'attachment',
                'Công việc có tệp đính kèm mới',
                $task->title.' · '.$attachment->original_name
            );
        }

        return $attachment;
    }

    public function log(Task $task, ?User $actor, string $action, array $meta = []): void
    {
        TaskActivityLog::query()->create([
            'task_id' => $task->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'meta' => $meta,
        ]);
    }

    /**
     * @return list<int>
     */
    protected function extractMentions(string $body): array
    {
        preg_match_all('/@id:(\d+)/', $body, $idMatches);
        $ids = collect($idMatches[1] ?? [])->map(fn ($id) => (int) $id);

        preg_match_all('/@([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', $body, $emailMatches);
        if (! empty($emailMatches[1])) {
            $ids = $ids->merge(
                User::query()->whereIn('email', $emailMatches[1])->pluck('id')
            );
        }

        return $ids->filter()->unique()->values()->all();
    }

    /**
     * Gửi thông báo cho người thực hiện + người liên quan.
     * Không gửi cho người tạo việc và không gửi cho người đang thao tác.
     */
    protected function notifyTeam(
        Task $task,
        User $actor,
        string $type,
        string $title,
        string $body
    ): void {
        if (! $task->is_published && $type !== 'published') {
            return;
        }

        $task->loadMissing(['assignee', 'creator', 'watchers']);

        $except = [(int) $actor->id, (int) $task->creator_id];
        $ids = collect($task->notifyRecipientIds())
            ->reject(fn ($id) => in_array((int) $id, $except, true))
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return;
        }

        User::query()->whereIn('id', $ids)->where('is_active', true)->get()
            ->each(fn (User $u) => $u->notify(new TaskEventNotification($task, $type, $title, $body)));
    }

    /**
     * @return Collection<int, User>
     */
    public function assignableUsers(?User $actor = null): Collection
    {
        return CurrentBranch::apply(
            User::query()->where('is_active', true)
        )
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'branch_id']);
    }
}
