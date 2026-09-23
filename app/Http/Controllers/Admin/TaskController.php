<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskChecklistItem;
use App\Models\TaskSubtask;
use App\Services\Tasks\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('tasks.view') || $user->isSuperAdmin(), 403);

        $view = $request->get('view', 'board') === 'list' ? 'list' : 'board';
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status');
        $priority = $request->get('priority');
        $dueFrom = $request->get('due_from');
        $dueTo = $request->get('due_to');
        $dueFilter = $request->get('due_filter'); // open|done|due_soon|overdue
        $openId = (int) $request->get('open', 0);

        $canFilterAssignees = $tasks->canFilterAssignees($user);
        $assigneeId = $canFilterAssignees ? $request->get('assignee_id') : null;

        $query = $tasks->visibleQuery($user)
            ->with(['assignee', 'creator', 'watchers', 'checklistItems', 'subtasks'])
            ->when($q !== '', fn ($builder) => $builder->where('title', 'like', '%'.$q.'%'))
            ->when($canFilterAssignees && $assigneeId, fn ($builder) => $builder->where('assignee_id', $assigneeId))
            ->when(! $canFilterAssignees, function ($builder) use ($user) {
                $builder->where(function ($own) use ($user) {
                    $own->where('assignee_id', $user->id)
                        ->orWhere('creator_id', $user->id);
                });
            })
            ->when($status, fn ($builder) => $builder->where('status', $status))
            ->when($priority, fn ($builder) => $builder->where('priority', $priority))
            ->when($dueFrom, fn ($builder) => $builder->whereDate('due_date', '>=', $dueFrom))
            ->when($dueTo, fn ($builder) => $builder->whereDate('due_date', '<=', $dueTo))
            ->when($dueFilter === 'open', fn ($builder) => $builder->where('status', '!=', 'done'))
            ->when($dueFilter === 'done', fn ($builder) => $builder->where('status', 'done'))
            ->when($dueFilter === 'overdue', function ($builder) {
                $builder->whereNotNull('due_date')
                    ->where('due_date', '<', now())
                    ->where('status', '!=', 'done');
            })
            ->when($dueFilter === 'due_soon', function ($builder) {
                $builder->whereNotNull('due_date')
                    ->where('due_date', '>=', now())
                    ->where('due_date', '<=', now()->addDays(3)->endOfDay())
                    ->where('status', '!=', 'done');
            });

        $columns = [];
        if ($view === 'board') {
            foreach (array_keys(Task::STATUSES) as $st) {
                $columns[$st] = (clone $query)
                    ->where('status', $st)
                    ->orderBy('position')
                    ->orderByDesc('id')
                    ->get();
            }
            $listTasks = collect();
        } else {
            $listTasks = $query
                ->orderByRaw("CASE status WHEN 'todo' THEN 1 WHEN 'doing' THEN 2 WHEN 'review' THEN 3 WHEN 'done' THEN 4 ELSE 5 END")
                ->orderBy('due_date')
                ->orderByDesc('id')
                ->paginate(30)
                ->withQueryString();
            $columns = [];
        }

        $users = $tasks->assignableUsers($user);
        $canAssign = $tasks->canAssign($user);

        return view('admin.tasks.index', compact(
            'view', 'columns', 'listTasks', 'users', 'canAssign', 'canFilterAssignees',
            'q', 'assigneeId', 'status', 'priority', 'dueFrom', 'dueTo', 'dueFilter', 'openId'
        ));
    }

    public function store(Request $request, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('tasks.manage') || $user->isSuperAdmin(), 403);

        $data = $this->validated($request, $tasks->canAssign($user));
        $watchers = $request->input('watcher_ids', []);
        $checklist = array_filter(array_map('trim', (array) $request->input('checklist', [])));

        $task = $tasks->create($user, $data, is_array($watchers) ? $watchers : [], $checklist);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'task' => $this->cardPayload($task)]);
        }

        return redirect()->route('admin.tasks.index', ['open' => $task->id])
            ->with('success', 'Đã tạo bản nháp. Công bố khi sẵn sàng để gửi thông báo.');
    }

    public function show(Request $request, Task $task, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($tasks->canView($user, $task), 403);

        $task->load([
            'assignee', 'creator', 'watchers', 'checklistItems',
            'subtasks.assignee', 'comments.user', 'attachments.user', 'activityLogs.user',
        ]);

        return response()->json(array_merge([
            'task' => $task,
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'users' => $tasks->assignableUsers($user)->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values(),
        ], $tasks->abilities($user, $task)));
    }

    public function publish(Request $request, Task $task, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($tasks->canEdit($user, $task), 403);

        $task = $tasks->publish($user, $task);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'task' => $task]);
        }

        return back()->with('success', 'Đã công bố công việc.');
    }

    public function duplicate(Request $request, Task $task, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($tasks->canView($user, $task), 403);
        abort_unless($user->hasPermission('tasks.manage') || $tasks->isTaskAdmin($user), 403);

        $copy = $tasks->duplicate($user, $task);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'task' => $this->cardPayload($copy), 'open' => $copy->id]);
        }

        return redirect()->route('admin.tasks.index', ['open' => $copy->id])
            ->with('success', 'Đã sao chép thành bản nháp.');
    }

    public function update(Request $request, Task $task, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($tasks->canWork($user, $task), 403);

        $canEdit = $tasks->canEdit($user, $task);
        $canAssign = $canEdit && $tasks->canAssign($user);

        $data = $this->validated($request, $canAssign, updating: true);
        $watchers = $request->has('watcher_ids') ? (array) $request->input('watcher_ids', []) : null;

        // Người được giao: chỉ đổi trạng thái / mô tả tiến độ, không sửa meta giao việc
        if (! $canEdit) {
            $data = collect($data)->only(['status', 'description'])->all();
            $watchers = null;
        }

        $task = $tasks->update($user, $task, $data, $watchers);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'task' => $task]);
        }

        return back()->with('success', 'Đã cập nhật công việc.');
    }

    public function updateStatus(Request $request, Task $task, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($tasks->canWork($user, $task), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'position' => 'nullable|integer|min:0',
            'ordered_ids' => 'nullable|array',
            'ordered_ids.*' => 'integer',
        ]);

        $task = $tasks->moveStatus($user, $task, $data['status'], $data['position'] ?? null);

        if (! empty($data['ordered_ids'])) {
            $tasks->reorderColumn($data['status'], $data['ordered_ids']);
        }

        return response()->json(['ok' => true, 'task' => $this->cardPayload($task->fresh(['assignee', 'checklistItems', 'subtasks']))]);
    }

    public function destroy(Request $request, Task $task, TaskService $tasks)
    {
        $user = $request->user();
        abort_unless($tasks->canDelete($user, $task), 403);
        $tasks->delete($user, $task);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('admin.tasks.index')->with('success', 'Đã xóa công việc.');
    }

    public function storeChecklist(Request $request, Task $task, TaskService $tasks)
    {
        abort_unless($tasks->canWork($request->user(), $task), 403);
        $data = $request->validate(['title' => 'required|string|max:255']);
        $item = $tasks->addChecklistItem($task, $data['title']);

        return response()->json(['ok' => true, 'item' => $item]);
    }

    public function toggleChecklist(Request $request, Task $task, TaskChecklistItem $item, TaskService $tasks)
    {
        abort_unless($item->task_id === $task->id, 404);
        abort_unless($tasks->canWork($request->user(), $task), 403);
        $data = $request->validate(['is_done' => 'required|boolean']);
        $item = $tasks->toggleChecklistItem($item, (bool) $data['is_done']);

        return response()->json(['ok' => true, 'item' => $item]);
    }

    public function destroyChecklist(Request $request, Task $task, TaskChecklistItem $item, TaskService $tasks)
    {
        abort_unless($item->task_id === $task->id, 404);
        abort_unless($tasks->canWork($request->user(), $task), 403);
        $item->delete();

        return response()->json(['ok' => true]);
    }

    public function storeSubtask(Request $request, Task $task, TaskService $tasks)
    {
        abort_unless($tasks->canWork($request->user(), $task), 403);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'assignee_id' => 'nullable|exists:users,id',
        ]);
        $sub = $tasks->addSubtask($task, $data['title'], $data['assignee_id'] ?? null);

        return response()->json(['ok' => true, 'subtask' => $sub->load('assignee')]);
    }

    public function toggleSubtask(Request $request, Task $task, TaskSubtask $subtask, TaskService $tasks)
    {
        abort_unless($subtask->task_id === $task->id, 404);
        abort_unless($tasks->canWork($request->user(), $task), 403);
        $data = $request->validate(['is_done' => 'required|boolean']);
        $sub = $tasks->toggleSubtask($subtask, (bool) $data['is_done']);

        return response()->json(['ok' => true, 'subtask' => $sub->load('assignee')]);
    }

    public function destroySubtask(Request $request, Task $task, TaskSubtask $subtask, TaskService $tasks)
    {
        abort_unless($subtask->task_id === $task->id, 404);
        abort_unless($tasks->canWork($request->user(), $task), 403);
        $subtask->delete();

        return response()->json(['ok' => true]);
    }

    public function storeComment(Request $request, Task $task, TaskService $tasks)
    {
        abort_unless($tasks->canComment($request->user(), $task), 403);
        $data = $request->validate(['body' => 'required|string|max:5000']);
        $comment = $tasks->addComment($request->user(), $task, $data['body']);

        return response()->json(['ok' => true, 'comment' => $comment]);
    }

    public function storeAttachment(Request $request, Task $task, TaskService $tasks)
    {
        abort_unless($tasks->canWork($request->user(), $task), 403);
        $request->validate(['file' => 'required|file|max:10240']);
        $attachment = $tasks->addAttachment($request->user(), $task, $request->file('file'));

        return response()->json(['ok' => true, 'attachment' => [
            'id' => $attachment->id,
            'original_name' => $attachment->original_name,
            'url' => $attachment->url(),
            'size' => $attachment->size,
        ]]);
    }

    public function destroyAttachment(Request $request, Task $task, TaskAttachment $attachment, TaskService $tasks)
    {
        abort_unless($attachment->task_id === $task->id, 404);
        abort_unless($tasks->canWork($request->user(), $task), 403);
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['ok' => true]);
    }

    protected function validated(Request $request, bool $canAssign, bool $updating = false): array
    {
        if ($request->has('due_date') && $request->input('due_date') === '') {
            $request->merge(['due_date' => null]);
        }
        if ($request->has('start_date') && $request->input('start_date') === '') {
            $request->merge(['start_date' => null]);
        }

        $data = $request->validate([
            'title' => ($updating ? 'sometimes|' : '').'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'assignee_id' => ($canAssign ? 'nullable|exists:users,id' : 'nullable'),
            'status' => ['nullable', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(Task::PRIORITIES))],
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'branch_id' => 'nullable|exists:branches,id',
            'watcher_ids' => 'nullable|array',
            'watcher_ids.*' => 'integer|exists:users,id',
            'checklist' => 'nullable|array',
            'checklist.*' => 'nullable|string|max:255',
        ]);

        if (! $canAssign) {
            unset($data['assignee_id']);
        }

        return $data;
    }

    protected function cardPayload(Task $task): array
    {
        $check = $task->checklistProgress();
        $subs = $task->subtaskProgress();

        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
            'priority_label' => $task->priorityLabel(),
            'due_date' => $task->due_date?->format('Y-m-d H:i'),
            'due_label' => $task->due_date?->format('d/m H:i'),
            'is_overdue' => $task->isOverdue(),
            'is_published' => (bool) $task->is_published,
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
                'initials' => $task->assignee->initials(),
            ] : null,
            'checklist' => $check,
            'subtasks' => $subs,
        ];
    }
}
