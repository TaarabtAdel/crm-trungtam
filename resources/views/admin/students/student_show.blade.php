@extends('layouts.admin')

@section('title', $student->name)

@section('content')
@php
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'bi-info-circle'],
        'classes' => ['label' => 'Lớp học', 'icon' => 'bi-journal-bookmark', 'count' => $student->classes_count],
        'tuition' => ['label' => 'Lịch sử học phí', 'icon' => 'bi-cash-coin', 'count' => $student->invoices_count],
        'attendance' => ['label' => 'Điểm danh', 'icon' => 'bi-clipboard-check', 'count' => $student->attendances_count],
    ];
    $helpItems = [
        [
            'title' => 'Các tab trên trang Chi tiết',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Thông tin</strong>: sửa hồ sơ HV và người thân.</li>'
                .'<li><strong>Lớp học</strong>: xem lớp đang theo học (ghi danh từ trang Lớp).</li>'
                .'<li><strong>Lịch sử học phí</strong>: các hóa đơn liên quan HV.</li>'
                .'<li><strong>Điểm danh</strong>: lịch sử có mặt / vắng theo buổi.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Ghi danh vào lớp',
            'body' => '<p class="mb-0">Thêm học viên vào lớp tại trang <strong>Lớp học</strong> → tab Học viên của lớp đó. Trên tab này chỉ xem / gỡ khỏi lớp.</p>',
        ],
        [
            'title' => 'Liên kết nhanh',
            'body' => '<p class="mb-0">Nút <em>Hóa đơn</em> mở danh sách HĐ lọc theo tên HV. <em>Điểm danh lớp</em> mở trang điểm danh theo lớp/ngày để ghi nhận buổi học.</p>',
        ],
    ];
@endphp

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap" style="gap:.75rem">
    <div>
        <a href="{{ route('admin.students.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Danh sách học viên</a>
        <div class="d-flex align-items-center mt-1 flex-wrap" style="gap:.5rem">
            <h4 class="mb-0 font-weight-bold">{{ $student->name }}</h4>
            <span class="badge lead-status {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span>
        </div>
        <div class="text-muted small mt-1">
            <span class="mr-2">{{ $student->branch?->name }}</span>
            @if($student->gender)<span class="mr-2">· {{ $student->gender }}</span>@endif
            @if($student->dob)<span class="mr-2">· {{ $student->dob->format('d/m/Y') }}</span>@endif
            @if($student->phone)<span class="mr-2">· <i class="bi bi-telephone"></i> {{ $student->phone }}</span>
            @elseif($student->parent_phone)<span class="mr-2">· <i class="bi bi-telephone"></i> {{ $student->parent_phone }}</span>@endif
            @if($student->email)<span class="mr-2">· <i class="bi bi-envelope"></i> {{ $student->email }}</span>@endif
        </div>
    </div>
    <div class="d-flex" style="gap:.5rem">
        @canPerm('finance.invoices.view')
        <a href="{{ route('admin.invoices.index', ['q' => $student->name]) }}" class="btn btn-sm btn-outline-secondary">Hóa đơn</a>
        @endcanPerm
        @canPerm('attendances.view')
        <a href="{{ route('admin.attendances.index') }}" class="btn btn-sm btn-outline-secondary">Điểm danh lớp</a>
        @endcanPerm
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalStudentShowHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>
</div>

<ul class="nav nav-tabs class-detail-tabs mb-0">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('admin.students.show', ['student' => $student, 'tab' => $key]) }}">
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
            @include('admin.students.student_tabs.info')
        @elseif($tab === 'classes')
            @include('admin.students.student_tabs.classes')
        @elseif($tab === 'tuition')
            @include('admin.students.student_tabs.tuition')
        @elseif($tab === 'attendance')
            @include('admin.students.student_tabs.attendance')
        @endif
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalStudentShowHelp',
    'title' => 'Hướng dẫn — Chi tiết học viên',
    'items' => $helpItems,
])
@endsection
