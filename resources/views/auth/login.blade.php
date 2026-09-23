@extends('layouts.workbench')

@php
    $brand = $brand ?? (string) \App\Models\Setting::get('center_name', \App\Models\Setting::get('logo_text', config('app.name')));
    $logoText = $logoText ?? (string) \App\Models\Setting::get('logo_text', 'CRM');
@endphp

@section('title', 'Đăng nhập — '.$brand)

@section('content')
<div class="wb-auth">
    <div class="wb-auth-hero">
        <div class="wb-auth-brand" aria-label="{{ $brand }}">
            <span class="wb-brand-mark wb-auth-mark">{{ mb_strtoupper(mb_substr($logoText, 0, 2)) }}</span>
            <h1 class="wb-auth-title">{{ $brand }}</h1>
            <p class="wb-auth-sub">Đăng nhập để vào bàn làm việc</p>
        </div>
    </div>

    <form method="POST" action="{{ route('login') }}" class="wb-auth-panel">
        @csrf
        <h2 class="wb-auth-panel-title">Đăng nhập</h2>

        @if(session('status'))
            <div class="wb-auth-alert is-ok">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="wb-auth-alert is-err">{{ $errors->first() }}</div>
        @endif

        <label class="wb-auth-field">
            <span>Email hoặc số điện thoại</span>
            <input type="text" name="login" value="{{ old('login') }}" required autofocus
                   autocomplete="username" inputmode="text"
                   placeholder="email@trungtam.vn hoặc 0901234567">
        </label>

        <label class="wb-auth-field">
            <span class="wb-auth-field-row">
                <span>Mật khẩu</span>
                <a href="{{ route('password.request') }}" class="wb-auth-link">Quên mật khẩu?</a>
            </span>
            <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </label>

        <label class="wb-auth-check">
            <input type="checkbox" name="remember" id="remember" value="1">
            <span>Ghi nhớ đăng nhập</span>
        </label>

        <button type="submit" class="wb-auth-submit">
            <i class="bi bi-box-arrow-in-right"></i>
            Đăng nhập
        </button>
    </form>
</div>
@endsection
