<div class="form-group"><label>Họ tên *</label><input name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required></div>
<div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required></div>
<div class="form-group"><label>Mật khẩu {{ $user ? '(để trống nếu không đổi)' : '*' }}</label><input type="password" name="password" class="form-control" {{ $user ? '' : 'required' }}></div>
<div class="form-group"><label>Vai trò *</label>
    <select name="role" class="form-control" required>
        @foreach(['super_admin'=>'Super Admin','admin'=>'Admin','sales'=>'Sales','teacher'=>'Giáo viên'] as $k=>$v)
            <option value="{{ $k }}" @selected(old('role', $user->role ?? 'admin')===$k)>{{ $v }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>SĐT</label><input name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}"></div>
<div class="form-group"><label>Chi nhánh</label>
    <select name="branch_id" class="form-control">
        <option value="">-- Chọn --</option>
        @foreach($branches as $b)
            <option value="{{ $b->id }}" @selected(old('branch_id', $user->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
</div>
@if($user)
<div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="uactive{{ $user->id }}" {{ ($user->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label" for="uactive{{ $user->id }}">Đang hoạt động</label></div>
@else
<input type="hidden" name="is_active" value="1">
@endif
