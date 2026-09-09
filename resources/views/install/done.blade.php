@extends('install.layout')

@section('title', 'Hoàn tất')

@section('content')
    <h1>Cài đặt thành công</h1>
    <p class="muted">Hệ thống đã sẵn sàng. Đăng nhập bằng tài khoản Super Admin vừa tạo.</p>

    <div class="steps">
        <span>1. Kiểm tra</span>
        <span>2. Database</span>
        <span>3. Tài khoản admin</span>
        <span class="on">4. Xong</span>
    </div>

    <div class="alert alert-ok">
        Trung tâm này đã có bảng dữ liệu và tài khoản admin trên database hiện tại.
        Các subdomain khác (DB riêng) vẫn có thể chạy <code>/install</code> lần đầu riêng.
    </div>

    <p>Tiếp theo gợi ý:</p>
    <ul>
        <li>Đăng nhập → <strong>Cài đặt nhanh</strong> / Hướng dẫn sử dụng</li>
        <li>Đổi mật khẩu admin nếu cần</li>
        <li>Cấu hình SMTP / Zalo trong Cài đặt khi sẵn sàng</li>
    </ul>

    <a class="btn" href="{{ route('login') }}">Đăng nhập ngay</a>
@endsection
