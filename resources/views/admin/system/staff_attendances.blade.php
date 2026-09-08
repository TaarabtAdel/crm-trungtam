@extends('layouts.admin')

@section('title', 'Chấm công nhân viên')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $weekdayLabels = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Chấm công nhân viên</h5>
            <small class="text-muted">Chọn nhân viên → tick nhiều ngày trên lịch → gắn trạng thái hàng loạt.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Người dùng</a>
            @canPerm('finance.staff_payroll.view')
            <a href="{{ route('admin.finance.staff-payroll') }}" class="btn btn-sm btn-outline-primary">Lương NV</a>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <form method="GET" class="filter-bar mb-3">
            <select name="user_id" id="attendanceUser" class="form-control form-control-sm js-staff-user" style="min-width:260px" data-placeholder="Chọn nhân viên...">
                <option value=""></option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}"
                            data-rate="{{ (float) $u->daily_rate }}"
                            @selected((int) ($userId ?? 0) === (int) $u->id)>
                        {{ $u->name }} — {{ $fmt($u->daily_rate) }}/ngày
                    </option>
                @endforeach
            </select>
            <input type="month" name="month" class="form-control form-control-sm" style="max-width:160px" value="{{ $month }}">
            <button class="btn btn-sm btn-outline-secondary">Xem lịch</button>
        </form>

        @if(!$selectedUser)
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar2-check" style="font-size:2.5rem;opacity:.4"></i>
                <div class="mt-2">Chọn nhân viên để bắt đầu chấm công.</div>
            </div>
        @else
            <div class="d-flex flex-wrap align-items-center mb-3" style="gap:.75rem">
                <div>
                    <strong>{{ $selectedUser->name }}</strong>
                    <span class="text-muted small ml-1">Lương ngày: {{ $fmt($selectedUser->daily_rate) }}</span>
                    <a href="{{ route('admin.users.show', ['user' => $selectedUser, 'tab' => 'info']) }}" class="small ml-2">Hồ sơ →</a>
                </div>
                <div class="ml-auto d-flex flex-wrap" style="gap:.5rem">
                    <span class="badge badge-success">Có mặt {{ $summary['present'] }}</span>
                    <span class="badge badge-info">Nửa ngày {{ $summary['half'] }}</span>
                    <span class="badge badge-warning">Phép {{ $summary['leave'] }}</span>
                    <span class="badge badge-secondary">Vắng {{ $summary['absent'] }}</span>
                    <span class="badge badge-primary">Công {{ $summary['units'] }} · {{ $fmt($summary['accrued']) }}</span>
                </div>
            </div>

            <div class="staff-att-toolbar border rounded p-2 mb-3 d-flex flex-wrap align-items-center" style="gap:.5rem">
                <span class="small text-muted mr-1">Đã chọn: <strong id="selectedCount">0</strong> ngày</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSelectWeekdays">Chọn T2–T6</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearSelection">Bỏ chọn</button>
                <div class="ml-auto d-flex flex-wrap align-items-center" style="gap:.35rem">
                    <select id="bulkStatus" class="form-control form-control-sm" style="max-width:140px">
                        @foreach(\App\Models\StaffAttendance::statusOptions() as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="bulkNote" class="form-control form-control-sm" style="max-width:180px" placeholder="Ghi chú (tuỳ chọn)">
                    @canPerm('system.staff_attendances.manage')
                    <button type="button" class="btn btn-sm btn-primary" id="btnMark">Chấm công</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearAtt">Xóa chấm</button>
                    @endcanPerm
                </div>
            </div>

            <div class="staff-att-weekdays d-flex mb-1 px-1" style="gap:.35rem">
                @foreach($weekdayLabels as $w)
                    <div class="flex-fill text-center small text-muted font-weight-bold">{{ $w }}</div>
                @endforeach
            </div>

            <div class="staff-att-grid" id="attendanceGrid">
                @php
                    $pad = $calendarDays[0]['weekday'] ?? 0; // CN=0
                @endphp
                @for($i = 0; $i < $pad; $i++)
                    <div class="staff-att-cell is-empty"></div>
                @endfor
                @foreach($calendarDays as $day)
                    @php $att = $day['attendance']; @endphp
                    <button type="button"
                            class="staff-att-cell
                                {{ $day['is_weekend'] ? 'is-weekend' : '' }}
                                {{ $att ? 'has-att status-'.$att->status : '' }}
                                {{ $day['is_future'] ? 'is-future' : '' }}"
                            data-date="{{ $day['date'] }}"
                            title="{{ $day['date'] }}{{ $att ? ' · '.$att->statusLabel() : '' }}">
                        <span class="day-num">{{ $day['day'] }}</span>
                        @if($att)
                            <span class="day-status">{{ $att->statusLabel() }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <form id="formMark" method="POST" action="{{ route('admin.staff-attendances.store') }}" class="d-none">
                @csrf
                <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="status" id="formStatus">
                <input type="hidden" name="note" id="formNote">
                <div id="formDates"></div>
            </form>
            <form id="formClear" method="POST" action="{{ route('admin.staff-attendances.destroy') }}" class="d-none">
                @csrf @method('DELETE')
                <input type="hidden" name="user_id" value="{{ $selectedUser->id }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <div id="formClearDates"></div>
            </form>
        @endif
    </div>
</div>

@include('partials.select2')
@endsection

@push('styles')
<style>
.staff-att-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: .35rem;
}
.staff-att-cell {
    min-height: 72px;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    background: #fff;
    padding: .4rem .45rem;
    text-align: left;
    cursor: pointer;
    transition: .15s ease;
}
.staff-att-cell.is-empty { border: 0; background: transparent; pointer-events: none; }
.staff-att-cell:hover:not(.is-empty) { border-color: #93c5fd; box-shadow: 0 0 0 2px rgba(59,130,246,.15); }
.staff-att-cell.is-selected { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 2px rgba(37,99,235,.25); }
.staff-att-cell.is-weekend { background: #f8fafc; }
.staff-att-cell.is-future { opacity: .55; }
.staff-att-cell .day-num { display: block; font-weight: 700; font-size: .95rem; color: #0f172a; }
.staff-att-cell .day-status { display: block; font-size: .68rem; margin-top: .2rem; color: #64748b; }
.staff-att-cell.status-present { background: #f0fdf4; border-color: #86efac; }
.staff-att-cell.status-half { background: #eff6ff; border-color: #93c5fd; }
.staff-att-cell.status-leave { background: #fffbeb; border-color: #fcd34d; }
.staff-att-cell.status-absent { background: #f1f5f9; border-color: #cbd5e1; }
.staff-att-cell.status-present .day-status { color: #15803d; font-weight: 600; }
.staff-att-cell.status-half .day-status { color: #1d4ed8; font-weight: 600; }
.staff-att-cell.status-leave .day-status { color: #b45309; font-weight: 600; }
@media (max-width: 767.98px) {
    .staff-att-cell { min-height: 56px; padding: .3rem; }
    .staff-att-cell .day-status { font-size: .6rem; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    crmSelect2Local($('.js-staff-user'), { placeholder: 'Chọn nhân viên...', allowClear: true });
    $('.js-staff-user').on('change', function () {
        if (this.form) this.form.submit();
    });

    var selected = {};
    function syncCount() {
        $('#selectedCount').text(Object.keys(selected).length);
    }
    function fillDates($box) {
        $box.empty();
        Object.keys(selected).forEach(function (d) {
            $box.append($('<input>', { type: 'hidden', name: 'dates[]', value: d }));
        });
    }

    $(document).on('click', '.staff-att-cell[data-date]', function () {
        var date = $(this).data('date');
        if (selected[date]) {
            delete selected[date];
            $(this).removeClass('is-selected');
        } else {
            selected[date] = true;
            $(this).addClass('is-selected');
        }
        syncCount();
    });

    $('#btnClearSelection').on('click', function () {
        selected = {};
        $('.staff-att-cell.is-selected').removeClass('is-selected');
        syncCount();
    });

    $('#btnSelectWeekdays').on('click', function () {
        $('.staff-att-cell[data-date]').each(function () {
            var d = new Date($(this).data('date') + 'T00:00:00');
            var wd = d.getDay();
            if (wd >= 1 && wd <= 5) {
                selected[$(this).data('date')] = true;
                $(this).addClass('is-selected');
            }
        });
        syncCount();
    });

    $('#btnMark').on('click', function () {
        if (!Object.keys(selected).length) {
            alert('Chọn ít nhất một ngày trên lịch.');
            return;
        }
        $('#formStatus').val($('#bulkStatus').val());
        $('#formNote').val($('#bulkNote').val());
        fillDates($('#formDates'));
        $('#formMark').submit();
    });

    $('#btnClearAtt').on('click', function () {
        if (!Object.keys(selected).length) {
            alert('Chọn ngày cần xóa chấm công.');
            return;
        }
        if (!confirm('Xóa chấm công các ngày đã chọn?')) return;
        fillDates($('#formClearDates'));
        $('#formClear').submit();
    });
})();
</script>
@endpush
