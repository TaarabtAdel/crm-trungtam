@extends('install.layout')

@section('title', 'Kiểm tra môi trường')

@section('content')
    <h1>Cài đặt CRM Trung Tâm</h1>
    <p class="muted">Wizard kiểu WordPress — phù hợp hosting cPanel (không cần SSH / artisan migrate).</p>

    <div class="steps">
        <span class="on">1. Kiểm tra</span>
        <span>2. Database</span>
        <span>3. Tài khoản admin</span>
        <span>4. Xong</span>
    </div>

    @if(session('error'))
        <div class="alert alert-err">{{ session('error') }}</div>
    @endif

    <p><strong>Yêu cầu máy chủ</strong></p>
    <ul class="check">
        <li class="{{ $checks['php'] ? 'ok' : 'bad' }}">PHP ≥ 8.2 (đang {{ PHP_VERSION }})</li>
        <li class="{{ $checks['pdo_mysql'] ? 'ok' : 'bad' }}">PDO MySQL</li>
        <li class="{{ $checks['mbstring'] ? 'ok' : 'bad' }}">mbstring</li>
        <li class="{{ $checks['openssl'] ? 'ok' : 'bad' }}">openssl</li>
        <li class="{{ $checks['tokenizer'] ? 'ok' : 'bad' }}">tokenizer</li>
        <li class="{{ $checks['json'] ? 'ok' : 'bad' }}">json</li>
        <li class="{{ $checks['storage'] ? 'ok' : 'bad' }}">Thư mục <code>storage/</code> ghi được</li>
        <li class="{{ $checks['env_writable'] ? 'ok' : 'bad' }}">Ghi được file <code>.env</code></li>
    </ul>

    <p class="muted">Trên cPanel: tạo database MySQL trống + user có quyền đầy đủ trên DB đó trước khi sang bước tiếp.
        @if(!empty($tenantDb))
            <br>Subdomain này dùng DB: <code>{{ $tenantDb }}</code>
        @endif
    </p>

    @if($ready)
        <a class="btn" href="{{ route('install.database') }}">Tiếp tục → Database</a>
    @else
        <p class="alert alert-err" style="margin-top:1rem">Sửa các mục đỏ rồi tải lại trang.</p>
    @endif
@endsection
