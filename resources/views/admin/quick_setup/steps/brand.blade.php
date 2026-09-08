<div class="form-row">
    <div class="form-group col-md-8">
        <label>Tên trung tâm <span class="text-danger">*</span></label>
        <input name="center_name" class="form-control" value="{{ old('center_name', $settings['center_name']) }}" required>
    </div>
    <div class="form-group col-md-4">
        <label>Logo text (sidebar) <span class="text-danger">*</span></label>
        <input name="logo_text" class="form-control" value="{{ old('logo_text', $settings['logo_text']) }}" maxlength="50" required>
    </div>
</div>
<p class="small text-muted mb-0">Tên hiện trên PDF hóa đơn; logo text hiện sidebar.</p>
