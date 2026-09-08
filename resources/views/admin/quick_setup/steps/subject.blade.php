<div class="form-row">
    <div class="form-group col-md-8">
        <label>Tên môn <span class="text-danger">*</span></label>
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
    <div class="form-group col-md-12 mb-0">
        <label>Mô tả</label>
        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
    </div>
</div>
@if($subjects->isNotEmpty())
    <p class="small text-success mt-2 mb-0"><i class="bi bi-check-circle"></i> Đã có {{ $subjects->count() }} môn — có thể chuyển bước.</p>
@endif
