@extends('layouts.admin')

@section('title', 'Dashboard Sales')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'KPI trên dashboard',
            'body' => '<p class="mb-0">Các ô chỉ số (khách mới, tương tác, đã chốt, tỷ lệ chuyển đổi, doanh thu pipeline) phản ánh hoạt động tuyển sinh theo phạm vi của bạn / chi nhánh đang chọn.</p>',
        ],
        [
            'title' => 'Bảng hiệu suất Sales',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Xếp hạng theo doanh thu chốt — chọn <em>Tháng này</em> hoặc <em>Tất cả thời gian</em>.</li>'
                .'<li>Cột Được phân / Tương tác / Đã chốt / Tỷ lệ giúp so sánh năng suất từng nhân viên.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Lịch hẹn sắp tới',
            'body' => '<p class="mb-0">Cột bên phải liệt kê follow-up gần nhất. Dùng nút <em>Danh sách Leads</em> / <em>Lịch hẹn</em> để đi sâu xử lý từng khách.</p>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Dashboard Sales</h5>
            <small class="text-muted">
                @if(auth()->user()->isSales())
                    Theo dõi hiệu suất tuyển sinh của bạn.
                @else
                    Tổng quan hiệu suất tuyển sinh theo chi nhánh đang chọn.
                @endif
            </small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalSalesHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
            @canPerm('crm.leads.view')
            <a href="{{ route('admin.leads.index') }}" class="btn btn-sm btn-outline-secondary">Danh sách Leads</a>
            @endcanPerm
            @canPerm('crm.interactions.view')
            <a href="{{ route('admin.interactions.index') }}" class="btn btn-sm btn-outline-secondary">Lịch hẹn</a>
            @endcanPerm
        </div>
    </div>
</div>

<div class="dash-kpis row">
    @foreach([
        ['label' => 'Khách mới hôm nay', 'value' => $kpi['new_leads'], 'icon' => 'person-plus', 'tone' => 'blue'],
        ['label' => 'Tương tác hôm nay', 'value' => $kpi['appointments'], 'icon' => 'telephone', 'tone' => 'orange'],
        ['label' => 'Đã chốt hôm nay', 'value' => $kpi['won_today'], 'icon' => 'trophy', 'tone' => 'green'],
        ['label' => 'Tỷ lệ chuyển đổi', 'value' => $kpi['conversion'].'%', 'icon' => 'graph-up', 'tone' => 'indigo'],
        ['label' => 'Doanh thu pipeline', 'value' => $fmt($kpi['expected_revenue']), 'icon' => 'cash-stack', 'tone' => 'red'],
    ] as $card)
        <div class="col-6 col-md-4 col-xl mb-3">
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

<div class="row">
    <div class="col-xl-8 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom">
                <div>
                    <strong>Hiệu suất nhân viên Sales</strong>
                    <div class="small text-muted">Xếp theo doanh thu chốt {{ $period === 'month' ? 'trong tháng này' : 'toàn bộ' }}</div>
                </div>
                <form method="GET" class="mb-0">
                    <select name="period" class="form-control form-control-sm" style="width:auto;min-width:140px" onchange="this.form.submit()">
                        <option value="all" @selected($period==='all')>Tất cả thời gian</option>
                        <option value="month" @selected($period==='month')>Tháng này</option>
                    </select>
                </form>
            </div>
            <div class="card-body-custom">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 sales-table">
                        <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Được phân</th>
                            <th>Tương tác</th>
                            <th>Đã chốt</th>
                            <th>Doanh thu</th>
                            <th>Tỷ lệ</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($performance as $row)
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $row['name'] }}</div>
                                    @if($row['branch'])
                                        <div class="mt-1">
                                            <span class="lead-meta-chip">{{ $row['branch'] }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $row['assigned'] }}</div>
                                    <div class="small text-muted">lead</div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $row['calls'] }}</div>
                                    <div class="small text-muted">lượt</div>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-success">{{ $row['won'] }}</div>
                                    <div class="small text-muted">chốt</div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $fmt($row['revenue']) }}</div>
                                </td>
                                <td>
                                    <span class="badge lead-status {{ $row['rate'] >= 30 ? 'lead-status-won' : ($row['rate'] >= 10 ? 'lead-status-interested' : 'lead-status-new') }}">
                                        {{ $row['rate'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Không có dữ liệu hiệu suất nhân viên.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom">
                <div>
                    <strong>Lịch sắp tới</strong>
                    <div class="small text-muted">Tương tác trạng thái sắp diễn ra</div>
                </div>
                @canPerm('crm.interactions.view')
                <a href="{{ route('admin.interactions.index', ['status' => 'upcoming']) }}" class="btn btn-sm btn-outline-secondary">Xem tất cả</a>
                @endcanPerm
            </div>
            <div class="card-body-custom">
                @forelse($upcoming as $item)
                    <div class="d-flex align-items-start mb-3" style="gap:.65rem">
                        <div class="interaction-avatar">{{ strtoupper(mb_substr($item->lead?->name ?? '?', 0, 1)) }}</div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start" style="gap:.5rem">
                                <div>
                                    @if($item->lead)
                                        <a href="{{ route('admin.leads.show', ['lead' => $item->lead, 'tab' => 'history']) }}" class="font-weight-bold text-dark">{{ $item->lead->name }}</a>
                                    @else
                                        <span class="text-muted">Lead đã xóa</span>
                                    @endif
                                    <div class="mt-1">
                                        <span class="lead-meta-chip"><i class="bi bi-{{ $item->typeIcon() }}"></i> {{ $item->type }}</span>
                                    </div>
                                </div>
                                <div class="text-right text-nowrap small text-muted">
                                    @if($item->scheduled_at)
                                        <div>{{ $item->scheduled_at->format('d/m') }}</div>
                                        <div>{{ $item->scheduled_at->format('H:i') }}</div>
                                    @endif
                                </div>
                            </div>
                            @if($item->notes)
                                <div class="small text-muted mt-1">{{ \Illuminate\Support\Str::limit($item->notes, 60) }}</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Không có lịch hẹn sắp tới.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalSalesHelp',
    'title' => 'Hướng dẫn — Dashboard Sales',
    'items' => $helpItems,
])
@endsection
