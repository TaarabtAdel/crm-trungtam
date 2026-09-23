@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
@endphp

<form method="GET" class="filter-bar mb-3">
    <input type="hidden" name="mode" value="day">
    <label class="small text-muted mb-0 mr-1">Ngày</label>
    <input type="date" name="date" class="form-control form-control-sm" style="max-width:170px" value="{{ $workDateStr }}">
    <button class="btn btn-sm btn-outline-secondary">Xem</button>
    <div class="ml-auto d-flex flex-wrap" style="gap:.4rem">
        <span class="badge badge-light border">Đã chấm {{ $summary['marked'] }}/{{ $summary['total'] }}</span>
        <span class="badge badge-success">Có mặt {{ $summary['present'] }}</span>
        <span class="badge badge-info">Nửa ngày {{ $summary['half'] }}</span>
        <span class="badge badge-warning">Phép {{ $summary['leave'] }}</span>
        <span class="badge badge-secondary">Vắng {{ $summary['absent'] }}</span>
    </div>
</form>

@if($users->isEmpty())
    <div class="text-center text-muted py-5">Chưa có nhân viên active để chấm công.</div>
@else
    <div class="staff-att-toolbar border rounded p-2 mb-3 d-flex flex-wrap align-items-center" style="gap:.5rem">
        <span class="small text-muted mr-1">Đã chọn: <strong id="daySelectedCount">0</strong> NV</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDaySelectAll">Chọn tất cả</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDaySelectUnmarked">Chọn chưa chấm</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDayClear">Bỏ chọn</button>
        <div class="ml-auto d-flex flex-wrap align-items-center" style="gap:.35rem">
            <select id="dayBulkStatus" class="form-control form-control-sm" style="max-width:140px">
                @foreach(\App\Models\StaffAttendance::statusOptions() as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <input type="text" id="dayBulkNote" class="form-control form-control-sm" style="max-width:180px" placeholder="Ghi chú (tuỳ chọn)">
            @canPerm('system.staff_attendances.manage')
            <button type="button" class="btn btn-sm btn-primary" id="btnDayMark">Chấm công</button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnDayClearAtt">Xóa chấm</button>
            @endcanPerm
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover staff-att-day-table mb-0">
            <thead>
            <tr>
                <th style="width:40px">
                    <input type="checkbox" id="dayCheckAll" title="Chọn tất cả">
                </th>
                <th>Nhân viên</th>
                <th class="text-right" style="width:140px">Lương ngày</th>
                <th style="width:140px">Trạng thái</th>
                <th>Ghi chú</th>
            </tr>
            </thead>
            <tbody>
            @foreach($dayRows as $row)
                @php
                    /** @var \App\Models\User $u */
                    $u = $row['user'];
                    /** @var \App\Models\StaffAttendance|null $att */
                    $att = $row['attendance'];
                @endphp
                <tr data-marked="{{ $att ? '1' : '0' }}">
                    <td>
                        <input type="checkbox" class="day-user-check" value="{{ $u->id }}">
                    </td>
                    <td>
                        <strong>{{ $u->name }}</strong>
                        <a href="{{ route('admin.staff-attendances.index', ['mode' => 'person', 'user_id' => $u->id, 'month' => $month]) }}" class="small text-muted ml-1">Lịch →</a>
                    </td>
                    <td class="text-right text-muted">{{ $fmt($u->daily_rate) }}</td>
                    <td>
                        @if($att)
                            <span class="badge {{ $att->statusBadgeClass() }}">{{ $att->statusLabel() }}</span>
                        @else
                            <span class="text-muted small">Chưa chấm</span>
                        @endif
                    </td>
                    <td class="small text-muted">{{ $att?->note }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <form id="formDayMark" method="POST" action="{{ route('admin.staff-attendances.store-day') }}" class="d-none">
        @csrf
        <input type="hidden" name="work_date" value="{{ $workDateStr }}">
        <input type="hidden" name="status" id="formDayStatus">
        <input type="hidden" name="note" id="formDayNote">
        <div id="formDayUserIds"></div>
    </form>
    <form id="formDayClear" method="POST" action="{{ route('admin.staff-attendances.destroy-day') }}" class="d-none">
        @csrf @method('DELETE')
        <input type="hidden" name="work_date" value="{{ $workDateStr }}">
        <div id="formDayClearUserIds"></div>
    </form>
@endif

@push('scripts')
<script>
(function () {
    function syncDayCount() {
        var n = $('.day-user-check:checked').length;
        $('#daySelectedCount').text(n);
        var total = $('.day-user-check').length;
        $('#dayCheckAll').prop('checked', total > 0 && n === total);
    }

    function fillUserIds($box) {
        $box.empty();
        $('.day-user-check:checked').each(function () {
            $box.append($('<input>', { type: 'hidden', name: 'user_ids[]', value: this.value }));
        });
    }

    $(document).on('change', '.day-user-check', syncDayCount);

    $('#dayCheckAll').on('change', function () {
        $('.day-user-check').prop('checked', this.checked);
        syncDayCount();
    });

    $('#btnDaySelectAll').on('click', function () {
        $('.day-user-check').prop('checked', true);
        syncDayCount();
    });

    $('#btnDaySelectUnmarked').on('click', function () {
        $('.day-user-check').prop('checked', false);
        $('tr[data-marked="0"] .day-user-check').prop('checked', true);
        syncDayCount();
    });

    $('#btnDayClear').on('click', function () {
        $('.day-user-check').prop('checked', false);
        syncDayCount();
    });

    $('#btnDayMark').on('click', function () {
        if (!$('.day-user-check:checked').length) {
            alert('Chọn ít nhất một nhân viên.');
            return;
        }
        $('#formDayStatus').val($('#dayBulkStatus').val());
        $('#formDayNote').val($('#dayBulkNote').val());
        fillUserIds($('#formDayUserIds'));
        $('#formDayMark').submit();
    });

    $('#btnDayClearAtt').on('click', function () {
        if (!$('.day-user-check:checked').length) {
            alert('Chọn nhân viên cần xóa chấm công.');
            return;
        }
        if (!confirm('Xóa chấm công ngày này cho các nhân viên đã chọn?')) return;
        fillUserIds($('#formDayClearUserIds'));
        $('#formDayClear').submit();
    });

    syncDayCount();
})();
</script>
@endpush
