@extends('layouts.admin')

@section('title', 'Lương nhân viên')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Hai khái niệm quan trọng',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Phải trả (tạm tính)</strong>: công chấm (có mặt / nửa ngày) × lương ngày — chưa phải dòng tiền.</li>'
                .'<li><strong>Đã chi</strong>: phiếu Chi phí loại Lương nhân viên đã ghi nhận — mới ảnh hưởng dòng tiền / lãi lỗ TC.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Khi thanh toán lương',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Chấm công tại <em>Chấm công NV</em> (chọn nhiều ngày).</li>'
                .'<li>Chọn tháng → xem số còn lại từng NV.</li>'
                .'<li>Bấm <em>Chi lương</em> → tạo phiếu Chi phí (mặc định Đã chi nếu có quyền).</li>'
                .'</ol>',
        ],
        [
            'title' => 'Lưu ý',
            'body' => '<p class="mb-0">Có thể chi một phần. Mục <em>Đã chi ngay</em> chỉ hiện nếu có quyền <strong>Chi ngay</strong>. Không có quyền thì phiếu ở trạng thái chờ duyệt.</p>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Báo cáo lương nhân viên</h5>
            <small class="text-muted">Tạm tính từ chấm công → chi lương để ghi nhận dòng tiền.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @canPerm('system.staff_attendances.view')
            <a href="{{ route('admin.staff-attendances.index') }}" class="btn btn-sm btn-outline-secondary">Chấm công</a>
            @endcanPerm
            <a href="{{ route('admin.expenses.index', ['category' => 'staff_salary']) }}" class="btn btn-sm btn-outline-secondary">Phiếu chi lương</a>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalStaffPayrollHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form method="GET" class="filter-bar mb-3">
            <select name="month" class="form-control form-control-sm" style="max-width:130px">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month == $m)>Tháng {{ $m }}</option>
                @endfor
            </select>
            <select name="year" class="form-control form-control-sm" style="max-width:110px">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endfor
            </select>
            <button class="btn btn-sm btn-outline-secondary">Xem</button>
        </form>

        <div class="alert alert-light border small mb-3">
            <strong>Dòng tiền:</strong>
            Chấm công chỉ tạo <em>công nợ lương nội bộ</em>.
            Khi bấm <strong>Chi lương</strong>, hệ thống tạo khoản <strong>Chi phí → Lương nhân viên</strong>
            → Dashboard TC / Báo cáo TC ghi nhận tiền ra.
        </div>

        <div class="row mb-3">
            @foreach([
                ['Phải trả (tạm tính)', $report['totals']['accrued'], 'primary'],
                ['Đã lập phiếu / đã chi', $report['totals']['paid'], 'success'],
                ['Còn lại', $report['totals']['remaining'], 'danger'],
                ['Tổng công', $report['totals']['days'].' ngày', 'secondary'],
            ] as [$label, $val, $tone])
                <div class="col-6 col-md-3 mb-2">
                    <div class="stat-card py-3">
                        <div class="stat-label">{{ $label }}</div>
                        <div class="stat-value text-{{ $tone }}" style="font-size:1.15rem">
                            {{ is_numeric($val) ? $fmt($val) : $val }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Chi nhánh</th>
                    <th>Lương/ngày</th>
                    <th>Công</th>
                    <th>Phải trả</th>
                    <th>Đã chi / phiếu</th>
                    <th>Còn lại</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($report['rows'] as $row)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', ['user' => $row['user_id'], 'tab' => 'payroll', 'month' => $month, 'year' => $year]) }}" class="font-weight-bold text-dark">{{ $row['name'] }}</a>
                        </td>
                        <td>{{ $row['branch'] ?: '—' }}</td>
                        <td>{{ $fmt($row['rate']) }}</td>
                        <td>{{ $row['days'] }} <span class="small text-muted">({{ $row['present_count'] }} đủ · {{ $row['half_count'] }} nửa)</span></td>
                        <td class="font-weight-bold">{{ $fmt($row['accrued']) }}</td>
                        <td>{{ $fmt($row['paid']) }}</td>
                        <td class="{{ $row['remaining'] > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $fmt($row['remaining']) }}</td>
                        <td class="text-nowrap text-right">
                            @canPerm('finance.expenses.manage')
                            @if($row['remaining'] > 0)
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-toggle="modal"
                                        data-target="#modalPayStaffSalary"
                                        data-user-id="{{ $row['user_id'] }}"
                                        data-user-name="{{ $row['name'] }}"
                                        data-amount="{{ $row['remaining'] }}"
                                        data-billing="{{ $report['billing_month'] }}">
                                    Chi lương
                                </button>
                            @else
                                <span class="small text-success">Đủ phiếu</span>
                            @endif
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Tháng này chưa có chấm công / phiếu lương NV.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@canPerm('finance.expenses.manage')
<div class="modal fade" id="modalPayStaffSalary" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.finance.staff-payroll.pay') }}" class="modal-content">
            @csrf
            <input type="hidden" name="user_id" id="payStaffUserId">
            <input type="hidden" name="billing_month" id="payStaffBillingMonth" value="{{ $report['billing_month'] }}">
            <div class="modal-header">
                <h5 class="modal-title">Chi lương — <span id="payStaffUserName"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    Tạo khoản Chi phí loại <strong>Lương nhân viên</strong>.
                    @canPerm('finance.expenses.pay_immediate')
                        Tick <em>Đã chi ngay</em> để ghi nhận tiền ra ngay trên báo cáo TC.
                    @else
                        Phiếu sẽ ở trạng thái <em>Chờ duyệt</em> cho đến khi được duyệt / đánh dấu đã chi.
                    @endcanPerm
                </p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Số tiền *</label>
                        <input type="number" name="amount" id="payStaffAmount" class="form-control" min="1" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Ngày chi *</label>
                        <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="note" id="payStaffNote" class="form-control" rows="2"></textarea>
                </div>
                @canPerm('finance.expenses.pay_immediate')
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="mark_paid" value="0">
                    <input type="checkbox" class="custom-control-input" id="payStaffMarkPaid" name="mark_paid" value="1" checked>
                    <label class="custom-control-label" for="payStaffMarkPaid">Đã chi ngay (ghi nhận dòng tiền)</label>
                </div>
                @else
                <input type="hidden" name="mark_paid" value="0">
                @endcanPerm
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary">Xác nhận</button>
            </div>
        </form>
    </div>
</div>
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalStaffPayrollHelp',
    'title' => 'Hướng dẫn — Lương nhân viên',
    'items' => $helpItems,
])
@endsection

@push('scripts')
<script>
$('#modalPayStaffSalary').on('show.bs.modal', function (e) {
    var btn = $(e.relatedTarget);
    var name = btn.data('user-name');
    var amount = btn.data('amount');
    var billing = btn.data('billing');
    $('#payStaffUserId').val(btn.data('user-id'));
    $('#payStaffUserName').text(name);
    $('#payStaffAmount').val(amount);
    $('#payStaffBillingMonth').val(billing);
    var parts = String(billing).split('-');
    $('#payStaffNote').val('Lương NV ' + name + ' tháng ' + (parts[1] || '') + '/' + (parts[0] || ''));
});
</script>
@endpush
