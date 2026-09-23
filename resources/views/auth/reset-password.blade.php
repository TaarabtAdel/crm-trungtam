@extends('layouts.workbench')

@php
    $brand = (string) \App\Models\Setting::get('center_name', \App\Models\Setting::get('logo_text', config('app.name')));
    $logoText = (string) \App\Models\Setting::get('logo_text', 'CRM');
@endphp

@section('title', 'Đặt lại mật khẩu — '.$brand)

@section('content')
<div class="wb-auth">
    <div class="wb-auth-hero">
        <div class="wb-auth-brand" aria-label="{{ $brand }}">
            <span class="wb-brand-mark wb-auth-mark">{{ mb_strtoupper(mb_substr($logoText, 0, 2)) }}</span>
            <h1 class="wb-auth-title">{{ $brand }}</h1>
            <p class="wb-auth-sub">Tạo mật khẩu mới</p>
        </div>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="wb-auth-panel">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <h2 class="wb-auth-panel-title">Đặt lại mật khẩu</h2>

        @if($errors->any())
            <div class="wb-auth-alert is-err">{{ $errors->first() }}</div>
        @endif

        <label class="wb-auth-field">
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username">
        </label>

        <label class="wb-auth-field">
            <span>Mật khẩu mới</span>
            <input type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Tối thiểu 8 ký tự">
        </label>

        <label class="wb-auth-field">
            <span>Xác nhận mật khẩu</span>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </label>

        <button type="submit" class="wb-auth-submit">
            <i class="bi bi-shield-check"></i>
            Lưu mật khẩu mới
        </button>

        <a href="{{ route('login') }}" class="wb-auth-back"><i class="bi bi-arrow-left"></i> Quay lại đăng nhập</a>
    </form>
</div>
@endsection
