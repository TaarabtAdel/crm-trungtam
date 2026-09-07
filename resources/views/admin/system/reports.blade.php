@extends('layouts.admin')

@section('title', 'Báo cáo')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Báo cáo tổng hợp là gì?',
            'body' => '<p class="mb-0">Các chỉ số nhanh về tuyển sinh (leads, chốt, tỷ lệ chuyển đổi), tài chính (DT dự kiến / đã thu / chưa thu) và đào tạo (học viên, lớp, giáo viên).</p>',
        ],
        [
            'title' => 'Cách đọc số liệu',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Leads / Đã chốt / Tỷ lệ chuyển đổi</strong>: hiệu quả CRM.</li>'
                .'<li><strong>DT dự kiến vs Đã thu / Chưa thu</strong>: theo dõi doanh thu và công nợ.</li>'
                .'<li><strong>Học viên / Đang học / Lớp / GV</strong>: quy mô vận hành hiện tại.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Khi cần chi tiết',
            'body' => '<p class="mb-0">Mở module tương ứng (CRM, Hóa đơn, Học viên, Lớp…) để lọc theo thời gian hoặc xuất dữ liệu chi tiết. Trang này chỉ là snapshot tổng quan hệ thống.</p>',
        ],
    ];
@endphp
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <h4 class="font-weight-bold mb-0">Báo cáo tổng hợp</h4>
        <small class="text-muted">Thống kê nhanh tuyển sinh, đào tạo và tài chính</small>
    </div>
    <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalSystemReportsHelp">
        <i class="bi bi-question-circle"></i> Hướng dẫn
    </button>
</div>
<div class="row">
    @foreach([
        ['Leads', $report['leads']],
        ['Đã chốt', $report['won']],
        ['Tỷ lệ chuyển đổi', $report['conversion'].'%'],
        ['DT dự kiến', number_format($report['expected_revenue'],0,',','.').' đ'],
        ['Đã thu', number_format($report['paid_revenue'],0,',','.').' đ'],
        ['Chưa thu', number_format($report['unpaid_revenue'],0,',','.').' đ'],
        ['Học viên', $report['students']],
        ['Đang học', $report['studying']],
        ['Lớp học', $report['classes']],
        ['Giáo viên', $report['teachers']],
    ] as [$label, $value])
    <div class="col-md-4 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $value }}</div>
        </div>
    </div>
    @endforeach
</div>

@include('partials.page_help', [
    'modalId' => 'modalSystemReportsHelp',
    'title' => 'Hướng dẫn — Báo cáo hệ thống',
    'items' => $helpItems,
])
@endsection
