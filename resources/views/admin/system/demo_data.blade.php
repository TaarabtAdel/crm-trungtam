@extends('layouts.admin')

@section('title', 'Khởi tạo data demo')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Khi nào dùng data demo?',
            'body' => '<p class="mb-0">Dùng trên môi trường <strong>thử nghiệm</strong> để xem nhanh toàn bộ module (lớp, HV, leads, hóa đơn, điểm danh, lương). <em>Không chạy trên dữ liệu thật đang vận hành.</em></p>',
        ],
        [
            'title' => 'Thao tác này làm gì?',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Xóa</strong> dữ liệu nghiệp vụ hiện có (môn, GV, lớp, HV, điểm danh, leads, tương tác, HĐ).</li>'
                .'<li><strong>Tạo lại</strong> bộ demo mẫu (chi nhánh, sales, lớp, HV, leads, HĐ…).</li>'
                .'<li>Tài khoản admin/sales được giữ hoặc cập nhật, không mất hết user hệ thống.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Cách chạy an toàn',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Xem lại số liệu hiện tại trên trang.</li>'
                .'<li>Bấm <strong>Chạy khởi tạo data demo</strong> và xác nhận.</li>'
                .'<li>Vào Dashboard / từng module để kiểm tra dữ liệu mẫu.</li>'
                .'</ol>',
        ],
    ];
@endphp
<div class="page-card" style="max-width:780px">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Khởi tạo data demo</h5>
            <small class="text-muted">Tạo dữ liệu mẫu để chạy thử toàn bộ module CRM</small>
        </div>
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalDemoDataHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>
    <div class="card-body-custom">
        <div class="alert alert-warning">
            <strong>Lưu ý:</strong> Thao tác này sẽ <u>xóa dữ liệu nghiệp vụ hiện có</u>
            (môn học, giáo viên, lớp, học viên, điểm danh, leads, tương tác, hóa đơn)
            rồi tạo lại bộ demo. Tài khoản admin/sales sẽ được giữ/cập nhật.
        </div>

        <h6 class="font-weight-bold mb-2">Dữ liệu hiện tại</h6>
        <div class="row mb-4">
            @foreach([
                'Giáo viên' => $summary['teachers'],
                'Học viên' => $summary['students'],
                'Lớp học' => $summary['classes'],
                'Leads' => $summary['leads'],
                'Hóa đơn' => $summary['invoices'],
            ] as $label => $value)
            <div class="col-6 col-md-4 mb-2">
                <div class="stat-card py-2">
                    <div class="stat-label mb-0">{{ $label }}</div>
                    <div class="stat-value" style="font-size:1.25rem">{{ $value }}</div>
                </div>
            </div>
            @endforeach
        </div>

        <h6 class="font-weight-bold mb-2">Bộ demo sẽ gồm</h6>
        <ul class="mb-4">
            <li>2 chi nhánh, 2 sales, 3 giáo viên, 5 môn học</li>
            <li>3 lớp học + 6 học viên (đã gắn lớp)</li>
            <li>Buổi học completed + điểm danh (để xem bảng lương)</li>
            <li>6 leads + vài lịch hẹn/tương tác</li>
            <li>Vài hóa đơn đã thu / chưa thu</li>
        </ul>

        <form method="POST" action="{{ route('admin.demo-data.run') }}" onsubmit="return confirm('Xóa dữ liệu nghiệp vụ và tạo lại demo?');">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-database-add"></i> Chạy khởi tạo data demo
            </button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-light ml-2">Về dashboard</a>
        </form>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalDemoDataHelp',
    'title' => 'Hướng dẫn — Data demo',
    'items' => $helpItems,
])
@endsection
