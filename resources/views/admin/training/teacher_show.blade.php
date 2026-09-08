@extends('layouts.admin')

@section('title', $teacher->name)

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'bi-info-circle'],
        'classes' => ['label' => 'Lớp đang dạy', 'icon' => 'bi-journal-bookmark', 'count' => $teacher->classes_count],
        'payroll' => ['label' => 'Lương GV', 'icon' => 'bi-cash-coin'],
        'schedule' => ['label' => 'Lịch dạy', 'icon' => 'bi-calendar3', 'count' => $teacher->sessions_count],
    ];
    $helpItems = [
        [
            'title' => 'Các tab chi tiết',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Thông tin</strong>: hồ sơ, đơn giá/giờ, trạng thái.</li>'
                .'<li><strong>Lớp đang dạy</strong>: lớp phụ trách và lớp có buổi dạy thay.</li>'
                .'<li><strong>Lương GV</strong>: tổng giờ buổi hoàn thành × đơn giá theo tháng.</li>'
                .'<li><strong>Lịch dạy</strong>: toàn bộ buổi gắn với GV (lọc theo tháng).</li>'
                .'</ul>',
        ],
        [
            'title' => 'Cách tính lương',
            'body' => '<p class="mb-0">Chỉ tính buổi trạng thái <em>Hoàn thành</em> trên TKB, theo <strong>GV gắn trên từng buổi</strong> (kể cả dạy thay). Sửa trạng thái buổi ở chi tiết lớp → Thời khóa biểu.</p>',
        ],
    ];
@endphp

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap" style="gap:.75rem">
    <div>
        <a href="{{ route('admin.teachers.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Danh sách giáo viên</a>
        <div class="d-flex align-items-center mt-1 flex-wrap" style="gap:.5rem">
            <div class="teacher-avatar">{{ strtoupper(mb_substr($teacher->name, 0, 1)) }}</div>
            <h4 class="mb-0 font-weight-bold">{{ $teacher->name }}</h4>
            <span class="badge lead-status {{ $teacher->statusBadgeClass() }}">{{ $teacher->statusLabel() }}</span>
        </div>
        <div class="text-muted small mt-1">
            <span class="mr-2">{{ $teacher->branch?->name }}</span>
            @if($teacher->specialty)<span class="mr-2">· {{ $teacher->specialty }}</span>@endif
            @if($teacher->phone)<span class="mr-2">· <i class="bi bi-telephone"></i> {{ $teacher->phone }}</span>@endif
            @if($teacher->email)<span class="mr-2">· <i class="bi bi-envelope"></i> {{ $teacher->email }}</span>@endif
        </div>
    </div>
    <div class="d-flex" style="gap:.5rem">
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalTeacherShowHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>
</div>

<ul class="nav nav-tabs class-detail-tabs mb-0">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('admin.teachers.show', ['teacher' => $teacher, 'tab' => $key] + ($key === 'payroll' ? ['payroll_month' => $month, 'payroll_year' => $year] : []) + ($key === 'schedule' && $scheduleMonth !== '' ? ['month' => $scheduleMonth] : [])) }}">
                <i class="bi {{ $meta['icon'] }} mr-1"></i>{{ $meta['label'] }}
                @isset($meta['count'])
                    <span class="badge badge-light border ml-1">{{ $meta['count'] }}</span>
                @endisset
            </a>
        </li>
    @endforeach
</ul>

<div class="page-card class-detail-panel border-top-0" style="border-top-left-radius:0;border-top-right-radius:0">
    <div class="card-body-custom">
        @if($tab === 'info')
            @include('admin.training.teacher_tabs.info')
        @elseif($tab === 'classes')
            @include('admin.training.teacher_tabs.classes')
        @elseif($tab === 'payroll')
            @include('admin.training.teacher_tabs.payroll')
        @elseif($tab === 'schedule')
            @include('admin.training.teacher_tabs.schedule')
        @endif
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalTeacherShowHelp',
    'title' => 'Hướng dẫn — Chi tiết giáo viên',
    'items' => $helpItems,
])
@endsection
