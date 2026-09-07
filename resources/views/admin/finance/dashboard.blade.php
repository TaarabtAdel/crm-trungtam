@extends('layouts.admin')

@section('title', 'Dashboard Tài chính')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Các chỉ số (KPI) nghĩa là gì?',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Thu tháng này</strong>: tổng tiền đã thu từ thanh toán trong tháng hiện tại.</li>'
                .'<li><strong>Chi tháng này</strong>: tổng khoản chi đã thực chi trong tháng.</li>'
                .'<li><strong>Lãi/Lỗ tháng</strong>: Thu − Chi trong tháng.</li>'
                .'<li><strong>Tổng công nợ</strong>: số tiền học viên còn nợ trên các hóa đơn chưa thu đủ.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Công nợ ưu tiên',
            'body' => '<p class="mb-0">Bảng dưới liệt kê các hóa đơn còn nợ cần theo dõi sớm. Bấm <em>Chi tiết</em> để vào hóa đơn và ghi nhận thu tiền. Xem đầy đủ tại <a href="'.route('admin.debts.index').'">Công nợ</a>.</p>',
        ],
        [
            'title' => 'Liên kết nhanh',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><a href="'.route('admin.invoices.index').'">Hóa đơn</a> — tạo HĐ, theo dõi trạng thái thu.</li>'
                .'<li><a href="'.route('admin.debts.index').'">Công nợ</a> — lọc quá hạn / sắp đến hạn.</li>'
                .'<li><a href="'.route('admin.finance.reports').'">Báo cáo</a> — xem thu chi theo kỳ, xuất Excel/PDF.</li>'
                .'</ul>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Dashboard Tài chính</h5>
            <small class="text-muted">Thu chi tháng này và công nợ cần theo dõi.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @canPerm('finance.invoices.manage')
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-primary">+ Hóa đơn</a>
            @endcanPerm
            @canPerm('finance.reports.view')
            <a href="{{ route('admin.finance.reports') }}" class="btn btn-sm btn-outline-secondary">Báo cáo</a>
            @endcanPerm
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalFinanceDashboardHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
</div>

<div class="dash-kpis row">
    @foreach([
        ['label' => 'Thu tháng này', 'value' => $fmt($kpi['revenue_month']), 'icon' => 'cash-stack', 'tone' => 'green'],
        ['label' => 'Chi tháng này', 'value' => $fmt($kpi['expense_month']), 'icon' => 'wallet2', 'tone' => 'orange'],
        ['label' => 'Lãi/Lỗ tháng', 'value' => $fmt($kpi['profit_month']), 'icon' => 'graph-up', 'tone' => 'indigo'],
        ['label' => 'Tổng công nợ', 'value' => $fmt($kpi['debt_total']), 'icon' => 'exclamation-circle', 'tone' => 'red'],
    ] as $card)
        <div class="col-6 col-md-6 col-xl-3 mb-3">
            <div class="dash-kpi dash-kpi-{{ $card['tone'] }}">
                <div class="dash-kpi-icon"><i class="bi bi-{{ $card['icon'] }}"></i></div>
                <div>
                    <div class="dash-kpi-value">{{ $card['value'] }}</div>
                    <div class="dash-kpi-label">{{ $card['label'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="page-card">
    <div class="card-header-custom">
        <strong>Công nợ ưu tiên</strong>
        <a href="{{ route('admin.debts.index') }}" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
    </div>
    <div class="card-body-custom">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Mã HĐ</th><th>Học viên</th><th>Còn nợ</th><th>Hạn</th><th>Quá hạn</th><th></th></tr></thead>
                <tbody>
                @forelse($debts as $inv)
                    <tr>
                        <td class="font-weight-bold">{{ $inv->code }}</td>
                        <td>{{ $inv->student?->name }}</td>
                        <td>{{ $fmt($inv->remaining_amount) }}</td>
                        <td>{{ optional($inv->due_date)->format('d/m/Y') ?: '—' }}</td>
                        <td>
                            @if($inv->isOverdue())
                                <span class="badge lead-status lead-status-lost">{{ $inv->daysOverdue() }} ngày</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-right"><a href="{{ route('admin.invoices.show', $inv) }}" class="btn btn-sm btn-primary">Chi tiết</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không có công nợ.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalFinanceDashboardHelp',
    'title' => 'Hướng dẫn — Dashboard Tài chính',
    'items' => $helpItems,
])
@endsection
