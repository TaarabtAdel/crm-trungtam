@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <strong>Lương tháng {{ $month }}/{{ $year }}</strong>
        <div class="small text-muted">Giờ buổi hoàn thành × đơn giá/giờ</div>
    </div>
    <form method="GET" action="{{ route('admin.teachers.show', $teacher) }}" class="d-flex align-items-center flex-wrap" style="gap:.5rem">
        <input type="hidden" name="tab" value="payroll">
        <select name="payroll_month" class="form-control form-control-sm" style="width:120px" onchange="this.form.submit()">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected($month == $m)>Tháng {{ $m }}</option>
            @endfor
        </select>
        <select name="payroll_year" class="form-control form-control-sm" style="width:100px" onchange="this.form.submit()">
            @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Đơn giá</div>
            <div class="stat-value" style="font-size:1.1rem">{{ $fmt($payrollSummary['rate']) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Buổi hoàn thành</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $payrollSummary['sessions'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Tổng giờ</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $payrollSummary['hours'] }}h</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Thực nhận</div>
            <div class="stat-value text-success" style="font-size:1.15rem">{{ $fmt($payrollSummary['total']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>Lớp</th>
            <th>Giờ</th>
            <th>Số giờ</th>
            <th>Thành tiền</th>
        </tr>
        </thead>
        <tbody>
        @forelse($payrollSessions as $session)
            @php $h = $session->hours(); @endphp
            <tr>
                <td>{{ $session->session_date->format('d/m/Y') }}</td>
                <td>
                    @if($session->courseClass)
                        <a href="{{ route('admin.classes.show', ['class' => $session->courseClass, 'tab' => 'timetable']) }}">{{ $session->courseClass->name }}</a>
                    @else
                        —
                    @endif
                </td>
                <td>
                    {{ $session->start_time ? substr($session->start_time, 0, 5) : '—' }}
                    –
                    {{ $session->end_time ? substr($session->end_time, 0, 5) : '—' }}
                </td>
                <td>{{ round($h, 2) }}h</td>
                <td class="font-weight-bold">{{ $fmt($h * $payrollSummary['rate']) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Không có buổi hoàn thành trong tháng này.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
