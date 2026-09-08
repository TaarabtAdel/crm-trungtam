@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <strong>Lương tháng {{ $month }}/{{ $year }}</strong>
        <div class="small text-muted">Công chấm × lương ngày · chi lương tại menu Lương NV</div>
    </div>
    <form method="GET" action="{{ route('admin.users.show', $user) }}" class="d-flex align-items-center flex-wrap" style="gap:.5rem">
        <input type="hidden" name="tab" value="payroll">
        <select name="month" class="form-control form-control-sm" style="width:120px" onchange="this.form.submit()">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($month == $m)>Tháng {{ $m }}</option>
            @endfor
        </select>
        <select name="year" class="form-control form-control-sm" style="width:100px" onchange="this.form.submit()">
            @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

@if($payrollSummary)
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Lương ngày</div>
            <div class="stat-value" style="font-size:1.1rem">{{ $fmt($payrollSummary['rate']) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Công</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $payrollSummary['days'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Phải trả</div>
            <div class="stat-value" style="font-size:1.1rem">{{ $fmt($payrollSummary['accrued']) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Còn lại</div>
            <div class="stat-value {{ $payrollSummary['remaining'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size:1.1rem">
                {{ $fmt($payrollSummary['remaining']) }}
            </div>
        </div>
    </div>
</div>

<div class="alert alert-light border small mb-3">
    Đã lập phiếu / đã chi: <strong>{{ $fmt($payrollSummary['paid']) }}</strong>
    · Tháng billing: <code>{{ $payrollSummary['billing_month'] }}</code>
    @canPerm('finance.staff_payroll.view')
        · <a href="{{ route('admin.finance.staff-payroll', ['month' => $month, 'year' => $year]) }}">Mở báo cáo lương NV</a>
    @endcanPerm
</div>
@endif

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>Trạng thái</th>
            <th>Công</th>
            <th>Thành tiền</th>
        </tr>
        </thead>
        <tbody>
        @forelse($attendances as $att)
            <tr>
                <td>{{ $att->work_date->format('d/m/Y') }}</td>
                <td><span class="badge {{ $att->statusBadgeClass() }}">{{ $att->statusLabel() }}</span></td>
                <td>{{ $att->dayUnits() }}</td>
                <td class="font-weight-bold">{{ $fmt($att->payAmount((float) ($payrollSummary['rate'] ?? $user->daily_rate))) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Không có công tính lương trong tháng này.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
