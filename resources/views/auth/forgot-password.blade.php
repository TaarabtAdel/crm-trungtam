<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quên mật khẩu — CRM Trung Tâm</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
@php $retryAfter = (int) ($retryAfter ?? session('retry_after', 0)); @endphp
<div class="login-page">
    <div class="login-card">
        <h4 class="mb-1 text-center font-weight-bold">{{ \App\Models\Setting::get('center_name', 'CRM Trung Tâm') }}</h4>
        <p class="text-muted text-center mb-4">Quên mật khẩu</p>

        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <p class="small text-muted">Nhập email tài khoản. Nếu email tồn tại và đang hoạt động, hệ thống sẽ gửi link đặt lại mật khẩu. Mỗi email chỉ gửi lại được sau <strong>1 phút</strong>.</p>

        <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
            @csrf
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="forgotEmail" class="form-control" value="{{ old('email') }}" required autofocus @if($retryAfter > 0) readonly @endif>
            </div>
            <button type="submit" class="btn btn-primary btn-block" id="forgotSubmitBtn" @if($retryAfter > 0) disabled @endif>
                <span id="forgotSubmitLabel">Gửi link đặt lại</span>
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="small">← Quay lại đăng nhập</a>
        </div>
    </div>
</div>
@if($retryAfter > 0)
<script>
(function () {
    var remain = {{ $retryAfter }};
    var btn = document.getElementById('forgotSubmitBtn');
    var label = document.getElementById('forgotSubmitLabel');
    var email = document.getElementById('forgotEmail');
    function tick() {
        if (remain <= 0) {
            btn.disabled = false;
            if (email) email.readOnly = false;
            label.textContent = 'Gửi link đặt lại';
            return;
        }
        label.textContent = 'Gửi lại sau ' + remain + 's';
        remain -= 1;
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
@endif
</body>
</html>
