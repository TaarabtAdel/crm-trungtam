@extends('layouts.admin')

@section('title', $user->name)

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'bi-info-circle'],
        'attendance' => ['label' => 'Chấm công', 'icon' => 'bi-calendar2-check'],
        'payroll' => ['label' => 'Lương NV', 'icon' => 'bi-cash-coin'],
    ];
@endphp

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap" style="gap:.75rem">
    <div>
        <a href="{{ route('admin.users.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Danh sách người dùng</a>
        <div class="d-flex align-items-center mt-1 flex-wrap" style="gap:.5rem">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center font-weight-bold"
                 style="width:44px;height:44px">{{ $user->initials() }}</div>
            <h4 class="mb-0 font-weight-bold">{{ $user->name }}</h4>
            @if($user->is_active)
                <span class="badge badge-success">Active</span>
            @else
                <span class="badge badge-secondary">Khóa</span>
            @endif
        </div>
        <div class="text-muted small mt-1">
            <span class="mr-2">{{ $user->branch?->name ?: 'Toàn hệ thống' }}</span>
            <span class="mr-2">· {{ $user->email }}</span>
            @if($user->phone)<span class="mr-2">· <i class="bi bi-telephone"></i> {{ $user->phone }}</span>@endif
            <span class="mr-2">· Lương ngày: <strong>{{ $fmt($user->daily_rate) }}</strong></span>
        </div>
    </div>
    <div class="d-flex flex-wrap" style="gap:.5rem">
        @canPerm('system.staff_attendances.view')
        <a href="{{ route('admin.staff-attendances.index', ['user_id' => $user->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-calendar2-check"></i> Chấm công lịch
        </a>
        @endcanPerm
        @canPerm('finance.staff_payroll.view')
        <a href="{{ route('admin.finance.staff-payroll', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm btn-outline-secondary">Lương NV</a>
        @endcanPerm
    </div>
</div>

<ul class="nav nav-tabs class-detail-tabs mb-0">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('admin.users.show', ['user' => $user, 'tab' => $key, 'month' => $month, 'year' => $year]) }}">
                <i class="bi {{ $meta['icon'] }} mr-1"></i>{{ $meta['label'] }}
            </a>
        </li>
    @endforeach
</ul>

<div class="page-card class-detail-panel border-top-0" style="border-top-left-radius:0;border-top-right-radius:0">
    <div class="card-body-custom">
        @if($tab === 'info')
            @include('admin.system.user_tabs.info')
        @elseif($tab === 'attendance')
            @include('admin.system.user_tabs.attendance')
        @elseif($tab === 'payroll')
            @include('admin.system.user_tabs.payroll')
        @endif
    </div>
</div>
@endsection
