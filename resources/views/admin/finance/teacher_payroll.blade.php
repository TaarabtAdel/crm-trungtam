@extends('layouts.admin')

@section('title', 'Lương giáo viên')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Hai khái niệm quan trọng',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Phải trả (tạm tính)</strong>: buổi <em>Hoàn thành</em> × đơn giá/giờ — chưa phải dòng tiền.</li>'
                .'<li><strong>Đã chi</strong>: phiếu Chi phí loại Lương GV đã ghi nhận — mới ảnh hưởng dòng tiền / lãi lỗ TC.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Khi thanh toán lương',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Chọn tháng → xem số còn lại từng GV.</li>'
                .'<li>Bấm <em>Chi lương</em> → tạo phiếu Chi phí (mặc định Đã chi).</li>'
                .'<li>Dashboard TC / Báo cáo TC / menu Chi phí sẽ cập nhật khoản chi.</li>'
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
            <h5 class="mb-0 font-weight-bold">Báo cáo lương giáo viên</h5>
            <small class="text-muted">Tạm tính từ buổi hoàn thành → chi lương để ghi nhận dòng tiền.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <a href="{{ route('admin.expenses.index', ['category' => 'salary']) }}" class="btn btn-sm btn-outline-secondary">Phiếu chi lương</a>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalTeacherPayrollHelp">
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
            Buổi hoàn thành trên TKB chỉ tạo <em>công nợ lương nội bộ</em>.
            Khi bấm <strong>Chi lương</strong>, hệ thống tạo khoản <strong>Chi phí → Lương GV</strong>
            → Dashboard TC / Báo cáo TC ghi nhận tiền ra.
        </div>

        <div class="row mb-3">
            @foreach([
                ['Phải trả (tạm tính)', $report['totals']['accrued'], 'primary'],
                ['Đã lập phiếu / đã chi', $report['totals']['paid'], 'success'],
                ['Còn lại', $report['totals']['remaining'], 'danger'],
                ['Tổng giờ', $report['totals']['hours'].'h', 'secondary'],
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
                    <th>Giáo viên</th>
                    <th>Chi nhánh</th>
                    <th>Đơn giá/h</th>
                    <th>Buổi HT</th>
                    <th>Giờ</th>
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
                            <a href="{{ route('admin.teachers.show', ['teacher' => $row['teacher_id'], 'tab' => 'payroll', 'payroll_month' => $month, 'payroll_year' => $year]) }}" class="font-weight-bold text-dark">{{ $row['name'] }}</a>
                        </td>
                        <td>{{ $row['branch'] ?: '—' }}</td>
                        <td>{{ $fmt($row['rate']) }}</td>
                        <td>{{ $row['sessions'] }}</td>
                        <td>{{ $row['hours'] }}h</td>
                        <td class="font-weight-bold">{{ $fmt($row['accrued']) }}</td>
                        <td>{{ $fmt($row['paid']) }}</td>
                        <td class="{{ $row['remaining'] > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $fmt($row['remaining']) }}</td>
                        <td class="text-nowrap text-right">
                            @canPerm('finance.expenses.manage')
                            @if($row['remaining'] > 0)
                                <button type="button" class="btn btn-sm btn-primary"
                                        data-toggle="modal"
                                        data-target="#modalPaySalary"
                                        data-teacher-id="{{ $row['teacher_id'] }}"
                                        data-teacher-name="{{ $row['name'] }}"
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
                    <tr><td colspan="9" class="text-center text-muted py-4">Tháng này chưa có buổi hoàn thành / phiếu lương.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@canPerm('finance.expenses.manage')
<div class="modal fade" id="modalPaySalary" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.finance.teacher-payroll.pay') }}" class="modal-content">
            @csrf
            <input type="hidden" name="teacher_id" id="payTeacherId">
            <input type="hidden" name="billing_month" id="payBillingMonth" value="{{ $report['billing_month'] }}">
            <div class="modal-header">
                <h5 class="modal-title">Chi lương — <span id="payTeacherName"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">
                    Tạo khoản Chi phí loại <strong>Lương GV</strong>.
                    @canPerm('finance.expenses.pay_immediate')
                        Tick <em>Đã chi ngay</em> để ghi nhận tiền ra ngay trên báo cáo TC.
                    @else
                        Phiếu sẽ ở trạng thái <em>Chờ duyệt</em> cho đến khi được duyệt / đánh dấu đã chi.
                    @endcanPerm
                </p>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Số tiền *</label>
                        <input type="number" name="amount" id="payAmount" class="form-control" min="1" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Ngày chi *</label>
                        <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Ghi chú</label>
                    <textarea name="note" id="payNote" class="form-control" rows="2"></textarea>
                </div>
                @canPerm('finance.expenses.pay_immediate')
                <div class="custom-control custom-checkbox">
                    <input type="hidden" name="mark_paid" value="0">
                    <input type="checkbox" class="custom-control-input" id="payMarkPaid" name="mark_paid" value="1" checked>
                    <label class="custom-control-label" for="payMarkPaid">Đã chi ngay (ghi nhận dòng tiền)</label>
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
    'modalId' => 'modalTeacherPayrollHelp',
    'title' => 'Hướng dẫn — Lương giáo viên',
    'items' => $helpItems,
])
@endsection

@push('scripts')
<script>
$('#modalPaySalary').on('show.bs.modal', function (e) {
    var btn = $(e.relatedTarget);
    var name = btn.data('teacher-name');
    var amount = btn.data('amount');
    var billing = btn.data('billing');
    $('#payTeacherId').val(btn.data('teacher-id'));
    $('#payTeacherName').text(name);
    $('#payAmount').val(amount);
    $('#payBillingMonth').val(billing);
    var parts = String(billing).split('-');
    $('#payNote').val('Lương GV ' + name + ' tháng ' + (parts[1] || '') + '/' + (parts[0] || ''));
});
</script>
@endpush
