@php
    use App\Services\ClassTimetableGenerator;
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <strong>
            @if($scheduleMonth !== '')
                Lịch dạy tháng {{ \Carbon\Carbon::createFromFormat('Y-m', $scheduleMonth)->format('m/Y') }}
            @else
                Tất cả lịch dạy
            @endif
        </strong>
        <span class="badge badge-secondary ml-1">{{ $sessions->count() }} buổi</span>
    </div>
    <form method="GET" action="{{ route('admin.teachers.show', $teacher) }}" class="d-flex align-items-center" style="gap:.5rem">
        <input type="hidden" name="tab" value="schedule">
        <label class="mb-0 small text-muted">Lọc tháng</label>
        <select name="month" class="form-control form-control-sm" style="width:170px" onchange="this.form.submit()">
            <option value="">Tất cả</option>
            @foreach($availableMonths as $ym)
                <option value="{{ $ym }}" @selected($scheduleMonth === $ym)>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('m/Y') }}
                </option>
            @endforeach
            @if($scheduleMonth !== '' && ! in_array($scheduleMonth, $availableMonths, true))
                <option value="{{ $scheduleMonth }}" selected>{{ \Carbon\Carbon::createFromFormat('Y-m', $scheduleMonth)->format('m/Y') }}</option>
            @endif
        </select>
        @if($scheduleMonth !== '')
            <a href="{{ route('admin.teachers.show', ['teacher' => $teacher, 'tab' => 'schedule']) }}" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        @endif
    </form>
</div>

<div class="table-responsive border rounded" style="max-height:560px;overflow:auto">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>Thứ</th>
            <th>Lớp</th>
            <th>Giờ</th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($sessions as $session)
            @php
                $isSub = $session->courseClass
                    && $session->courseClass->teacher_id
                    && (int) $session->courseClass->teacher_id !== (int) $teacher->id;
                $badge = match ($session->status) {
                    'completed' => 'success',
                    'cancelled' => 'secondary',
                    default => 'info',
                };
            @endphp
            <tr>
                <td>{{ $session->session_date->format('d/m/Y') }}</td>
                <td>{{ ClassTimetableGenerator::dayLabel($session->session_date) }}</td>
                <td>{{ $session->courseClass?->name ?: '—' }}</td>
                <td>
                    {{ $session->start_time ? substr($session->start_time, 0, 5) : '—' }}
                    –
                    {{ $session->end_time ? substr($session->end_time, 0, 5) : '—' }}
                </td>
                <td>
                    @if($isSub)
                        <span class="badge badge-warning">Dạy thay</span>
                    @else
                        <span class="badge badge-info">Phụ trách</span>
                    @endif
                </td>
                <td><span class="badge badge-{{ $badge }}">{{ $session->statusLabel() }}</span></td>
                <td class="text-right">
                    @if($session->courseClass)
                        @canPerm('training.classes.view')
                        <a href="{{ route('admin.classes.show', ['class' => $session->courseClass, 'tab' => 'timetable']) }}" class="btn btn-sm btn-outline-primary">TKB lớp</a>
                        @endcanPerm
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ $scheduleMonth !== '' ? 'Chưa có lịch trong tháng này.' : 'Chưa có lịch dạy.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
