<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt lại mật khẩu — CRM Trung Tâm</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <h4 class="mb-1 text-center font-weight-bold">{{ \App\Models\Setting::get('center_name', 'CRM Trung Tâm') }}</h4>
        <p class="text-muted text-center mb-4">Đặt lại mật khẩu</p>

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required autofocus>
            </div>
            <div class="form-group">
                <label>Mật khẩu mới</label>
                <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                <small class="text-muted">Tối thiểu 8 ký tự</small>
            </div>
            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Lưu mật khẩu mới</button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="small">← Quay lại đăng nhập</a>
        </div>
    </div>
</div>
</body>
</html>
