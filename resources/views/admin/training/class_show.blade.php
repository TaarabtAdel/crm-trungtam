@extends('layouts.admin')

@section('title', $class->name)

@section('content')
@php
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'bi-info-circle'],
        'students' => ['label' => 'Học viên', 'icon' => 'bi-people', 'count' => $class->students_count],
        'timetable' => ['label' => 'Thời khóa biểu', 'icon' => 'bi-calendar3', 'count' => $class->sessions_count],
        'tuition' => ['label' => 'Thu học phí', 'icon' => 'bi-cash-coin'],
    ];
@endphp

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap" style="gap:.75rem">
    <div>
        <a href="{{ route('admin.classes.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Danh sách lớp học</a>
        <h4 class="mb-1 mt-1 font-weight-bold">{{ $class->name }}</h4>
        <div class="text-muted small">
            @if($class->code)<span class="mr-2">Mã: {{ $class->code }}</span>@endif
            <span class="mr-2">{{ $class->branch?->name }}</span>
            @if($class->subject)<span class="mr-2">· {{ $class->subject->name }}</span>@endif
            @if($class->teacher)<span class="mr-2">· GV: {{ $class->teacher->name }}</span>@endif
            <span class="badge badge-info">{{ $class->status }}</span>
        </div>
    </div>
    <div>
        <a href="{{ route('admin.attendances.index', ['class_id' => $class->id]) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-clipboard-check"></i> Điểm danh
        </a>
    </div>
</div>

<ul class="nav nav-tabs class-detail-tabs mb-0">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('admin.classes.show', ['class' => $class, 'tab' => $key] + ($key === 'timetable' ? ['month' => $month ?? now()->format('Y-m')] : []) + ($key === 'tuition' ? ['billing_month' => $billingMonth ?? now()->format('Y-m')] : [])) }}">
                <i class="bi {{ $meta['icon'] }} mr-1"></i>{{ $meta['label'] }}
                @if(!empty($meta['count']))
                    <span class="badge badge-light border ml-1">{{ $meta['count'] }}</span>
                @endif
            </a>
        </li>
    @endforeach
</ul>

<div class="page-card class-detail-panel border-top-0" style="border-top-left-radius:0;border-top-right-radius:0">
    <div class="card-body-custom">
        @if($tab === 'info')
            @include('admin.training.class_tabs.info')
        @elseif($tab === 'students')
            @include('admin.training.class_tabs.students')
        @elseif($tab === 'timetable')
            @include('admin.training.class_tabs.timetable')
        @elseif($tab === 'tuition')
            @include('admin.training.class_tabs.tuition')
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).on('change', '.js-tuition-type', function () {
    var label = $(this).closest('form').find('.js-tuition-fee-label');
    label.text(this.value === 'per_session' ? 'Học phí / buổi' : 'Học phí / tháng');
});
</script>
@endpush
