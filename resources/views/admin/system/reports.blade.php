@extends('layouts.admin')

@section('title', 'Báo cáo')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Báo cáo tổng hợp',
            'body' => '<p class="mb-0">KPI tuyển sinh, đào tạo, chuyên cần tháng hiện tại, retention đơn giản và aging công nợ theo hạn thanh toán.</p>',
        ],
        [
            'title' => 'Chuyên cần & retention',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>% chuyên cần</strong>: (có mặt + muộn) / tổng dòng điểm danh trong tháng.</li>'
                .'<li><strong>Retention</strong>: tỷ lệ HV đang học đã có từ trước tháng này (proxy giữ chân).</li>'
                .'</ul>',
        ],
    ];
@endphp
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <h4 class="font-weight-bold mb-0">Báo cáo tổng hợp</h4>
        <small class="text-muted">Snapshot · chuyên cần tháng {{ $report['period_label'] }} · aging công nợ</small>
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
        ['DT dự kiến', $fmt($report['expected_revenue'])],
        ['Đã thu', $fmt($report['paid_revenue'])],
        ['Công nợ còn lại', $fmt($report['unpaid_revenue'])],
        ['Học viên', $report['students']],
        ['Đang học', $report['studying']],
        ['Lớp học', $report['classes']],
        ['Giáo viên', $report['teachers']],
        ['% chuyên cần ('.$report['period_label'].')', $report['attendance_rate'].'%'],
        ['Retention (proxy)', $report['retention'].'%'],
    ] as [$label, $value])
    <div class="col-md-4 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $value }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom"><strong>Aging công nợ</strong></div>
            <div class="card-body-custom">
                <table class="table table-sm mb-0">
                    <tbody>
                    @foreach([
                        'current' => 'Chưa đến hạn',
                        '1_30' => 'Quá hạn 1–30 ngày',
                        '31_60' => 'Quá hạn 31–60 ngày',
                        '61_90' => 'Quá hạn 61–90 ngày',
                        '90_plus' => 'Quá hạn > 90 ngày',
                    ] as $key => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="text-right font-weight-bold {{ ($report['debt_buckets'][$key] ?? 0) > 0 && $key !== 'current' ? 'text-danger' : '' }}">
                                {{ $fmt($report['debt_buckets'][$key] ?? 0) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="small text-muted mt-2">
                    Điểm danh tháng: {{ $report['attendance_present'] }}/{{ $report['attendance_total'] }} dòng có mặt/muộn.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom"><strong>Công nợ theo chi nhánh</strong></div>
            <div class="card-body-custom">
                <ul class="list-unstyled mb-0">
                    @forelse($report['debt_by_branch'] as $row)
                        <li class="d-flex justify-content-between py-1 border-bottom">
                            <span>{{ $row->branch?->name ?: '—' }}</span>
                            <span class="font-weight-bold">{{ $fmt($row->total) }}</span>
                        </li>
                    @empty
                        <li class="text-muted">Không có công nợ.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalSystemReportsHelp',
    'title' => 'Hướng dẫn — Báo cáo hệ thống',
    'items' => $helpItems,
])
@endsection
