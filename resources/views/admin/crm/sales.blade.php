@extends('layouts.admin')

@section('title', 'Dashboard Sales')

@section('content')
<div class="mb-3">
    <h4 class="font-weight-bold mb-0">Dashboard Sales</h4>
    <small class="text-muted">Hiệu suất tuyển sinh hôm nay</small>
</div>
<div class="row">
    @foreach([
        ['Khách mới hôm nay', $kpi['new_leads']],
        ['Cuộc gọi hôm nay', $kpi['calls']],
        ['Tỷ lệ chuyển đổi', $kpi['conversion'].'%'],
        ['Doanh thu dự kiến', number_format($kpi['expected_revenue'],0,',','.').' đ'],
    ] as [$label,$value])
    <div class="col-md-3 mb-3">
        <div class="stat-card">
            <div class="stat-label">{{ $label }}</div>
            <div class="stat-value" style="font-size:1.5rem">{{ $value }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="page-card">
    <div class="card-header-custom"><strong>Hiệu suất nhân viên Sales</strong></div>
    <div class="card-body-custom">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Họ tên nhân viên</th><th>Chi nhánh</th><th>Lead được phân</th><th>Cuộc gọi</th><th>Đã chốt</th><th>Doanh thu</th><th>Tỷ lệ (%)</th></tr></thead>
                <tbody>
                @forelse($performance as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['branch'] ?? '—' }}</td>
                        <td>{{ $row['assigned'] }}</td>
                        <td>{{ $row['calls'] }}</td>
                        <td>{{ $row['won'] }}</td>
                        <td>@vnd($row['revenue'])</td>
                        <td>{{ $row['rate'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Không có dữ liệu hiệu suất nhân viên.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
