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
        <div class="form-group"><label>Email đăng nhập *</label><input type="email" name="email" class="form-control" value="{{ old('email', $teacher->email ?? '') }}" required></div>
    </div>
    <div class="col-md-6">
        <div class="form-group"><label>Số điện thoại</label><input name="phone" class="form-control" value="{{ old('phone', $teacher->phone ?? '') }}"></div>
    </div>
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
