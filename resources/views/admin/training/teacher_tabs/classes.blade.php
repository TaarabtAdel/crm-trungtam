<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <strong>Lớp đang dạy ({{ $teachingClasses->count() }})</strong>
    @canPerm('training.classes.view')
    <a href="{{ route('admin.classes.index') }}" class="btn btn-sm btn-outline-secondary">Danh sách lớp</a>
    @endcanPerm
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Lớp</th>
            <th>Môn</th>
            <th>Chi nhánh</th>
            <th>HV</th>
            <th>Buổi</th>
            <th>Vai trò</th>
            <th>TT</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($teachingClasses as $class)
            <tr>
                <td class="font-weight-bold">{{ $class->name }}</td>
                <td>{{ $class->subject?->name ?: '—' }}</td>
                <td>{{ $class->branch?->name ?: '—' }}</td>
                <td>{{ $class->students_count }}</td>
                <td>{{ $class->sessions_count }}</td>
                <td>
                    @if($class->is_substitute_only)
                        <span class="badge badge-warning">Dạy thay</span>
                    @else
                        <span class="badge badge-info">Phụ trách</span>
                    @endif
                </td>
                <td><span class="lead-meta-chip">{{ $class->status }}</span></td>
                <td class="text-right">
                    @canPerm('training.classes.view')
                    <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a>
                    @endcanPerm
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Chưa gắn lớp nào.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
