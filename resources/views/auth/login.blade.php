<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập — CRM Trung Tâm</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <h4 class="mb-1 text-center font-weight-bold">{{ \App\Models\Setting::get('center_name', 'CRM Trung Tâm') }}</h4>
        <p class="text-muted text-center mb-4">Đăng nhập quản trị</p>
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', 'admin@crm.local') }}" required autofocus>
            </div>
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="mb-0">Mật khẩu</label>
                    <a href="{{ route('password.request') }}" class="small">Quên mật khẩu?</a>
                </div>
                <input type="password" name="password" class="form-control mt-1" value="password" required>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" name="remember" id="remember">
                <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
        </form>
    </div>
</div>
</body>
</html>
