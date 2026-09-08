<div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Lớp đang theo học ({{ $studentClasses->count() }})</strong>
</div>
<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Lớp</th>
            <th>Môn / GV</th>
            <th>Lịch</th>
            <th>Trạng thái</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($studentClasses as $class)
            <tr>
                <td>
                    <a href="{{ route('admin.classes.show', $class) }}" class="font-weight-bold text-dark">{{ $class->name }}</a>
                    @if($class->code)<div class="small text-muted">{{ $class->code }}</div>@endif
                </td>
                <td>
                    <div>{{ $class->subject?->name ?: '—' }}</div>
                    <div class="small text-muted">{{ $class->teacher?->name ?: 'Chưa gán GV' }}</div>
                </td>
                <td class="small">
                    @if(!empty($class->schedule_days))
                        {{ implode(', ', $class->schedule_days) }}
                        <div class="text-muted">
                            {{ $class->start_time ? substr($class->start_time, 0, 5) : '' }}
                            @if($class->start_time || $class->end_time)–@endif
                            {{ $class->end_time ? substr($class->end_time, 0, 5) : '' }}
                        </div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td><span class="badge lead-status {{ $class->statusBadgeClass() }}">{{ $class->statusLabel() }}</span></td>
                <td class="text-nowrap text-right">
                    <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'timetable']) }}" class="btn btn-sm btn-outline-secondary" title="TKB"><i class="bi bi-calendar3"></i></a>
                    @canPerm('students.manage')
                    <form method="POST" action="{{ route('admin.students.classes.detach', [$student, $class]) }}" class="d-inline" onsubmit="return confirm('Gỡ khỏi lớp?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash"></i></button>
                    </form>
                    @endcanPerm
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Chưa xếp lớp.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
