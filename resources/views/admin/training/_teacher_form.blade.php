<div class="row">
    <div class="col-md-6">
        <div class="form-group"><label>Chi nhánh làm việc *</label>
            <select name="branch_id" class="form-control" required>
                <option value="">-- Chọn chi nhánh --</option>
                @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $teacher->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Họ và tên *</label><input name="name" class="form-control" value="{{ old('name', $teacher->name ?? '') }}" required></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Email đăng nhập *</label><input type="email" name="email" class="form-control" value="{{ old('email', $teacher->email ?? '') }}" required>
            <small class="text-muted">Dùng cùng email này để tạo tài khoản role Giáo viên.</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Số điện thoại</label><input name="phone" class="form-control" value="{{ old('phone', $teacher->phone ?? '') }}"></div>
    </div>
    @php
        $existingLogin = ! empty($teacher?->email)
            ? \App\Models\User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($teacher->email)])->exists()
            : false;
    @endphp
    @if(empty($teacher?->id) || ! $existingLogin)
    <div class="col-md-6">
        <div class="custom-control custom-checkbox mt-2 mb-2">
            <input type="checkbox" class="custom-control-input" id="create_login" name="create_login" value="1"
                   @checked(old('create_login', empty($teacher?->id)))
                   onchange="document.getElementById('teacher_login_password').disabled = !this.checked">
            <label class="custom-control-label" for="create_login">Tạo tài khoản đăng nhập (role Giáo viên)</label>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Mật khẩu đăng nhập</label>
            <input type="password" name="password" id="teacher_login_password" class="form-control"
                   minlength="6" autocomplete="new-password"
                   @disabled(! old('create_login', empty($teacher?->id)))>
            <small class="text-muted">Tối thiểu 6 ký tự. GV dùng để ghi nhật ký và xem lương.</small>
        </div>
    </div>
    @elseif($existingLogin)
    <div class="col-12">
        <div class="alert alert-light border py-2 small mb-3">
            Đã có tài khoản đăng nhập với email này (role Giáo viên nếu đã gán).
            <a href="{{ route('admin.my-payroll') }}">Bảng lương của tôi</a> gắn theo email hồ sơ GV.
        </div>
    </div>
    @endif
    <div class="col-md-6">
        <div class="form-group"><label>Chuyên môn</label><input name="specialty" class="form-control" value="{{ old('specialty', $teacher->specialty ?? '') }}"></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Trình độ</label><input name="qualification" class="form-control" value="{{ old('qualification', $teacher->qualification ?? '') }}" placeholder="Cử nhân, Thạc sĩ..."></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Lương theo giờ</label><input type="number" name="hourly_rate" class="form-control" value="{{ old('hourly_rate', $teacher->hourly_rate ?? 0) }}" min="0"></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Ngày vào làm</label><input type="date" name="joined_at" class="form-control" value="{{ old('joined_at', optional($teacher->joined_at ?? null)->format('Y-m-d')) }}"></div>
    </div>
    <div class="col-12">
        <div class="form-group"><label>Ghi chú</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $teacher->notes ?? '') }}</textarea></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Trạng thái</label>
            <select name="status" class="form-control">
                @foreach(\App\Models\Teacher::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(old('status', $teacher->status ?? 'active')===$k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
