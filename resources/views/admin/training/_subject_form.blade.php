<div class="form-group">
    <label>Tên môn học *</label>
    <input name="name" class="form-control" value="{{ old('name', $subject->name ?? '') }}" required placeholder="Nhập tên môn học...">
</div>
<div class="form-group">
    <label>Chi nhánh *</label>
    <select name="branch_id" class="form-control" required>
        <option value="">-- Chọn chi nhánh --</option>
        @foreach($branches as $b)
            <option value="{{ $b->id }}" @selected(old('branch_id', $subject->branch_id ?? $currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>Mô tả</label>
    <textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả môn học...">{{ old('description', $subject->description ?? '') }}</textarea>
</div>
<div class="form-group mb-0">
    <label>Trạng thái</label>
    <select name="status" class="form-control">
        @foreach(\App\Models\Subject::statusOptions() as $k=>$v)
            <option value="{{ $k }}" @selected(old('status', $subject->status ?? 'active')===$k)>{{ $v }}</option>
        @endforeach
    </select>
</div>
