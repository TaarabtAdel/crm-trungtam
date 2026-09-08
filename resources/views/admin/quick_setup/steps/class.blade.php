<div class="form-row">
    <div class="form-group col-md-8">
        <label>Tên lớp <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="form-group col-md-4">
        <label>Chi nhánh <span class="text-danger">*</span></label>
        <select name="branch_id" class="form-control" required>
            <option value="">-- Chọn --</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label>Môn học</label>
        <select name="subject_id" class="form-control">
            <option value="">-- Chọn --</option>
            @foreach($subjects as $s)
                <option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label>Giáo viên</label>
        <select name="teacher_id" class="form-control">
            <option value="">-- Chọn --</option>
            @foreach($teachers as $t)
                <option value="{{ $t->id }}" @selected(old('teacher_id') == $t->id)>{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-12">
        <label>Lịch học (thứ)</label>
        <div class="d-flex flex-wrap" style="gap:.5rem 1rem">
            @foreach($days as $d)
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="day_{{ $d }}" name="schedule_days[]" value="{{ $d }}"
                           @checked(in_array($d, old('schedule_days', ['T2', 'T4']), true))>
                    <label class="custom-control-label" for="day_{{ $d }}">{{ $d }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group col-md-3">
        <label>Giờ bắt đầu</label>
        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', '18:00') }}">
    </div>
    <div class="form-group col-md-3">
        <label>Giờ kết thúc</label>
        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', '20:00') }}">
    </div>
    <div class="form-group col-md-3">
        <label>Loại học phí</label>
        <select name="tuition_type" class="form-control">
            <option value="monthly" @selected(old('tuition_type', 'monthly') === 'monthly')>Theo tháng</option>
            <option value="per_session" @selected(old('tuition_type') === 'per_session')>Theo buổi</option>
        </select>
    </div>
    <div class="form-group col-md-3">
        <label>Đơn giá</label>
        <input type="number" name="tuition_fee" class="form-control" value="{{ old('tuition_fee', 0) }}" min="0">
    </div>
</div>
<hr>
<div class="font-weight-bold small mb-2">Sinh thời khóa biểu (khuyến nghị)</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>Từ ngày</label>
        <input type="date" name="tt_from" class="form-control" value="{{ old('tt_from', now()->startOfMonth()->toDateString()) }}">
    </div>
    <div class="form-group col-md-6">
        <label>Đến ngày</label>
        <input type="date" name="tt_to" class="form-control" value="{{ old('tt_to', now()->addMonth()->endOfMonth()->toDateString()) }}">
    </div>
</div>
<p class="small text-muted mb-0">Cần chọn thứ + khoảng ngày để sinh buổi. Không điền vẫn tạo lớp (checklist lớp chưa xong nếu chưa có buổi).</p>
@if($classes->isNotEmpty())
    <p class="small text-success mt-2 mb-0"><i class="bi bi-check-circle"></i> Đã có {{ $classes->count() }} lớp.</p>
@endif
