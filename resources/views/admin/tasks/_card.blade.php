@php
    $prog = $task->checklistProgress();
    $subs = $task->subtaskProgress();
    $canWorkCard = app(\App\Services\Tasks\TaskService::class)->canWork(auth()->user(), $task);
@endphp
<div class="task-card" data-id="{{ $task->id }}" data-can-work="{{ $canWorkCard ? '1' : '0' }}" draggable="false">
    <div class="task-card-title">
        @unless($task->is_published)
            <span class="task-draft-pill" title="Bản nháp">Nháp</span>
        @endunless
        {{ $task->title }}
    </div>
    <div class="task-card-meta">
        <span class="task-priority task-priority-{{ $task->priority }}">{{ $task->priorityLabel() }}</span>
        @if($task->due_date)
            <span class="task-due {{ $task->isOverdue() ? 'is-overdue' : '' }}">
                <i class="bi bi-calendar-event"></i> {{ $task->due_date->format('d/m') }}
            </span>
        @endif
    </div>
    <div class="task-card-foot">
        <div class="task-card-progress">
            @if($prog['total'] > 0)
                <span title="Checklist"><i class="bi bi-check2-square"></i> {{ $prog['done'] }}/{{ $prog['total'] }}</span>
            @endif
            @if($subs['total'] > 0)
                <span title="Subtask"><i class="bi bi-diagram-3"></i> {{ $subs['done'] }}/{{ $subs['total'] }}</span>
            @endif
        </div>
        @if($task->assignee)
            <span class="task-avatar" title="{{ $task->assignee->name }}">{{ $task->assignee->initials() }}</span>
        @endif
    </div>
</div>
