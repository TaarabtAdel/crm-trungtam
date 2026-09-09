@extends('install.layout')

@section('title', 'Kết nối Database')

@section('content')
    <h1>Kết nối MySQL</h1>
    <p class="muted">
        Nhập thông tin MySQL. Hệ thống tạo bảng bằng code (<code>Versions\Ver1</code>), không cần SSH migrate.
        @if($tenantMode)
            <br><strong>Multi-tenant:</strong> mỗi subdomain một DB — chỉ ghi host/user/pass dùng chung, không đổi <code>TENANT_RESOLVE</code>.
        @endif
    </p>

    <div class="steps">
        <span>1. Kiểm tra</span>
        <span class="on">2. Database</span>
        <span>3. Tài khoản admin</span>
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

    <form method="POST" action="{{ route('install.database.store') }}">
        @csrf
        <div class="row">
            <div>
                <label>Host *</label>
                <input type="text" name="host" value="{{ $defaults['host'] }}" required placeholder="localhost">
            </div>
            <div>
                <label>Port *</label>
                <input type="number" name="port" value="{{ $defaults['port'] }}" required>
            </div>
        </div>
        <label>Tên database *</label>
        <input type="text" name="database" value="{{ $defaults['database'] }}" required
               @if($tenantMode) readonly @endif>
        @if($tenantMode)
            <p class="muted" style="margin:.25rem 0 0;font-size:.85rem">Cố định theo subdomain — tạo đúng DB này trên cPanel trước.</p>
        @endif
        <label>Username *</label>
        <input type="text" name="username" value="{{ $defaults['username'] }}" required>
        <label>Password</label>
        <input type="password" name="password" value="{{ old('password') }}" autocomplete="new-password">
        <label>URL ứng dụng *</label>
        <input type="url" name="app_url" value="{{ $defaults['app_url'] }}" required>
        <label class="check-box">
            <input type="checkbox" name="app_debug" value="1" @checked(old('app_debug'))>
            Bật APP_DEBUG (chỉ môi trường local)
        </label>
        <button class="btn" type="submit">Kiểm tra &amp; tạo bảng</button>
        <a class="btn btn-ghost" href="{{ route('install.index') }}" style="margin-left:.5rem">Quay lại</a>
    </form>
@endsection
