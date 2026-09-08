@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <strong>Chấm công tháng {{ $month }}/{{ $year }}</strong>
        <div class="small text-muted">Có mặt = 1 công · Nửa ngày = 0.5 · Phép / Vắng = 0</div>
    </div>
    <div class="d-flex align-items-center flex-wrap" style="gap:.5rem">
        <form method="GET" action="{{ route('admin.users.show', $user) }}" class="d-flex align-items-center flex-wrap" style="gap:.5rem">
            <input type="hidden" name="tab" value="attendance">
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
        @canPerm('system.staff_attendances.view')
        <a href="{{ route('admin.staff-attendances.index', ['user_id' => $user->id, 'month' => sprintf('%04d-%02d', $year, $month)]) }}" class="btn btn-sm btn-primary">
            Chấm trên lịch
        </a>
        @endcanPerm
    </div>
</div>

@php
    $units = round($attendances->sum(fn ($a) => $a->dayUnits()), 2);
    $accrued = (float) $attendances->sum(fn ($a) => $a->payAmount((float) $user->daily_rate));
@endphp

<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Số dòng</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $attendances->count() }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Công</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $units }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Lương ngày</div>
            <div class="stat-value" style="font-size:1.1rem">{{ $fmt($user->daily_rate) }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Tạm tính</div>
            <div class="stat-value text-success" style="font-size:1.1rem">{{ $fmt($accrued) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Ngày</th>
            <th>Trạng thái</th>
            <th>Công</th>
            <th>Thành tiền</th>
            <th>Ghi chú</th>
        </tr>
        </thead>
        <tbody>
        @forelse($attendances as $att)
            <tr>
                <td>{{ $att->work_date->format('d/m/Y') }}</td>
                <td><span class="badge {{ $att->statusBadgeClass() }}">{{ $att->statusLabel() }}</span></td>
                <td>{{ $att->dayUnits() }}</td>
                <td class="font-weight-bold">{{ $fmt($att->payAmount((float) $user->daily_rate)) }}</td>
                <td class="text-muted small">{{ $att->note ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Chưa có chấm công tháng này.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
