@extends('layouts.admin')

@section('title', $lead->name)

@section('content')
@php
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'bi-info-circle'],
        'history' => ['label' => 'Lịch sử tư vấn', 'icon' => 'bi-chat-left-text', 'count' => $lead->interactions_count],
    ];
    $helpItems = [
        [
            'title' => 'Hai tab chính',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Thông tin</strong>: sửa hồ sơ lead, trạng thái, Sales phụ trách, doanh thu dự kiến (pipeline).</li>'
                .'<li><strong>Lịch sử tư vấn</strong>: ghi gọi/nhắn/gặp mặt và theo dõi các tương tác đã có.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Cập nhật & chăm sóc',
            'body' => '<p class="mb-0">Trên tab Thông tin, lưu thay đổi trạng thái (ví dụ <em>Đã chốt</em>) và gán lại Sales nếu cần. Doanh thu dự kiến chỉ phục vụ KPI Sales, không thay cho hóa đơn thực tế.</p>',
        ],
        [
            'title' => 'Sau khi chốt — học viên',
            'body' => '<p class="mb-0">Khi lead <strong>Đã chốt</strong>, bấm <strong>Đưa vào danh sách học viên</strong> để tạo hồ sơ HV (map SĐT/email khách → HV, người thân → phụ huynh). Cần quyền quản lý học viên.</p>',
        ],
        [
            'title' => 'Tương tác nhanh',
            'body' => '<p class="mb-0">Tab Lịch sử cho phép thêm tương tác gắn với lead này. Có thể quản lý lịch hẹn tổng hợp tại menu <em>Lịch hẹn / Tương tác</em>.</p>',
        ],
    ];
@endphp

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap" style="gap:.75rem">
    <div>
        <a href="{{ route('admin.leads.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Danh sách Leads</a>
        <div class="d-flex align-items-center mt-1 flex-wrap" style="gap:.5rem">
            <h4 class="mb-0 font-weight-bold">{{ $lead->name }}</h4>
            <span class="badge lead-status {{ $lead->statusBadgeClass() }}">{{ $lead->statusLabel() }}</span>
            @if($lead->student_id)
                <a href="{{ route('admin.students.show', $lead->student_id) }}" class="badge badge-success">Đã có HV #{{ $lead->student_id }}</a>
            @endif
        </div>
        <div class="text-muted small mt-1">
            @if($lead->phone)<span class="mr-2"><i class="bi bi-telephone"></i> {{ $lead->phone }}</span>@endif
            @if($lead->email)<span class="mr-2"><i class="bi bi-envelope"></i> {{ $lead->email }}</span>@endif
            @if($lead->related_name || $lead->related_phone)
                <span class="mr-2">· PH: {{ $lead->related_name ?: '—' }}@if($lead->related_phone) · {{ $lead->related_phone }}@endif</span>
            @endif
            <span class="mr-2">· {{ $lead->branch?->name }}</span>
            @if($lead->source)<span class="mr-2">· {{ $lead->source }}</span>@endif
            <span>· Sales: {{ $lead->assignedSales?->name ?? 'Chưa gán' }}</span>
        </div>
    </div>
    <div class="d-flex align-items-center flex-wrap" style="gap:.5rem">
        @canPerm('students.manage')
            @if($lead->student_id)
                <a href="{{ route('admin.students.show', $lead->student_id) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-person-check"></i> Xem học viên
                </a>
            @elseif($lead->status === 'won')
                <form method="POST" action="{{ route('admin.leads.convert-student', $lead) }}" class="d-inline"
                      onsubmit="return confirm('Tạo học viên từ lead {{ $lead->name }}?');">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-person-plus"></i> Đưa vào danh sách học viên
                    </button>
                </form>
            @endif
        @endcanPerm
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalLeadShowHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>
</div>

<ul class="nav nav-tabs class-detail-tabs mb-0">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('admin.leads.show', ['lead' => $lead, 'tab' => $key]) }}">
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
            @include('admin.crm.lead_tabs.info')
        @elseif($tab === 'history')
            @include('admin.crm.lead_tabs.history')
        @endif
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalLeadShowHelp',
    'title' => 'Hướng dẫn — Chi tiết Lead',
    'items' => $helpItems,
])

@include('partials.select2')
@endsection

@push('scripts')
<script>
(function () {
    var $el = $('.js-lead-sales-select');
    if ($el.length && typeof crmSelect2Local === 'function') {
        crmSelect2Local($el, {
            placeholder: 'Chọn Sales phụ trách',
            allowClear: true
        });
    }
})();
</script>
@endpush
