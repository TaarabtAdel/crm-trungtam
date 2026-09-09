@extends('layouts.admin')

@section('title', 'Lương giáo viên')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Công thức tính lương tháng',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Gốc</strong>: buổi <em>Hoàn thành</em> × đơn giá/giờ (theo buổi; kể cả dạy thay / đơn giá lớp).</li>'
                .'<li><strong>Phải trả (net)</strong> = Gốc + Thưởng − Phạt − Ứng trước.</li>'
                .'<li><strong>Đã chi</strong>: phiếu Chi phí loại <em>Lương GV</em> (không gồm ứng).</li>'
                .'<li><strong>Còn lại</strong> = max(0, Net − Đã chi). Nút <em>Chi lương</em> mặc định = còn lại.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Thưởng / Phạt / Ứng trước',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Menu <strong>Hành động</strong> từng dòng: Thưởng / Phạt / Ứng — <em>ghi chú bắt buộc</em>.</li>'
                .'<li><strong>Thưởng / Phạt</strong>: chỉ điều chỉnh bảng lương; tiền thưởng được chi khi chọn <em>Chi lương</em>.</li>'
                .'<li><strong>Ứng trước</strong>: tạo ngay phiếu Chi phí <em>Ứng lương GV</em> (vào dòng tiền) và trừ vào net tháng.</li>'
                .'<li><strong>Thưởng tất cả / Phạt tất cả</strong> (menu Thao tác): cùng số tiền + ghi chú cho mọi GV <em>active</em> của chi nhánh đang chọn.</li>'
                .'<li>Xem / xóa ở mục <em>Lịch sử điều chỉnh tháng</em>. Không xóa được ứng nếu phiếu chi đã ở trạng thái <em>Đã chi</em>.</li>'
                .'</ul>',
        ],
        [
            'title' => 'PDF &amp; phiếu chi',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Menu Hành động → <em>Xuất PDF cá nhân</em> = bảng lương từng người (chi tiết buổi + điều chỉnh).</li>'
                .'<li><em>Xuất tổng hợp</em> = bảng cả tháng (có thưởng/phạt/ứng/net).</li>'
                .'<li>Phiếu chi lương / ứng: menu <a href="'.e(route('admin.expenses.index', ['category' => 'salary'])).'">Chi phí</a>.</li>'
                .'</ul>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Báo cáo lương giáo viên</h5>
            <small class="text-muted">Gốc buổi HT + thưởng/phạt/ứng → chi lương.</small>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap:.5rem">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                    Thao tác
                </button>
                <div class="dropdown-menu dropdown-menu-right shadow">
                    @canPerm('finance.expenses.manage')
                    <button type="button" class="dropdown-item" data-toggle="modal" data-target="#modalBulkTeacherAdj"
                            data-type="bonus" data-label="Thưởng tất cả GV">
                        <i class="bi bi-gift text-success mr-1"></i> Thưởng tất cả
                    </button>
                    <button type="button" class="dropdown-item" data-toggle="modal" data-target="#modalBulkTeacherAdj"
                            data-type="penalty" data-label="Phạt tất cả GV">
                        <i class="bi bi-exclamation-diamond text-warning mr-1"></i> Phạt tất cả
                    </button>
                    <div class="dropdown-divider"></div>
                    @endcanPerm
                    <a class="dropdown-item" href="{{ route('admin.expenses.index', ['category' => 'salary']) }}">
                        <i class="bi bi-receipt mr-1"></i> Phiếu chi lương
                    </a>
                </div>
            </div>
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
            <a href="{{ route('admin.finance.teacher-payroll.pdf', ['month' => $month, 'year' => $year]) }}"
               class="btn btn-sm btn-outline-secondary" target="_blank">
                <i class="bi bi-file-earmark-spreadsheet"></i> Xuất tổng hợp
            </a>
        </form>

        <div class="row mb-3">
            @foreach([
                ['Gốc (buổi HT)', $report['totals']['accrued'], 'secondary'],
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
                    <th>Giáo viên</th>
                    <th>Buổi / Giờ</th>
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
                            <a href="{{ route('admin.teachers.show', ['teacher' => $row['teacher_id'], 'tab' => 'payroll', 'payroll_month' => $month, 'payroll_year' => $year]) }}" class="font-weight-bold text-dark">{{ $row['name'] }}</a>
                            <div class="small text-muted">{{ $row['branch'] ?: '—' }}</div>
                        </td>
                        <td>{{ $row['sessions'] }} / {{ $row['hours'] }}h</td>
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
                                    <a class="dropdown-item" href="{{ route('admin.finance.teacher-payroll.person-pdf', ['teacher' => $row['teacher_id'], 'month' => $month, 'year' => $year]) }}"
                                       target="_blank">
                                        <i class="bi bi-file-earmark-pdf text-danger mr-1"></i> Xuất PDF cá nhân
                                    </a>
                                    @canPerm('finance.expenses.manage')
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item"
                                            data-toggle="modal" data-target="#modalTeacherAdj"
                                            data-type="bonus" data-label="Thưởng"
                                            data-teacher-id="{{ $row['teacher_id'] }}"
                                            data-teacher-name="{{ $row['name'] }}">
                                        <i class="bi bi-gift text-success mr-1"></i> Thưởng
                                    </button>
                                    <button type="button" class="dropdown-item"
                                            data-toggle="modal" data-target="#modalTeacherAdj"
                                            data-type="penalty" data-label="Phạt"
                                            data-teacher-id="{{ $row['teacher_id'] }}"
                                            data-teacher-name="{{ $row['name'] }}">
                                        <i class="bi bi-exclamation-diamond text-warning mr-1"></i> Phạt
                                    </button>
                                    <button type="button" class="dropdown-item"
                                            data-toggle="modal" data-target="#modalTeacherAdj"
                                            data-type="advance" data-label="Ứng trước"
                                            data-teacher-id="{{ $row['teacher_id'] }}"
                                            data-teacher-name="{{ $row['name'] }}">
                                        <i class="bi bi-cash-coin text-info mr-1"></i> Ứng trước
                                    </button>
                                    @if($row['remaining'] > 0)
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item font-weight-bold text-primary"
                                            data-toggle="modal" data-target="#modalPaySalary"
                                            data-teacher-id="{{ $row['teacher_id'] }}"
                                            data-teacher-name="{{ $row['name'] }}"
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
                    <tr><td colspan="10" class="text-center text-muted py-4">Tháng này chưa có dữ liệu lương / điều chỉnh.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(($report['adjustments'] ?? collect())->isNotEmpty())
            <div class="mt-3">
                <button class="btn btn-sm btn-link px-0" type="button" data-toggle="collapse" data-target="#teacherAdjList">
                    Lịch sử điều chỉnh tháng ({{ $report['adjustments']->count() }})
                </button>
                <div class="collapse" id="teacherAdjList">
                    <div class="table-responsive border rounded">
                        <table class="table table-sm mb-0">
                            <thead>
                            <tr>
                                <th>Loại</th>
                                <th>GV</th>
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
                                    <td>{{ $adj->teacher?->name ?: '—' }}</td>
                                    <td>{{ $fmt($adj->amount) }}</td>
                                    <td class="small">{{ $adj->note }}</td>
                                    <td class="small text-muted">{{ $adj->creator?->name }} · {{ $adj->created_at?->format('d/m H:i') }}</td>
                                    <td class="text-right">
                                        @canPerm('finance.expenses.manage')
                                        <form method="POST" action="{{ route('admin.finance.teacher-payroll.adjustments.destroy', $adj) }}" class="d-inline"
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
                <p class="small text-muted mb-2">Số mặc định = còn lại (đã gồm thưởng/phạt/ứng).</p>
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
                    <label class="custom-control-label" for="payMarkPaid">Đã chi ngay</label>
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

<div class="modal fade" id="modalTeacherAdj" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.finance.teacher-payroll.adjustments.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="teacher_id" id="adjTeacherId">
            <input type="hidden" name="billing_month" value="{{ $report['billing_month'] }}">
            <input type="hidden" name="type" id="adjType">
            <div class="modal-header">
                <h5 class="modal-title"><span id="adjLabel">Điều chỉnh</span> — <span id="adjTeacherName"></span></h5>
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
                <div id="adjAdvanceHint" class="small text-muted d-none">
                    Ứng trước sẽ tạo phiếu Chi phí <strong>Ứng lương GV</strong> và trừ vào phải trả tháng này.
                    @canPerm('finance.expenses.pay_immediate')
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="hidden" name="mark_paid" value="0">
                        <input type="checkbox" class="custom-control-input" id="adjMarkPaid" name="mark_paid" value="1" checked>
                        <label class="custom-control-label" for="adjMarkPaid">Đã chi ngay khoản ứng</label>
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

<div class="modal fade" id="modalBulkTeacherAdj" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.finance.teacher-payroll.adjustments.bulk') }}" class="modal-content">
            @csrf
            <input type="hidden" name="billing_month" value="{{ $report['billing_month'] }}">
            <input type="hidden" name="type" id="bulkAdjType">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkAdjTitle">Thưởng/Phạt tất cả</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Áp cùng số tiền cho <strong>mọi giáo viên active</strong> của chi nhánh hiện tại.</p>
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
    var billing = btn.data('billing');
    $('#payTeacherId').val(btn.data('teacher-id'));
    $('#payTeacherName').text(name);
    $('#payAmount').val(btn.data('amount'));
    $('#payBillingMonth').val(billing);
    var parts = String(billing).split('-');
    $('#payNote').val('Lương GV ' + name + ' tháng ' + (parts[1] || '') + '/' + (parts[0] || ''));
});
$('#modalTeacherAdj').on('show.bs.modal', function (e) {
    var btn = $(e.relatedTarget);
    var type = btn.data('type');
    $('#adjTeacherId').val(btn.data('teacher-id'));
    $('#adjTeacherName').text(btn.data('teacher-name'));
    $('#adjType').val(type);
    $('#adjLabel').text(btn.data('label'));
    $('#adjAdvanceHint').toggleClass('d-none', type !== 'advance');
});
$('#modalBulkTeacherAdj').on('show.bs.modal', function (e) {
    var btn = $(e.relatedTarget);
    $('#bulkAdjType').val(btn.data('type'));
    $('#bulkAdjTitle').text(btn.data('label'));
});
</script>
@endpush
