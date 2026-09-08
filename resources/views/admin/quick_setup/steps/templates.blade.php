@if(! $paymentTemplate)
    <div class="alert alert-warning">
        Chưa có mẫu <code>payment_success</code>.
        Chạy seeder hoặc tạo tại
        <a href="{{ route('admin.notification-templates.index') }}">Mẫu thông báo</a>.
    </div>
@else
    <p class="small text-muted mb-3">Chỉnh nhanh mẫu xác nhận thanh toán (Email / Zalo). Zalo Template ID lấy từ ZNS đã duyệt.</p>
    <div class="form-row">
        <div class="form-group col-md-12">
            <label>Tiêu đề Email</label>
            <input name="email_subject" class="form-control" value="{{ old('email_subject', $paymentTemplate->email_subject) }}">
        </div>
        <div class="form-group col-md-12">
            <label>Nội dung Email</label>
            <textarea name="content_email" class="form-control" rows="6">{{ old('content_email', $paymentTemplate->content_email) }}</textarea>
        </div>
        <div class="form-group col-md-6">
            <label>Zalo Template ID</label>
            <input name="zalo_template_id" class="form-control" value="{{ old('zalo_template_id', $paymentTemplate->zalo_template_id) }}">
        </div>
        <div class="form-group col-md-6 d-flex align-items-end">
            <div>
                <div class="custom-control custom-checkbox custom-control-inline">
                    <input type="checkbox" class="custom-control-input" id="is_active_email" name="is_active_email" value="1" @checked(old('is_active_email', $paymentTemplate->is_active_email))>
                    <label class="custom-control-label" for="is_active_email">Bật Email</label>
                </div>
                <div class="custom-control custom-checkbox custom-control-inline">
                    <input type="checkbox" class="custom-control-input" id="is_active_zalo" name="is_active_zalo" value="1" @checked(old('is_active_zalo', $paymentTemplate->is_active_zalo))>
                    <label class="custom-control-label" for="is_active_zalo">Bật Zalo</label>
                </div>
            </div>
        </div>
    </div>
@endif
