<p class="small text-muted mb-3">Tạo thêm tài khoản nhân sự. Có thể chọn nhiều vai trò.</p>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>Họ tên <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>Email đăng nhập <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>Mật khẩu <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control" required minlength="6">
    </div>
    <div class="form-group col-md-6">
        <label>SĐT</label>
        <input name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="form-group col-md-12">
        <label>Vai trò <span class="text-danger">*</span></label>
        <div class="user-roles-grid border rounded p-2">
            @foreach($roleOptions as $k => $v)
                @if($k === 'super_admin') @continue @endif
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="qs_role_{{ $k }}" name="roles[]" value="{{ $k }}"
                           @checked(in_array($k, old('roles', ['training']), true))>
                    <label class="custom-control-label" for="qs_role_{{ $k }}">{{ $v }}</label>
                </div>
            @endforeach
        </div>
    </div>
    <div class="form-group col-md-6 mb-0">
        <label>Chi nhánh</label>
        <select name="branch_id" class="form-control">
            <option value="">— Không gắn —</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
</div>
