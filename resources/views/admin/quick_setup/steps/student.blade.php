<div class="form-row">
    <div class="form-group col-md-6">
        <label>Họ tên HV <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>Chi nhánh <span class="text-danger">*</span></label>
        <select name="branch_id" class="form-control" required>
            <option value="">-- Chọn --</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-4">
        <label>SĐT HV</label>
        <input name="phone" class="form-control" value="{{ old('phone') }}">
    </div>
    <div class="form-group col-md-4">
        <label>Email HV</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}">
    </div>
    <div class="form-group col-md-4">
        <label>Xếp vào lớp</label>
        <select name="class_id" class="form-control">
            <option value="">— Chưa xếp —</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(old('class_id') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-6">
        <label>Tên phụ huynh</label>
        <input name="parent_name" class="form-control" value="{{ old('parent_name') }}">
    </div>
    <div class="form-group col-md-6 mb-0">
        <label>SĐT phụ huynh</label>
        <input name="parent_phone" class="form-control" value="{{ old('parent_phone') }}">
    </div>
</div>
