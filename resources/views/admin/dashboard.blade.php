@extends('layouts.admin')

@section('title', 'Bảng điều khiển')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $pctClass = fn ($p) => $p >= 0 ? 'text-success' : 'text-danger';
    $pctText = fn ($p) => ($p >= 0 ? '+' : '').$p.'%';
    $helpItems = [
        [
            'title' => 'Trang này dùng để làm gì?',
            'body' => '<p class="mb-0">Tổng quan nhanh toàn trung tâm: số lượng nhân sự/học viên/lớp, doanh thu–chi phí, và khối lượng công việc giáo viên theo tháng đang chọn.</p>',
        ],
        [
            'title' => 'Cách đọc các chỉ số',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>KPI trên cùng</strong>: tổng hiện tại (GV, HV, lớp, buổi học, doanh thu).</li>'
                .'<li><strong>Cập nhật doanh thu</strong>: biểu đồ theo ngày trong tháng; chọn tháng bằng bộ lọc.</li>'
                .'<li><strong>Tổng quan tài chính</strong>: so sánh doanh thu vs chi phí (lương GV) theo năm.</li>'
                .'<li>% xanh/đỏ là so với kỳ trước (tháng / năm / tuần).</li>'
                .'</ul>',
        ],
        [
            'title' => 'Bộ lọc tháng / năm',
            'body' => '<p class="mb-0">Đổi <em>tháng</em> hoặc <em>năm</em> trên từng khối biểu đồ để xem dữ liệu tương ứng. Một số khối dùng chung tham số URL nên đổi ở một chỗ có thể ảnh hưởng khối khác.</p>',
        ],
        [
            'title' => 'Khi cần chi tiết hơn',
            'body' => '<p class="mb-0">Vào module tương ứng: <strong>Học viên</strong>, <strong>Lớp học</strong>, <strong>Tài chính</strong> (hóa đơn/công nợ) để xem và thao tác chi tiết — Dashboard chỉ để theo dõi tổng quan.</p>',
        ],
    ];
@endphp

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <h5 class="mb-0 font-weight-bold">Bảng điều khiển</h5>
        <small class="text-muted">Tổng quan KPI vận hành và tài chính trung tâm.</small>
    </div>
    <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalDashboardHelp">
        <i class="bi bi-question-circle"></i> Hướng dẫn
    </button>
</div>

