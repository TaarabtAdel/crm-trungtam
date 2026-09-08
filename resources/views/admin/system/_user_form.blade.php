@php
    $selectedRoles = old('roles', $user ? $user->roleKeys() : ['admin']);
    if (! is_array($selectedRoles)) {
        $selectedRoles = [$selectedRoles];
    }
    $uid = $user->id ?? 'new';
@endphp

<div class="form-row">
    <div class="form-group col-md-6">
        <label>Họ tên <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>Email đăng nhập <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required autocomplete="off">
    </div>
    <div class="form-group col-md-6">
        <label>Mật khẩu {{ $user ? '' : '*' }}</label>
        <input type="password" name="password" class="form-control" {{ $user ? '' : 'required' }} autocomplete="new-password"
               placeholder="{{ $user ? 'Để trống nếu không đổi' : 'Tối thiểu 6 ký tự' }}">
    </div>
    <div class="form-group col-md-6">
        <label>Số điện thoại</label>
        <input name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}" placeholder="09...">
    </div>
    <div class="form-group col-md-6">
        <label>Lương ngày (đ)</label>
        <input type="number" name="daily_rate" class="form-control" value="{{ old('daily_rate', $user->daily_rate ?? 0) }}" min="0" step="1000">
        <small class="text-muted">Dùng khi chấm công tính lương nhân viên.</small>
    </div>
    <div class="form-group col-md-12">
        <label>Vai trò <span class="text-danger">*</span> <small class="text-muted">(có thể chọn nhiều)</small></label>
        <div class="user-roles-grid border rounded p-2">
            @foreach(config('permissions.roles') as $k => $v)
                <div class="custom-control custom-checkbox">
                    <input type="checkbox"
                           class="custom-control-input"
                           id="role_{{ $uid }}_{{ $k }}"
                           name="roles[]"
                           value="{{ $k }}"
                           @checked(in_array($k, $selectedRoles, true))>
                    <label class="custom-control-label" for="role_{{ $uid }}_{{ $k }}">{{ $v }}</label>
                </div>
            @endforeach
        </div>
        <small class="text-muted">Quyền thực tế = hợp quyền của các vai trò đã chọn (cấu hình tại Phân quyền).</small>
    </div>
    <div class="form-group col-md-6">
        <label>Chi nhánh</label>
        <select name="branch_id" class="form-control">
            <option value="">— Không gắn / toàn hệ thống —</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id', $user->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6 d-flex align-items-end">
        @if($user)
            <input type="hidden" name="is_active" value="0">
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox" class="custom-control-input" id="uactive{{ $user->id }}" name="is_active" value="1" @checked((string) old('is_active', $user->is_active ? '1' : '0') === '1')>
                <label class="custom-control-label" for="uactive{{ $user->id }}">Đang hoạt động</label>
            </div>
        @else
            <input type="hidden" name="is_active" value="1">
            <span class="badge badge-success mb-3">Tạo mới sẽ Active</span>
        @endif
    </div>
</div>
