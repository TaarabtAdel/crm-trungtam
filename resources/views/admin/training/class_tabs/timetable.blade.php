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
        <label class="mb-0 small text-muted">Lọc tháng</label>
        <select name="month" class="form-control form-control-sm" style="width:170px" onchange="this.form.submit()">
            <option value="">Tất cả</option>
            @foreach($availableMonths as $ym)
                <option value="{{ $ym }}" @selected($month === $ym)>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('m/Y') }}
                </option>
            @endforeach
            @if($month !== '' && ! in_array($month, $availableMonths, true))
                <option value="{{ $month }}" selected>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}</option>
            @endif
        </select>
        @if($month !== '')
            <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'timetable']) }}" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
        @endif
        <a href="{{ route('admin.classes.timetable.pdf', ['class' => $class, 'month' => $month ?: null]) }}"
           class="btn btn-sm btn-outline-danger" target="_blank" title="Xuất thời khóa biểu PDF">
            <i class="bi bi-file-earmark-pdf"></i> Xuất TKB
        </a>
    </form>
</div>

<div class="row">
    @canPerm('training.classes.manage')
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
                    <small class="text-muted">Hoàn thành chỉ khi đã điểm danh đủ HV trong lớp.</small>
                </div>
                <div class="form-group">
                    <label>Buổi bù cho (tuỳ chọn)</label>
                    <select name="makeup_of_session_id" class="form-control">
                        <option value="">— Không phải buổi bù —</option>
                        @foreach($cancelledSessions as $cancelled)
                            <option value="{{ $cancelled->id }}">
                                Hủy {{ $cancelled->session_date?->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="hidden" name="force_conflict" value="0">
                    <input type="checkbox" class="custom-control-input" id="forceConflictStore" name="force_conflict" value="1">
                    <label class="custom-control-label small" for="forceConflictStore">Bỏ qua cảnh báo trùng lịch (GV/phòng/HV)</label>
                </div>
                <button class="btn btn-outline-secondary btn-sm btn-block">Thêm buổi</button>
            </form>
        </div>
    </div>
    @endcanPerm

    <div class="col-lg-{{ auth()->user()?->hasPermission('training.classes.manage') ? '8' : '12' }} mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>
                @if($month !== '')
                    Buổi học tháng {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}
                @else
                    Tất cả buổi học
                @endif
            </strong>
            <div class="d-flex align-items-center" style="gap:.4rem">
                <span class="badge badge-secondary">{{ $sessions->count() }} buổi</span>
                <a href="{{ route('admin.classes.timetable.pdf', ['class' => $class, 'month' => $month ?: null]) }}"
                   class="btn btn-sm btn-danger" target="_blank">
                    <i class="bi bi-file-earmark-pdf"></i> Xuất TKB
                </a>
            </div>
        </div>
        <div class="table-responsive border rounded" style="max-height:560px;overflow:auto">
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
                            @if($session->isMakeup())
                                <br><span class="badge badge-warning">Bù {{ $session->makeupOf?->session_date?->format('d/m') }}</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @canPerm('attendances.manage')
                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#attendanceSession{{ $session->id }}" title="Điểm danh">
                                <i class="bi bi-clipboard-check"></i>
                                @php
                                    $dateKey = $session->session_date->format('Y-m-d');
                                    $dayAtt = $attendancesByDate->get($dateKey, collect());
                                    $marked = $dayAtt->count();
                                @endphp
                                @if($marked > 0)
                                    <span class="small">{{ $marked }}/{{ $classStudents->count() }}</span>
                                @endif
                            </button>
                            @endcanPerm
                            @if($session->status === 'completed')
                                <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'journal', 'month' => $session->session_date->format('Y-m')]) }}"
                                   class="btn btn-sm btn-outline-info" title="Nhật ký">
                                    <i class="bi bi-journal-text"></i>
                                </a>
                            @endif
                            @canPerm('training.classes.manage')
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editSession{{ $session->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.classes.timetable.sessions.destroy', [$class, $session]) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa buổi này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ $month !== '' ? 'Chưa có buổi học trong tháng này.' : 'Chưa có buổi học nào.' }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($sessions as $session)
@canPerm('training.classes.manage')
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
                    <select name="status" class="form-control js-session-status">
                        @foreach(['scheduled'=>'Đã lên lịch','completed'=>'Hoàn thành','cancelled'=>'Hủy'] as $k=>$v)
                            <option value="{{ $k }}" @selected($session->status===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Buổi bù cho</label>
                    <select name="makeup_of_session_id" class="form-control">
                        <option value="">— Không —</option>
                        @foreach($cancelledSessions as $cancelled)
                            <option value="{{ $cancelled->id }}" @selected((int) $session->makeup_of_session_id === (int) $cancelled->id)>
                                Hủy {{ $cancelled->session_date?->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="custom-control custom-checkbox mb-2">
                    <input type="hidden" name="force_conflict" value="0">
                    <input type="checkbox" class="custom-control-input" id="forceConflict{{ $session->id }}" name="force_conflict" value="1">
                    <label class="custom-control-label small" for="forceConflict{{ $session->id }}">Bỏ qua cảnh báo trùng lịch</label>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input js-session-completed-flag" id="sessionCompleted{{ $session->id }}" value="1">
                    <label class="form-check-label" for="sessionCompleted{{ $session->id }}">Đánh dấu buổi học hoàn thành (tính lương GV)</label>
                    <div class="small text-muted">
                        Bắt buộc <strong>điểm danh đủ học viên</strong> trước khi hoàn thành.
                        Tự tick khi chọn trạng thái <em>Hoàn thành</em> — hệ thống tạo nhật ký sẵn.
                    </div>
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
@endcanPerm
@endforeach

@canPerm('attendances.manage')
@foreach($sessions as $session)
@php
    $dateKey = $session->session_date->format('Y-m-d');
    $dayAtt = $attendancesByDate->get($dateKey, collect());
@endphp
<div class="modal fade" id="attendanceSession{{ $session->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.attendances.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="class_id" value="{{ $class->id }}">
            <input type="hidden" name="session_date" value="{{ $dateKey }}">
            <input type="hidden" name="redirect_to" value="{{ request()->getRequestUri() }}">
            <div class="modal-header">
                <h5 class="modal-title">
                    Điểm danh — {{ $session->session_date->format('d/m/Y') }}
                    <small class="text-muted font-weight-normal">
                        ({{ $session->start_time ? substr($session->start_time,0,5) : '—' }}–{{ $session->end_time ? substr($session->end_time,0,5) : '—' }})
                    </small>
                </h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" style="max-height:70vh;overflow-y:auto">
                @if($classStudents->isEmpty())
                    <p class="text-muted mb-0">Lớp chưa có học viên. Thêm học viên ở tab <strong>Học viên</strong> trước.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Học viên</th>
                                @foreach(\App\Models\Attendance::statusOptions() as $label)
                                    <th class="text-center text-nowrap">{{ $label }}</th>
                                @endforeach
                                <th style="min-width:160px">Ghi chú</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($classStudents as $student)
                                @php
                                    $row = $dayAtt->get($student->id);
                                    $st = $row?->status ?? 'present';
                                    $note = $row?->note ?? '';
                                @endphp
                                <tr>
                                    <td class="font-weight-bold">{{ $student->name }}</td>
                                    @foreach(array_keys(\App\Models\Attendance::statusOptions()) as $opt)
                                        <td class="text-center align-middle">
                                            <input type="radio" name="statuses[{{ $student->id }}]" value="{{ $opt }}" {{ $st === $opt ? 'checked' : '' }}>
                                        </td>
                                    @endforeach
                                    <td>
                                        <input type="text" name="notes[{{ $student->id }}]" value="{{ $note }}" class="form-control form-control-sm" placeholder="Ghi chú...">
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
                @if($classStudents->isNotEmpty())
                    <button class="btn btn-success">Lưu điểm danh</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endforeach
@endcanPerm
