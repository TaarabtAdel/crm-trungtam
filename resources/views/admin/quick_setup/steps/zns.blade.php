<p class="small text-muted mb-3">Tuỳ chọn. Cấu hình ZNS để gửi tin thanh toán học phí.</p>
<div class="custom-control custom-switch mb-2">
    <input type="checkbox" class="custom-control-input" id="zalo_notify_enabled" name="zalo_notify_enabled" value="1" @checked(old('zalo_notify_enabled', $settings['zalo_notify_enabled']) == '1')>
    <label class="custom-control-label" for="zalo_notify_enabled">Bật kênh Zalo</label>
</div>
<div class="custom-control custom-checkbox mb-3">
    <input type="checkbox" class="custom-control-input" id="zalo_notify_payment" name="zalo_notify_payment" value="1" @checked(old('zalo_notify_payment', $settings['zalo_notify_payment']) == '1')>
    <label class="custom-control-label" for="zalo_notify_payment">Thông báo thanh toán học phí thành công</label>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>ZNS App ID</label>
        <input name="zalo_zns_app_id" class="form-control" value="{{ old('zalo_zns_app_id', $settings['zalo_zns_app_id']) }}">
    </div>
    <div class="form-group col-md-6">
        <label>Secret Key</label>
        <input type="password" name="zalo_zns_secret_key" class="form-control" placeholder="{{ $settings['zalo_zns_secret_set'] ? '•••• (giữ nguyên nếu trống)' : '' }}">
    </div>
    <div class="form-group col-md-6">
        <label>Access Token</label>
        <input type="password" name="zalo_zns_access_token" class="form-control" placeholder="{{ $settings['zalo_zns_access_token_set'] ? '•••• (giữ nguyên nếu trống)' : '' }}">
    </div>
    <div class="form-group col-md-6 mb-0">
        <label>Refresh Token</label>
        <input type="password" name="zalo_zns_refresh_token" class="form-control" placeholder="{{ $settings['zalo_zns_refresh_token_set'] ? '•••• (giữ nguyên nếu trống)' : '' }}">
    </div>
</div>
