<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Học viên</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $class->students_count }} / {{ $class->max_students }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Buổi học (TKB)</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $class->sessions_count }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Học phí</div>
            <div class="stat-value" style="font-size:1.1rem">{{ $class->tuitionDisplay() }}</div>
            <small class="text-muted">{{ $class->tuitionTypeLabel() }}</small>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Lịch học</div>
            <div class="font-weight-bold">
                {{ !empty($class->schedule_days) ? implode(', ', $class->schedule_days) : '—' }}
            </div>
            <small class="text-muted">
                {{ $class->start_time ? substr($class->start_time,0,5) : '' }}
                @if($class->start_time || $class->end_time) – @endif
                {{ $class->end_time ? substr($class->end_time,0,5) : '' }}
                @if($class->room) · Phòng {{ $class->room }} @endif
            </small>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.classes.update', $class) }}">
    @csrf @method('PUT')
    <input type="hidden" name="from_detail" value="1">
    @include('admin.training._class_form', ['class' => $class, 'branches' => $branches, 'subjects' => $subjects, 'teachers' => $teachers, 'days' => $days])
    <div class="text-right mt-2">
        <button class="btn btn-primary">Lưu thông tin lớp</button>
    </div>
</form>
