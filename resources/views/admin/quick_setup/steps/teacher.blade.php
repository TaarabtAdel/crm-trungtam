<div class="form-row">
    <div class="form-group col-md-6">
        <label>Họ tên <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
        <small class="text-muted">Trùng email user để nhận nhắc nhật ký.</small>
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
    <div class="form-group col-md-4">
        <label>SĐT</label>
        <input name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="form-group col-md-4">
        <label>Lương / giờ</label>
        <input type="number" name="hourly_rate" class="form-control" value="{{ old('hourly_rate', 0) }}" min="0">
    </div>
    <div class="form-group col-md-12">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="create_login" name="create_login" value="1" @checked(old('create_login'))>
            <label class="custom-control-label" for="create_login">Tạo luôn tài khoản đăng nhập (role Giáo viên)</label>
        </div>
    </div>
    <div class="form-group col-md-6 mb-0">
        <label>Mật khẩu đăng nhập (nếu tạo TK)</label>
        <input type="password" name="password" class="form-control" minlength="6">
    </div>
</div>
@if($teachers->isNotEmpty())
    <p class="small text-success mt-2 mb-0"><i class="bi bi-check-circle"></i> Đã có {{ $teachers->count() }} GV.</p>
@endif
