@php $selectedDays = old('schedule_days', $class->schedule_days ?? []); @endphp
<div class="row">
    <div class="col-md-6"><div class="form-group"><label>Chi nhánh *</label>
        <select name="branch_id" class="form-control" required>
            <option value="">-- Chọn --</option>
            @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $class->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>@endforeach
        </select>
    </div></div>
    <div class="col-md-6"><div class="form-group"><label>Tên lớp *</label><input name="name" class="form-control" value="{{ old('name', $class->name ?? '') }}" required></div></div>
    <div class="col-md-6"><div class="form-group"><label>Mã lớp</label><input name="code" class="form-control" value="{{ old('code', $class->code ?? '') }}"></div></div>
    <div class="col-md-6"><div class="form-group"><label>Môn học</label>
        <select name="subject_id" class="form-control">
            <option value="">-- Chọn --</option>
            @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id', $class->subject_id ?? '')==$s->id)>{{ $s->name }}</option>@endforeach
        </select>
    </div></div>
    <div class="col-md-6"><div class="form-group"><label>Giáo viên</label>
        <select name="teacher_id" class="form-control">
            <option value="">-- Chọn --</option>
            @foreach($teachers as $t)<option value="{{ $t->id }}" @selected(old('teacher_id', $class->teacher_id ?? '')==$t->id)>{{ $t->name }}</option>@endforeach
        </select>
    </div></div>
    <div class="col-md-6"><div class="form-group"><label>Phòng học</label><input name="room" class="form-control" value="{{ old('room', $class->room ?? '') }}"></div></div>
    <div class="col-12"><div class="form-group"><label>Lịch học</label><div class="day-toggle">
        @foreach($days as $d)
            <label class="btn btn-outline-primary btn-sm {{ in_array($d, $selectedDays) ? 'active' : '' }}">
                <input type="checkbox" name="schedule_days[]" value="{{ $d }}" {{ in_array($d, $selectedDays) ? 'checked' : '' }} class="d-none" onchange="this.parentElement.classList.toggle('active', this.checked)"> {{ $d }}
            </label>
        @endforeach
    </div></div></div>
    <div class="col-md-3"><div class="form-group"><label>Giờ bắt đầu</label><input type="time" name="start_time" class="form-control" value="{{ old('start_time', isset($class->start_time) ? substr($class->start_time,0,5) : '08:00') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Giờ kết thúc</label><input type="time" name="end_time" class="form-control" value="{{ old('end_time', isset($class->end_time) ? substr($class->end_time,0,5) : '10:00') }}"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Sĩ số tối đa</label><input type="number" name="max_students" class="form-control" value="{{ old('max_students', $class->max_students ?? 0) }}" min="0"></div></div>
    <div class="col-md-3"><div class="form-group"><label>Loại học phí *</label>
        <select name="tuition_type" class="form-control js-tuition-type">
            <option value="monthly" @selected(old('tuition_type', $class->tuition_type ?? 'monthly')==='monthly')>Theo tháng</option>
            <option value="per_session" @selected(old('tuition_type', $class->tuition_type ?? '')==='per_session')>Theo buổi</option>
        </select>
    </div></div>
    <div class="col-md-4"><div class="form-group">
        <label class="js-tuition-fee-label">{{ old('tuition_type', $class->tuition_type ?? 'monthly')==='per_session' ? 'Học phí / buổi' : 'Học phí / tháng' }}</label>
        <input type="number" name="tuition_fee" class="form-control" value="{{ old('tuition_fee', $class->tuition_fee ?? 0) }}" min="0">
        <small class="text-muted">Đơn giá cấu hình lớp. Doanh thu thực ghi nhận qua hóa đơn đã thu.</small>
    </div></div>
    <div class="col-md-4"><div class="form-group"><label>Ngày bắt đầu</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($class->start_date ?? null)->format('Y-m-d')) }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Ngày kết thúc</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($class->end_date ?? null)->format('Y-m-d')) }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Trạng thái</label>
        <select name="status" class="form-control">
            @foreach(\App\Models\CourseClass::statusOptions() as $k=>$v)
                <option value="{{ $k }}" @selected(old('status', $class->status ?? 'active')===$k)>{{ $v }}</option>
            @endforeach
        </select>
    </div></div>
</div>