<div class="dash-kpis row">
    @foreach([
        ['label' => 'Nhân sự / Giáo viên', 'value' => $stats['teachers'], 'icon' => 'people', 'tone' => 'blue'],
        ['label' => 'Học viên', 'value' => $stats['students'], 'icon' => 'mortarboard', 'tone' => 'green'],
        ['label' => 'Lớp học', 'value' => $stats['classes'], 'icon' => 'journal-bookmark', 'tone' => 'orange'],
        ['label' => 'Buổi học', 'value' => $stats['sessions'], 'icon' => 'calendar3', 'tone' => 'indigo'],
        ['label' => 'Tổng doanh thu', 'value' => $fmt($stats['revenue']), 'icon' => 'cash-stack', 'tone' => 'red'],
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
                <strong>Cập nhật doanh thu</strong>
                <form method="GET" class="mb-0">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <select name="month" class="form-control form-control-sm" style="width:auto;min-width:170px" onchange="this.form.submit()">
                        @foreach($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}" @selected($month===$opt['value'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="card-body-custom">
                <div class="row align-items-center">
                    <div class="col-lg-8 mb-3 mb-lg-0">
                        <canvas id="chartDailyRevenue" height="140"></canvas>
                    </div>
                    <div class="col-lg-4">
                        <div class="dash-side-metric dash-side-income mb-3">
                            <div class="small text-muted">Thu nhập tháng này</div>
                            <div class="h5 mb-1 font-weight-bold">{{ $fmt($incomeThisMonth) }}</div>
                            <div class="small {{ $pctClass($compare['income_month_pct']) }}">{{ $pctText($compare['income_month_pct']) }} so với tháng trước</div>
                        </div>
                        <div class="dash-side-metric dash-side-cost">
                            <div class="small text-muted">Chi phí tháng này <small>(lương GV)</small></div>
                            <div class="h5 mb-1 font-weight-bold">{{ $fmt($costThisMonth) }}</div>
                            <div class="small {{ $pctClass($compare['cost_month_pct']) }}">{{ $pctText($compare['cost_month_pct']) }} so với tháng trước</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom"><strong>Tổng kết năm</strong></div>
            <div class="card-body-custom">
                <div class="dash-donut-wrap">
                    <canvas id="chartYearDonut"></canvas>
                    <div class="dash-donut-center">
                        <div class="font-weight-bold">{{ $fmt($yearRevenue) }}</div>
                        <div class="small {{ $pctClass($compare['year_pct']) }}">{{ $pctText($compare['year_pct']) }} so với năm ngoái</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-5 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom">
                <strong>Tổng quan tài chính</strong>
                <form method="GET" class="mb-0">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <select name="year" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()">
                        @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}" @selected($year==$y)>Năm {{ $y }}</option>
                        @endfor
                    </select>
                </form>
            </div>
            <div class="card-body-custom">
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="dash-mini-box">
                            <div class="small text-muted">Tổng doanh thu</div>
                            <div class="font-weight-bold">{{ $fmt($yearRevenue) }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="dash-mini-box">
                            <div class="small text-muted">Tổng chi phí</div>
                            <div class="font-weight-bold">{{ $fmt($yearCost) }}</div>
                        </div>
                    </div>
                </div>
                <canvas id="chartFinanceYear" height="140"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-3 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom"><strong>Tổng quan đào tạo</strong></div>
            <div class="card-body-custom">
                <div class="dash-train-row mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>Học viên</span>
                        <strong>{{ $studentsNow }}</strong>
                    </div>
                    <div class="progress dash-progress mb-1"><div class="progress-bar bg-primary" style="width:{{ min(100, $studentsNow > 0 ? 70 : 5) }}%"></div></div>
                    <div class="small {{ $pctClass($compare['students_pct']) }}">{{ $pctText($compare['students_pct']) }} so với trước</div>
                </div>
                <div class="dash-train-row">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>Lớp học</span>
                        <strong>{{ $classesNow }}</strong>
                    </div>
                    <div class="progress dash-progress mb-1"><div class="progress-bar bg-warning" style="width:{{ min(100, $classesNow > 0 ? 55 : 5) }}%"></div></div>
                    <div class="small {{ $pctClass($compare['classes_pct']) }}">{{ $pctText($compare['classes_pct']) }} so với trước</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom"><strong>Thu nhập hàng tuần</strong></div>
            <div class="card-body-custom">
                <div class="mb-2">
                    <div class="h4 mb-0 font-weight-bold">{{ $fmt($weekIncome) }}</div>
                    <div class="small {{ $pctClass($compare['week_pct']) }}">{{ $pctText($compare['week_pct']) }} so với tuần trước</div>
                </div>
                <canvas id="chartWeekly" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom">
                <strong>Tổng quan công việc nhân sự</strong>
                <span class="badge badge-light border">Theo thù lao tháng đang chọn</span>
            </div>
            <div class="card-body-custom p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                        <tr>
                            <th>Giáo viên</th>
                            <th>Buổi HT</th>
                            <th>Giờ dạy</th>
                            <th>Thù lao</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($teacherWorkload as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['sessions'] }}</td>
                                <td>{{ $row['hours'] }}h</td>
                                <td class="font-weight-bold">{{ $fmt($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Không có dữ liệu hiển thị</td></tr>
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
                <strong>Khóa học nổi bật</strong>
                <span class="badge badge-primary">Top doanh thu</span>
            </div>
            <div class="card-body-custom">
                @forelse($topClasses as $i => $row)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center" style="gap:.65rem">
                            <div class="dash-rank">{{ $i + 1 }}</div>
                            <div class="font-weight-bold">{{ $row['name'] }}</div>
                        </div>
                        <div class="text-primary font-weight-bold">{{ $fmt($row['total']) }}</div>
                    </div>
                @empty
                    <div class="text-muted text-center py-4">Chưa có dữ liệu khóa học</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalDashboardHelp',
    'title' => 'Hướng dẫn — Bảng điều khiển',
    'items' => $helpItems,
])
@endsection

@push('scripts')
<script>
const money = (v) => new Intl.NumberFormat('vi-VN').format(v) + ' đ';
const commonOpts = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { callback: (v) => v >= 1000 ? (v/1000)+'k' : v } },
        x: { grid: { display: false } }
    }
};

new Chart(document.getElementById('chartDailyRevenue'), {
    type: 'line',
    data: {
        labels: {!! json_encode($dailyLabels) !!},
        datasets: [{
            data: {!! json_encode($dailyRevenue) !!},
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,.12)',
            fill: true,
            tension: .35,
            pointRadius: 0,
            borderWidth: 2
        }]
    },
    options: commonOpts
});

new Chart(document.getElementById('chartYearDonut'), {
    type: 'doughnut',
    data: {
        labels: ['Doanh thu', 'Chi phí lương'],
        datasets: [{
            data: [{{ (float) $yearRevenue }}, {{ (float) $yearCost }}],
            backgroundColor: ['#1e293b', '#94a3b8'],
            borderWidth: 0,
            cutout: '72%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});

new Chart(document.getElementById('chartFinanceYear'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($monthLabels) !!},
        datasets: [
            {
                label: 'Doanh thu',
                data: {!! json_encode($monthlyRevenue) !!},
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                barPercentage: .55
            },
            {
                label: 'Chi phí',
                data: {!! json_encode($monthlyCost) !!},
                backgroundColor: '#fb7185',
                borderRadius: 4,
                barPercentage: .55
            }
        ]
    },
    options: {
        ...commonOpts,
        plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10 } } }
    }
});

new Chart(document.getElementById('chartWeekly'), {
    type: 'line',
    data: {
        labels: {!! json_encode($weeklyLabels) !!},
        datasets: [{
            data: {!! json_encode($weeklyData) !!},
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,.12)',
            fill: true,
            tension: .35,
            pointRadius: 2,
            borderWidth: 2
        }]
    },
    options: commonOpts
});
</script>
@endpush
