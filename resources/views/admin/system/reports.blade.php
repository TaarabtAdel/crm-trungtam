@extends('layouts.admin')

@section('title', 'Báo cáo')

@section('content')
<div class="mb-3">
    <h4 class="font-weight-bold mb-0">Báo cáo tổng hợp</h4>
    <small class="text-muted">Thống kê nhanh tuyển sinh, đào tạo và tài chính</small>
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
@endsection
