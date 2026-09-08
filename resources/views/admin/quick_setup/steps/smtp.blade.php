<p class="small text-muted mb-3">Tuỳ chọn. Dùng Gmail: App password + smtp.gmail.com / 587 / TLS.</p>
<div class="custom-control custom-switch mb-3">
    <input type="checkbox" class="custom-control-input" id="smtp_enabled" name="smtp_enabled" value="1" @checked(old('smtp_enabled', $settings['smtp_enabled']) == '1')>
    <label class="custom-control-label font-weight-bold" for="smtp_enabled">Bật SMTP</label>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>Host</label>
        <input name="smtp_host" class="form-control" value="{{ old('smtp_host', $settings['smtp_host'] ?: 'smtp.gmail.com') }}">
    </div>
    <div class="form-group col-md-3">
        <label>Port</label>
        <input type="number" name="smtp_port" class="form-control" value="{{ old('smtp_port', $settings['smtp_port'] ?: 587) }}">
    </div>
    <div class="form-group col-md-3">
        <label>Mã hóa</label>
        <select name="smtp_encryption" class="form-control">
            <option value="tls" @selected(old('smtp_encryption', $settings['smtp_encryption']) === 'tls')>TLS</option>
            <option value="ssl" @selected(old('smtp_encryption', $settings['smtp_encryption']) === 'ssl')>SSL</option>
            <option value="none" @selected(old('smtp_encryption', $settings['smtp_encryption']) === 'none')>Không</option>
        </select>
    </div>
    <div class="form-group col-md-6">
        <label>Username</label>
        <input name="smtp_username" class="form-control" value="{{ old('smtp_username', $settings['smtp_username']) }}">
    </div>
    <div class="form-group col-md-6">
        <label>Password / App password</label>
        <input type="password" name="smtp_password" class="form-control" placeholder="{{ $settings['smtp_password_set'] ? '•••• (giữ nguyên nếu trống)' : '' }}">
    </div>
    <div class="form-group col-md-6">
        <label>From email</label>
        <input type="email" name="smtp_from_address" class="form-control" value="{{ old('smtp_from_address', $settings['smtp_from_address']) }}">
    </div>
    <div class="form-group col-md-6 mb-0">
        <label>From name</label>
        <input name="smtp_from_name" class="form-control" value="{{ old('smtp_from_name', $settings['smtp_from_name']) }}">
    </div>
</div>
<p class="small mb-0 mt-2">
    <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener">Hướng dẫn App password (Google)</a>
</p>
