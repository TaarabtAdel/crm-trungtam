@extends('layouts.admin')

@section('title', 'Dashboard Tài chính')

@section('content')
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Dashboard Tài chính</h5>
            <small class="text-muted">Thu chi tháng này và công nợ cần theo dõi.</small>
        </div>
        <div class="d-flex" style="gap:.5rem">
            @canPerm('finance.invoices.manage')
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-primary">+ Hóa đơn</a>
            @endcanPerm
            @canPerm('finance.reports.view')
            <a href="{{ route('admin.finance.reports') }}" class="btn btn-sm btn-outline-secondary">Báo cáo</a>
            @endcanPerm
        </div>
    </div>
</div>

<div class="dash-kpis row">
    @foreach([
        ['label' => 'Thu tháng này', 'value' => $fmt($kpi['revenue_month']), 'icon' => 'cash-stack', 'tone' => 'green'],
        ['label' => 'Chi tháng này', 'value' => $fmt($kpi['expense_month']), 'icon' => 'wallet2', 'tone' => 'orange'],
        ['label' => 'Lãi/Lỗ tháng', 'value' => $fmt($kpi['profit_month']), 'icon' => 'graph-up', 'tone' => 'indigo'],
        ['label' => 'Tổng công nợ', 'value' => $fmt($kpi['debt_total']), 'icon' => 'exclamation-circle', 'tone' => 'red'],
        ['label' => 'HĐ quá hạn', 'value' => $kpi['overdue_count'], 'icon' => 'clock-history', 'tone' => 'pink'],
        ['label' => 'HĐ còn nợ', 'value' => $kpi['unpaid_invoices'], 'icon' => 'receipt', 'tone' => 'blue'],
    ] as $card)
        <div class="col-6 col-md-4 col-xl-2 mb-3">
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
@endsection
