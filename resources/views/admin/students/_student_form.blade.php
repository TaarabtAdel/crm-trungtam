@php $selectedClasses = collect(old('class_ids', isset($student) ? $student->classes->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all(); @endphp
<div class="row">
    <div class="col-md-6"><div class="form-group"><label>Chi nhánh *</label>
        <select name="branch_id" class="form-control" required>
            <option value="">-- Chọn --</option>
            @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $student->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>@endforeach
        </select>
    </div></div>
    <div class="col-md-6"><div class="form-group"><label>Họ và tên *</label><input name="name" class="form-control" value="{{ old('name', $student->name ?? '') }}" required></div></div>
    <div class="col-md-4"><div class="form-group"><label>Ngày sinh</label><input type="date" name="dob" class="form-control" value="{{ old('dob', optional($student->dob ?? null)->format('Y-m-d')) }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Giới tính</label>
        <select name="gender" class="form-control">
            @foreach(['Nam','Nữ','Khác'] as $g)<option value="{{ $g }}" @selected(old('gender', $student->gender ?? 'Nam')===$g)>{{ $g }}</option>@endforeach
        </select>
    </div></div>
    <div class="col-md-4"><div class="form-group"><label>Trạng thái</label>
        <select name="status" class="form-control">
            @foreach(\App\Models\Student::statusOptions() as $k=>$v)
                <option value="{{ $k }}" @selected(old('status', $student->status ?? 'studying')===$k)>{{ $v }}</option>
            @endforeach
        </select>
    </div></div>
    <div class="col-md-6"><div class="form-group"><label>SĐT học viên</label><input name="phone" class="form-control" value="{{ old('phone', $student->phone ?? '') }}" placeholder="Số điện thoại học viên"></div></div>
    <div class="col-md-6"><div class="form-group"><label>Email học viên</label><input type="email" name="email" class="form-control" value="{{ old('email', $student->email ?? '') }}" placeholder="email@example.com"></div></div>
    <div class="col-md-4"><div class="form-group"><label>SĐT người thân</label><input name="parent_phone" class="form-control" value="{{ old('parent_phone', $student->parent_phone ?? '') }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Tên người thân</label><input name="parent_name" class="form-control" value="{{ old('parent_name', $student->parent_name ?? '') }}"></div></div>
    <div class="col-md-4"><div class="form-group"><label>Email người thân</label><input type="email" name="parent_email" class="form-control" value="{{ old('parent_email', $student->parent_email ?? '') }}"></div></div>
    @unless(!empty($hideClasses))
    <div class="col-md-12">
        <div class="form-group mb-0">
            <label>Lớp học</label>
            @if(($classes ?? collect())->isEmpty())
                <div class="text-muted small">Chưa có lớp đang hoạt động.</div>
            @else
                <div class="student-class-checks border rounded p-3">
                    <div class="row">
                        @foreach($classes as $c)
                            <div class="col-md-6 col-lg-4 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="studentClass{{ $c->id }}"
                                           name="class_ids[]"
                                           value="{{ $c->id }}"
                                           @checked(in_array((int) $c->id, $selectedClasses, true))>
                                    <label class="custom-control-label" for="studentClass{{ $c->id }}">{{ $c->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <small class="text-muted">Tick các lớp muốn ghi danh (có thể chọn nhiều).</small>
            @endif
        </div>
    </div>
    @endunless
    <div class="col-md-6"><div class="form-group"><label>Địa chỉ</label><textarea name="address" class="form-control" rows="2">{{ old('address', $student->address ?? '') }}</textarea></div></div>
    <div class="col-md-6"><div class="form-group"><label>Ghi chú</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $student->notes ?? '') }}</textarea></div></div>
</div>
