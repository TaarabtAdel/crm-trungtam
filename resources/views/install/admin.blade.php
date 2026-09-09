@extends('install.layout')

@section('title', 'Tài khoản Admin')

@section('content')
    <h1>Tạo tài khoản Super Admin</h1>
    <p class="muted">Tài khoản đầu tiên quản trị toàn hệ thống.</p>

    <div class="steps">
        <span>1. Kiểm tra</span>
        <span>2. Database</span>
        <span class="on">3. Tài khoản admin</span>
        <span>4. Xong</span>
    </div>

    @if(session('error'))
        <div class="alert alert-err">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-ok">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-err">
            <ul style="margin:0;padding-left:1.1rem">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('install.admin.store') }}">
        @csrf
        <label>Tên trung tâm *</label>
        <input type="text" name="center_name" value="{{ old('center_name', 'CRM Trung Tâm') }}" required>
        <label>Họ tên admin *</label>
        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required>
        <label>Email đăng nhập *</label>
        <input type="email" name="admin_email" value="{{ old('admin_email') }}" required>
        <label>Mật khẩu * (tối thiểu 8 ký tự)</label>
        <input type="password" name="admin_password" required autocomplete="new-password">
        <label>Nhập lại mật khẩu *</label>
        <input type="password" name="admin_password_confirmation" required autocomplete="new-password">
        <button class="btn" type="submit">Hoàn tất cài đặt</button>
    </form>
@endsection
