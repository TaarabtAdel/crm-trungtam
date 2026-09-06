@php
    use App\Services\ClassTimetableGenerator;
    $scheduleDays = $class->schedule_days ?? [];
    $timeRange = trim(($class->start_time ? substr($class->start_time, 0, 5) : '').' – '.($class->end_time ? substr($class->end_time, 0, 5) : ''), ' –');
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <p class="small text-muted mb-0">
        Lương GV tính theo <strong>buổi hoàn thành</strong> trên TKB (theo giáo viên của từng buổi, kể cả dạy thay).
    </p>
    <form method="GET" action="{{ route('admin.classes.show', $class) }}" class="d-flex align-items-center" style="gap:.5rem">
        <input type="hidden" name="tab" value="timetable">
        <label class="mb-0 small text-muted">Tháng</label>
        <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width:160px" onchange="this.form.submit()">
    </form>
</div>

<div class="row">
    <div class="col-lg-4 mb-3">
        <div class="border rounded p-3 h-100">
            <h6 class="font-weight-bold mb-2">Tạo thời khóa biểu</h6>
            <p class="small text-muted mb-2">
                Theo lịch lớp:
                <strong>{{ $scheduleDays ? implode(', ', $scheduleDays) : 'chưa chọn thứ' }}</strong>
                @if($timeRange) · <strong>{{ $timeRange }}</strong> @endif
                @if($class->teacher) · GV mặc định: <strong>{{ $class->teacher->name }}</strong> @endif
            </p>
            @if(empty($scheduleDays))
                <div class="alert alert-warning py-2 small">Lớp chưa có lịch học (T2–CN). Cập nhật ở tab Thông tin.</div>
            @endif
            <form method="POST" action="{{ route('admin.classes.timetable.generate', $class) }}">
                @csrf
                <div class="form-group">
                    <label>Từ ngày *</label>
                    <input type="date" name="from" class="form-control" value="{{ old('from', $defaultFrom) }}" required>
                </div>
                <div class="form-group">
                    <label>Đến ngày *</label>
                    <input type="date" name="to" class="form-control" value="{{ old('to', $defaultTo) }}" required>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="replace_scheduled" value="0">
                    <input type="checkbox" class="form-check-input" name="replace_scheduled" value="1" id="replaceScheduled" checked>
                    <label class="form-check-label small" for="replaceScheduled">
                        Xóa buổi <em>Đã lên lịch</em> trong khoảng rồi tạo lại
                    </label>
                </div>
                <button class="btn btn-primary btn-block btn-sm" {{ empty($scheduleDays) ? 'disabled' : '' }}>
                    <i class="bi bi-calendar-plus"></i> Tạo thời khóa biểu
                </button>
            </form>

            <hr>
            <strong class="d-block mb-2">Thêm 1 buổi lẻ</strong>
            <form method="POST" action="{{ route('admin.classes.timetable.sessions.store', $class) }}">
                @csrf
                <div class="form-group">
                    <label>Ngày *</label>
                    <input type="date" name="session_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="form-group">
                    <label>Giáo viên dạy *</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">-- Chọn giáo viên --</option>
                        @foreach($sessionTeachers as $t)
                            <option value="{{ $t->id }}" @selected(old('teacher_id', $class->teacher_id)==$t->id)>
                                {{ $t->name }}@if($class->teacher_id && $t->id != $class->teacher_id) (dạy thay)@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Chọn GV dạy thay nếu khác GV phụ trách lớp.</small>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label>Bắt đầu</label>
                        <input type="time" name="start_time" class="form-control" value="{{ $class->start_time ? substr($class->start_time,0,5) : '08:00' }}">
                    </div>
                    <div class="form-group col-6">
                        <label>Kết thúc</label>
                        <input type="time" name="end_time" class="form-control" value="{{ $class->end_time ? substr($class->end_time,0,5) : '10:00' }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="scheduled">Đã lên lịch</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="cancelled">Hủy</option>
                    </select>
                </div>
                <button class="btn btn-outline-secondary btn-sm btn-block">Thêm buổi</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Buổi học tháng {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}</strong>
            <span class="badge badge-secondary">{{ $sessions->count() }} buổi</span>
        </div>
        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Thứ</th>
                    <th>Giờ</th>
                    <th>GV dạy</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($sessions as $session)
                    @php
                        $sessionTeacherId = $session->teacher_id ?: $class->teacher_id;
                        $isSubstitute = $sessionTeacherId && $class->teacher_id && $sessionTeacherId != $class->teacher_id;
                    @endphp
                    <tr>
                        <td>{{ $session->session_date->format('d/m/Y') }}</td>
                        <td>{{ ClassTimetableGenerator::dayLabel($session->session_date) }}</td>
                        <td>
                            {{ $session->start_time ? substr($session->start_time,0,5) : '—' }}
                            –
                            {{ $session->end_time ? substr($session->end_time,0,5) : '—' }}
                        </td>
                        <td>
                            {{ $session->teacher?->name ?? $class->teacher?->name ?? '—' }}
                            @if($isSubstitute)
                                <br><span class="badge badge-warning">Dạy thay</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badge = match($session->status) {
                                    'completed' => 'success',
                                    'cancelled' => 'secondary',
                                    default => 'info',
                                };
                            @endphp
                            <span class="badge badge-{{ $badge }}">{{ $session->statusLabel() }}</span>
                        </td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editSession{{ $session->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.classes.timetable.sessions.destroy', [$class, $session]) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa buổi này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có buổi học trong tháng này.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($sessions as $session)
<div class="modal fade" id="editSession{{ $session->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.classes.timetable.sessions.update', [$class, $session]) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Sửa buổi {{ $session->session_date->format('d/m/Y') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group"><label>Ngày *</label>
                    <input type="date" name="session_date" class="form-control" value="{{ $session->session_date->format('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>Giáo viên dạy *</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">-- Chọn giáo viên --</option>
                        @foreach($sessionTeachers as $t)
                            <option value="{{ $t->id }}" @selected(old('teacher_id', $session->teacher_id ?: $class->teacher_id)==$t->id)>
                                {{ $t->name }}@if($class->teacher_id && $t->id != $class->teacher_id) (dạy thay)@endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Buổi hoàn thành sẽ tính lương cho GV được chọn ở đây.</small>
                </div>
                <div class="form-row">
                    <div class="form-group col-6"><label>Bắt đầu</label>
                        <input type="time" name="start_time" class="form-control" value="{{ $session->start_time ? substr($session->start_time,0,5) : '' }}">
                    </div>
                    <div class="form-group col-6"><label>Kết thúc</label>
                        <input type="time" name="end_time" class="form-control" value="{{ $session->end_time ? substr($session->end_time,0,5) : '' }}">
                    </div>
                </div>
                <div class="form-group"><label>Trạng thái</label>
                    <select name="status" class="form-control">
                        @foreach(['scheduled'=>'Đã lên lịch','completed'=>'Hoàn thành','cancelled'=>'Hủy'] as $k=>$v)
                            <option value="{{ $k }}" @selected($session->status===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mb-0"><label>Ghi chú</label>
                    <textarea name="notes" class="form-control" rows="2">{{ $session->notes }}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
@endforeach
