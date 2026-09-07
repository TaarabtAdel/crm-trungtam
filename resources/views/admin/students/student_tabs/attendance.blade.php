<div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Lịch sử điểm danh</strong>
    @canPerm('attendances.view')
    <a href="{{ route('admin.attendances.index') }}" class="btn btn-sm btn-outline-secondary">Mở trang điểm danh</a>
    @endcanPerm
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>Lớp</th>
            <th>Trạng thái</th>
            <th>Ghi chú</th>
        </tr>
        </thead>
        <tbody>
        @forelse($attendances as $row)
            <tr>
                <td class="text-nowrap">{{ optional($row->session_date)->format('d/m/Y') }}</td>
                <td>
                    @if($row->courseClass)
                        <a href="{{ route('admin.classes.show', $row->courseClass) }}" class="text-dark">{{ $row->courseClass->name }}</a>
                    @else
                        —
                    @endif
                </td>
                <td>
                    <span class="lead-meta-chip">{{ \App\Models\Attendance::statusOptions()[$row->status] ?? $row->status }}</span>
                </td>
                <td class="small text-muted">{{ $row->note ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Chưa có dữ liệu điểm danh.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($attendances, 'links'))
    <div class="mt-2">{{ $attendances->appends(['tab' => 'attendance'])->links() }}</div>
@endif
