<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Bàn làm việc')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/workbench.css') }}?v=12">
    @stack('styles')
</head>
<body class="workbench-body">
<div class="wb-stage">
    <div class="wb-bg" aria-hidden="true"></div>
    @if(session()->has('impersonator_id'))
        <div class="wb-impersonate-bar">
            <span>
                <i class="bi bi-person-bounding-box"></i>
                Đang xem với tư cách <strong>{{ auth()->user()->name }}</strong>
            </span>
            <form method="POST" action="{{ route('admin.impersonation.leave') }}" class="mb-0">
                @csrf
                <button type="submit" class="wb-impersonate-leave">
                    <i class="bi bi-arrow-counterclockwise"></i> Trở lại Admin
                </button>
            </form>
        </div>
    @endif
    @yield('content')
</div>
@include('partials.scheduler_tick')
@stack('scripts')
</body>
</html>
