@extends('layouts.admin')

@section('title', 'Công việc')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tasks.css') }}?v=4">
@endpush

@section('content')
@php
    use App\Models\Task;
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap:.75rem">
    <div>
        <h4 class="mb-1">Công việc</h4>
        <div class="text-muted small">Kanban · danh sách · giao việc · checklist · bình luận</div>
    </div>
    <div class="d-flex align-items-center" style="gap:.5rem">
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.tasks.index', array_filter(['view' => 'board', 'q' => $q, 'assignee_id' => $canFilterAssignees ? $assigneeId : null, 'priority' => $priority, 'due_filter' => $dueFilter ?? null])) }}"
               class="btn {{ $view === 'board' ? 'btn-primary' : 'btn-outline-primary' }}">Board</a>
            <a href="{{ route('admin.tasks.index', array_filter(['view' => 'list', 'q' => $q, 'assignee_id' => $canFilterAssignees ? $assigneeId : null, 'status' => $status, 'priority' => $priority, 'due_filter' => $dueFilter ?? null])) }}"
               class="btn {{ $view === 'list' ? 'btn-primary' : 'btn-outline-primary' }}">List</a>
        </div>
        @canPerm('tasks.manage')
        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#taskCreateModal">
            <i class="bi bi-plus-lg"></i> Tạo việc
        </button>
        @endcanPerm
    </div>
</div>

<form method="GET" class="card card-body mb-3 py-2" id="taskFilterForm">
    <input type="hidden" name="view" value="{{ $view }}">
    <div class="form-row align-items-end">
        <div class="col-md-3 mb-2">
            <label class="small text-muted mb-1">Tìm kiếm</label>
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Tên công việc...">
        </div>
        @if($canFilterAssignees ?? false)
        <div class="col-md-2 mb-2">
            <label class="small text-muted mb-1">Người thực hiện</label>
            <select name="assignee_id" class="form-control form-control-sm js-filter-assignee" data-placeholder="Tất cả">
                <option value="">Tất cả</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected((string)$assigneeId === (string)$u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($view === 'list')
        <div class="col-md-2 mb-2">
            <label class="small text-muted mb-1">Giai đoạn</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">Tất cả</option>
                @foreach(Task::STATUSES as $k => $label)
                    <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-md-2 mb-2">
            <label class="small text-muted mb-1">Ưu tiên</label>
            <select name="priority" class="form-control form-control-sm">
                <option value="">Tất cả</option>
                @foreach(Task::PRIORITIES as $k => $label)
                    <option value="{{ $k }}" @selected($priority === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="small text-muted mb-1">Trạng thái</label>
            <select name="due_filter" class="form-control form-control-sm">
                <option value="">Tất cả</option>
                <option value="open" @selected(($dueFilter ?? '') === 'open')>Chưa hoàn thành</option>
                <option value="done" @selected(($dueFilter ?? '') === 'done')>Đã hoàn thành</option>
                <option value="due_soon" @selected(($dueFilter ?? '') === 'due_soon')>Sắp đến hạn (3 ngày)</option>
                <option value="overdue" @selected(($dueFilter ?? '') === 'overdue')>Quá hạn</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="small text-muted mb-1">Hạn từ</label>
            <input type="date" name="due_from" value="{{ $dueFrom }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-1 mb-2">
            <button class="btn btn-sm btn-outline-secondary btn-block">Lọc</button>
        </div>
    </div>
</form>

@if($view === 'board')
    <div class="task-board" id="taskBoard">
        @foreach(Task::STATUSES as $statusKey => $statusLabel)
            <div class="task-column" data-status="{{ $statusKey }}">
                <div class="task-column-head">
                    <strong>{{ $statusLabel }}</strong>
                    <span class="badge badge-light">{{ ($columns[$statusKey] ?? collect())->count() }}</span>
                </div>
                <div class="task-column-body" data-status="{{ $statusKey }}">
                    @foreach($columns[$statusKey] ?? [] as $task)
                        @include('admin.tasks._card', ['task' => $task])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="table-responsive border rounded bg-white">
        <table class="table table-hover mb-0">
            <thead>
            <tr>
                <th>Công việc</th>
                <th>Người làm</th>
                <th>Ưu tiên</th>
                <th>Trạng thái</th>
                <th>Hạn</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($listTasks as $task)
                <tr class="task-row" data-id="{{ $task->id }}" style="cursor:pointer">
                    <td>
                        <div class="font-weight-bold">
                            @unless($task->is_published)
                                <span class="task-draft-pill">Nháp</span>
                            @endunless
                            {{ $task->title }}
                        </div>
                        @php $prog = $task->checklistProgress(); @endphp
                        @if($prog['total'] > 0)
                            <small class="text-muted"><i class="bi bi-check2-square"></i> {{ $prog['done'] }}/{{ $prog['total'] }}</small>
                        @endif
                    </td>
                    <td>{{ $task->assignee?->name ?? '—' }}</td>
                    <td><span class="task-priority task-priority-{{ $task->priority }}">{{ $task->priorityLabel() }}</span></td>
                    <td>{{ $task->statusLabel() }}</td>
                    <td class="{{ $task->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                        {{ $task->due_date?->format('d/m/Y H:i') ?? '—' }}
                    </td>
                    <td><button type="button" class="btn btn-sm btn-outline-primary js-open-task" data-id="{{ $task->id }}">Chi tiết</button></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Chưa có công việc.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($listTasks, 'links'))
        <div class="mt-3">{{ $listTasks->links() }}</div>
    @endif
@endif

@include('admin.tasks._create_modal')
@include('admin.tasks._detail_modal')
@include('partials.select2')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
window.TaskBoard = {
    urls: {
        show: @json(url('admin/tasks')),
        status: @json(url('admin/tasks')),
    },
    csrf: @json(csrf_token()),
    openId: {{ (int) $openId }},
    canManage: @json(auth()->user()->hasPermission('tasks.manage') || auth()->user()->isSuperAdmin()),
};
</script>
<script src="{{ asset('js/tasks.js') }}?v=7"></script>
@if($canFilterAssignees ?? false)
<script>
(function () {
    if (typeof crmSelect2Local !== 'function') return;
    crmSelect2Local($('.js-filter-assignee'), {
        placeholder: 'Tất cả người thực hiện',
        allowClear: true
    });
})();
</script>
@endif
@endpush
