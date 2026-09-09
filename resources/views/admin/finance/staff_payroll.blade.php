@extends('layouts.admin')

@section('title', 'Lương nhân viên')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Công thức tính lương tháng',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Gốc</strong>: ngày công (có mặt = 1, nửa ngày = 0,5) × lương/ngày trên hồ sơ NV.</li>'
                .'<li><strong>Phải trả (net)</strong> = Gốc + Thưởng − Phạt − Ứng trước.</li>'
                .'<li><strong>Đã chi</strong>: phiếu Chi phí loại <em>Lương nhân viên</em> (không gồm ứng).</li>'
                .'<li><strong>Còn lại</strong> = max(0, Net − Đã chi). Nút <em>Chi lương</em> mặc định = còn lại.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Thưởng / Phạt / Ứng trước',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Menu <strong>Hành động</strong> từng dòng: Thưởng / Phạt / Ứng — <em>ghi chú bắt buộc</em>.</li>'
                .'<li><strong>Thưởng / Phạt</strong>: điều chỉnh bảng lương; thưởng được chi khi chọn <em>Chi lương</em>.</li>'
                .'<li><strong>Ứng trước</strong>: tạo ngay phiếu Chi phí <em>Ứng lương NV</em> và trừ vào net tháng.</li>'
                .'<li><strong>Thưởng tất cả / Phạt tất cả</strong> (menu Thao tác): cùng số tiền + ghi chú cho mọi NV <em>active</em> của chi nhánh đang chọn.</li>'
                .'<li>Xem / xóa ở <em>Lịch sử điều chỉnh tháng</em>. Không xóa ứng nếu phiếu đã <em>Đã chi</em>.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Chấm công, PDF &amp; phiếu chi',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Chấm công trước tại <a href="'.e(route('admin.staff-attendances.index')).'">Chấm công NV</a> thì mới có gốc lương.</li>'
                .'<li>Menu Hành động → <em>Xuất PDF cá nhân</em> = bảng lương từng người.</li>'
                .'<li><em>Xuất tổng hợp</em> = bảng cả tháng. Phiếu chi: menu Chi phí (Lương NV / Ứng lương NV).</li>'
                .'</ul>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Báo cáo lương nhân viên</h5>
            <small class="text-muted">Gốc ngày công + thưởng/phạt/ứng → chi lương.</small>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap:.5rem">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                    Thao tác
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    @canPerm('finance.expenses.manage')
                    <button type="button" class="dropdown-item" data-toggle="modal" data-target="#modalBulkStaffAdj"
                            data-type="bonus" data-label="Thưởng tất cả NV">
                        <i class="bi bi-gift text-success mr-1"></i> Thưởng tất cả
                    </button>
                    <button type="button" class="dropdown-item" data-toggle="modal" data-target="#modalBulkStaffAdj"
                            data-type="penalty" data-label="Phạt tất cả NV">
                        <i class="bi bi-exclamation-diamond text-warning mr-1"></i> Phạt tất cả
                    </button>
                    <div class="dropdown-divider"></div>
                    @endcanPerm
                    @canPerm('system.staff_attendances.view')
                    <a class="dropdown-item" href="{{ route('admin.staff-attendances.index') }}">
                        <i class="bi bi-calendar2-check mr-1"></i> Chấm công
                    </a>
                    @endcanPerm
                    <a class="dropdown-item" href="{{ route('admin.expenses.index', ['category' => 'staff_salary']) }}">
                        <i class="bi bi-receipt mr-1"></i> Phiếu chi lương
                    </a>
                </div>
            </div>
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
            <a href="{{ route('admin.finance.staff-payroll.pdf', ['month' => $month, 'year' => $year]) }}"
               class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="bi bi-file-earmark-spreadsheet"></i> Xuất tổng hợp
            </a>
        </form>

        <div class="row mb-3">
            @foreach([
                ['Gốc (công)', $report['totals']['accrued'], 'secondary'],
                ['Phải trả (net)', $report['totals']['net'] ?? $report['totals']['accrued'], 'primary'],
                ['Đã chi lương', $report['totals']['paid'], 'success'],
                ['Còn lại', $report['totals']['remaining'], 'danger'],
            ] as [$label, $val, $tone])
                <div class="col-6 col-md-3 mb-2">
                    <div class="stat-card py-3">
                        <div class="stat-label">{{ $label }}</div>
                        <div class="stat-value text-{{ $tone }}" style="font-size:1.15rem">{{ $fmt($val) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0 table-sm">
                <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Công</th>
                    <th>Gốc</th>
                    <th>Thưởng</th>
                    <th>Phạt</th>
                    <th>Ứng</th>
                    <th>Phải trả</th>
                    <th>Đã chi</th>
                    <th>Còn lại</th>
                    <th class="text-right" style="min-width:7rem">Hành động</th>
                </tr>
                </thead>
                <tbody>
                @forelse($report['rows'] as $row)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', ['user' => $row['user_id'], 'tab' => 'payroll', 'month' => $month, 'year' => $year]) }}" class="font-weight-bold text-dark">{{ $row['name'] }}</a>
                            <div class="small text-muted">{{ $row['branch'] ?: '—' }} · {{ $fmt($row['rate']) }}/ngày</div>
                        </td>
                        <td>{{ $row['days'] }} <span class="small text-muted">({{ $row['present_count'] }} đủ · {{ $row['half_count'] }} nửa)</span></td>
                        <td>{{ $fmt($row['accrued']) }}</td>
                        <td class="{{ ($row['bonus'] ?? 0) > 0 ? 'text-success' : 'text-muted' }}">{{ $fmt($row['bonus'] ?? 0) }}</td>
                        <td class="{{ ($row['penalty'] ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">{{ $fmt($row['penalty'] ?? 0) }}</td>
                        <td class="{{ ($row['advance'] ?? 0) > 0 ? 'text-info' : 'text-muted' }}">{{ $fmt($row['advance'] ?? 0) }}</td>
                        <td class="font-weight-bold">{{ $fmt($row['net'] ?? $row['accrued']) }}</td>
                        <td>{{ $fmt($row['paid']) }}</td>
                        <td class="{{ $row['remaining'] > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ $fmt($row['remaining']) }}</td>
                        <td class="text-nowrap text-right">
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        data-toggle="dropdown" data-display="static" aria-haspopup="true" aria-expanded="false">
                                    Hành động
                                </button>
                                <div class="dropdown-menu dropdown-menu-right shadow">
                                    <a class="dropdown-item" href="{{ route('admin.finance.staff-payroll.person-pdf', ['user' => $row['user_id'], 'month' => $month, 'year' => $year]) }}"
                                       target="_blank">
                                        <i class="bi bi-file-earmark-pdf text-danger mr-1"></i> Xuất PDF cá nhân
                                    </a>
                                    @canPerm('finance.expenses.manage')
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item"
                                            data-toggle="modal" data-target="#modalStaffAdj"
                                            data-type="bonus" data-label="Thưởng"
                                            data-user-id="{{ $row['user_id'] }}"
                                            data-user-name="{{ $row['name'] }}">
                                        <i class="bi bi-gift text-success mr-1"></i> Thưởng
                                    </button>
                                    <button type="button" class="dropdown-item"
                                            data-toggle="modal" data-target="#modalStaffAdj"
                                            data-type="penalty" data-label="Phạt"
                                            data-user-id="{{ $row['user_id'] }}"
                                            data-user-name="{{ $row['name'] }}">
                                        <i class="bi bi-exclamation-diamond text-warning mr-1"></i> Phạt
                                    </button>
                                    <button type="button" class="dropdown-item"
                                            data-toggle="modal" data-target="#modalStaffAdj"
                                            data-type="advance" data-label="Ứng trước"
                                            data-user-id="{{ $row['user_id'] }}"
                                            data-user-name="{{ $row['name'] }}">
                                        <i class="bi bi-cash-coin text-info mr-1"></i> Ứng trước
                                    </button>
                                    @if($row['remaining'] > 0)
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item font-weight-bold text-primary"
                                            data-toggle="modal" data-target="#modalPayStaffSalary"
                                            data-user-id="{{ $row['user_id'] }}"
                                            data-user-name="{{ $row['name'] }}"
                                            data-amount="{{ $row['remaining'] }}"
                                            data-billing="{{ $report['billing_month'] }}">
                                        <i class="bi bi-wallet2 mr-1"></i> Chi lương
                                    </button>
                                    @else
                                    <div class="dropdown-divider"></div>
                                    <span class="dropdown-item-text small text-success"><i class="bi bi-check2 mr-1"></i> Đã chi đủ</span>
                                    @endif
                                    @endcanPerm
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Tháng này chưa có chấm công / điều chỉnh / phiếu lương.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(($report['adjustments'] ?? collect())->isNotEmpty())
            <div class="mt-3">
                <button class="btn btn-sm btn-link px-0" type="button" data-toggle="collapse" data-target="#staffAdjList">
                    Lịch sử điều chỉnh tháng ({{ $report['adjustments']->count() }})
                </button>
                <div class="collapse" id="staffAdjList">
                    <div class="table-responsive border rounded">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Loại</th>
                                <th>NV</th>
                                <th>Số tiền</th>
                                <th>Ghi chú</th>
                                <th>Người tạo</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($report['adjustments'] as $adj)
                                <tr>
                                    <td>{{ $adj->typeLabel() }}</td>
                                    <td>{{ $adj->user?->name ?: '—' }}</td>
                                    <td>{{ $fmt($adj->amount) }}</td>
                                    <td class="small">{{ $adj->note }}</td>
                                    <td class="small text-muted">{{ $adj->creator?->name }} · {{ $adj->created_at?->format('d/m H:i') }}</td>
                                    <td class="text-right">
                                        @canPerm('finance.expenses.manage')
                                        <form method="POST" action="{{ route('admin.finance.staff-payroll.adjustments.destroy', $adj) }}" class="d-inline"
                                              onsubmit="return confirm('Xóa khoản này?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                                        </form>
                                        @endcanPerm
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
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
                <p class="small text-muted mb-2">Số mặc định = còn lại (đã gồm thưởng/phạt/ứng).</p>
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
                    <label class="custom-control-label" for="payStaffMarkPaid">Đã chi ngay</label>
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

<div class="modal fade" id="modalStaffAdj" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.finance.staff-payroll.adjustments.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="user_id" id="adjStaffUserId">
            <input type="hidden" name="billing_month" value="{{ $report['billing_month'] }}">
            <input type="hidden" name="type" id="adjStaffType">
            <div class="modal-header">
                <h5 class="modal-title"><span id="adjStaffLabel">Điều chỉnh</span> — <span id="adjStaffUserName"></span></h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Số tiền *</label>
                    <input type="number" name="amount" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label>Ghi chú *</label>
                    <textarea name="note" class="form-control" rows="3" required placeholder="Lý do thưởng / phạt / ứng…"></textarea>
                </div>
                <div id="adjStaffAdvanceHint" class="small text-muted d-none">
                    Ứng trước sẽ tạo phiếu Chi phí <strong>Ứng lương NV</strong> và trừ vào phải trả tháng này.
                    @canPerm('finance.expenses.pay_immediate')
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="hidden" name="mark_paid" value="0">
                        <input type="checkbox" class="custom-control-input" id="adjStaffMarkPaid" name="mark_paid" value="1" checked>
                        <label class="custom-control-label" for="adjStaffMarkPaid">Đã chi ngay khoản ứng</label>
                    </div>
                    @else
                    <input type="hidden" name="mark_paid" value="0">
                    @endcanPerm
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalBulkStaffAdj" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.finance.staff-payroll.adjustments.bulk') }}" class="modal-content">
            @csrf
            <input type="hidden" name="billing_month" value="{{ $report['billing_month'] }}">
            <input type="hidden" name="type" id="bulkStaffAdjType">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkStaffAdjTitle">Thưởng/Phạt tất cả</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Áp cùng số tiền cho <strong>mọi nhân viên active</strong> của chi nhánh hiện tại.</p>
                <div class="form-group">
                    <label>Số tiền *</label>
                    <input type="number" name="amount" class="form-control" min="1" required>
                </div>
                <div class="form-group">
                    <label>Ghi chú *</label>
                    <textarea name="note" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary">Áp dụng</button>
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
    var billing = btn.data('billing');
    $('#payStaffUserId').val(btn.data('user-id'));
    $('#payStaffUserName').text(name);
    $('#payStaffAmount').val(btn.data('amount'));
    $('#payStaffBillingMonth').val(billing);
    var parts = String(billing).split('-');
    $('#payStaffNote').val('Lương NV ' + name + ' tháng ' + (parts[1] || '') + '/' + (parts[0] || ''));
});
$('#modalStaffAdj').on('show.bs.modal', function (e) {
    var btn = $(e.relatedTarget);
    var type = btn.data('type');
    $('#adjStaffUserId').val(btn.data('user-id'));
    $('#adjStaffUserName').text(btn.data('user-name'));
    $('#adjStaffType').val(type);
    $('#adjStaffLabel').text(btn.data('label'));
    $('#adjStaffAdvanceHint').toggleClass('d-none', type !== 'advance');
});
$('#modalBulkStaffAdj').on('show.bs.modal', function (e) {
    var btn = $(e.relatedTarget);
    $('#bulkStaffAdjType').val(btn.data('type'));
    $('#bulkStaffAdjTitle').text(btn.data('label'));
});
</script>
@endpush
