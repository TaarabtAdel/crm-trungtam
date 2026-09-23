@extends('layouts.workbench')

@php
    $brand = (string) \App\Models\Setting::get('center_name', \App\Models\Setting::get('logo_text', config('app.name')));
    $logoText = (string) \App\Models\Setting::get('logo_text', 'CRM');
    $retryAfter = (int) ($retryAfter ?? session('retry_after', 0));
@endphp

@section('title', 'Quên mật khẩu — '.$brand)

@section('content')
<div class="wb-auth">
    <div class="wb-auth-hero">
        <div class="wb-auth-brand" aria-label="{{ $brand }}">
            <span class="wb-brand-mark wb-auth-mark">{{ mb_strtoupper(mb_substr($logoText, 0, 2)) }}</span>
            <h1 class="wb-auth-title">{{ $brand }}</h1>
            <p class="wb-auth-sub">Khôi phục mật khẩu</p>
        </div>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="wb-auth-panel" id="forgotPasswordForm">
        @csrf
        <h2 class="wb-auth-panel-title">Quên mật khẩu</h2>
        <p class="wb-auth-hint">Nhập email tài khoản. Nếu email tồn tại và đang hoạt động, hệ thống sẽ gửi link đặt lại. Mỗi email chỉ gửi lại được sau <strong>1 phút</strong>.</p>

        @if(session('status'))
            <div class="wb-auth-alert is-ok">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="wb-auth-alert is-err">{{ $errors->first() }}</div>
        @endif

        <label class="wb-auth-field">
            <span>Email</span>
            <input type="email" name="email" id="forgotEmail" value="{{ old('email') }}" required autofocus autocomplete="username" @if($retryAfter > 0) readonly @endif>
        </label>

        <button type="submit" class="wb-auth-submit" id="forgotSubmitBtn" @if($retryAfter > 0) disabled @endif>
            <i class="bi bi-envelope"></i>
            <span id="forgotSubmitLabel">Gửi link đặt lại</span>
        </button>

        <a href="{{ route('login') }}" class="wb-auth-back"><i class="bi bi-arrow-left"></i> Quay lại đăng nhập</a>
    </form>
</div>
@endsection

@if($retryAfter > 0)
@push('scripts')
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
@endpush
@endif
