@extends('layouts.admin')

@section('title', 'Hóa đơn')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Tạo hóa đơn mới',
            'body' => '<p class="mb-0">Bấm <strong>+ Tạo hóa đơn</strong>, chọn học viên. Nếu chọn lớp: chọn hình thức <em>Theo tháng / Theo buổi / Theo khóa</em> (giống tab thu học phí lớp), có thể giảm giá. Không chọn lớp thì nhập số tiền thủ công.</p>',
        ],
        [
            'title' => 'Trạng thái hóa đơn',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><em>Chưa thu</em> — chưa có khoản thanh toán nào.</li>'
                .'<li><em>Thu một phần</em> — đã thu nhưng còn nợ.</li>'
                .'<li><em>Đã thu</em> — thu đủ; hoa hồng có thể phát sinh nếu đã gắn Sales.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Thu tiền & lọc danh sách',
            'body' => '<p class="mb-0">Bấm <em>Chi tiết</em> trên từng dòng để ghi nhận thanh toán / trả góp. Dùng ô tìm kiếm và bộ lọc trạng thái để tìm nhanh HĐ cần xử lý.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Danh sách Hóa đơn</h5>
            <small class="text-muted">Theo dõi công nợ, thanh toán và trả góp học phí.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @canPerm('finance.invoices.manage')
            <button type="submit" form="bulkDeleteInvoices" class="btn btn-outline-danger btn-sm d-none" id="bulkDeleteBtn" disabled>
                <i class="bi bi-trash"></i> Xóa đã chọn (<span id="bulkSelectedCount">0</span>)
            </button>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Tạo hóa đơn</button>
            @endcanPerm
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalInvoicesHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:220px" placeholder="Mã HĐ, học viên...">
            <select name="class_id" class="form-control form-control-sm js-filter-class" style="max-width:240px" data-placeholder="Tất cả lớp">
                <option value=""></option>
                @if(!empty($selectedClass))
                    <option value="{{ $selectedClass->id }}" selected>{{ $selectedClass->name }}@if($selectedClass->code) ({{ $selectedClass->code }})@endif</option>
                @endif
            </select>
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\Invoice::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            @canPerm('finance.invoices.manage')
            <form id="bulkDeleteInvoices" method="POST" action="{{ route('admin.invoices.bulk-destroy') }}" class="d-none"
                  onsubmit="return confirm('Xóa ' + ($('.js-invoice-check:checked').length) + ' hóa đơn đã chọn? Thao tác không hoàn tác.');">
                @csrf
            </form>
            @endcanPerm
            <table class="table table-hover mb-0 invoices-table">
                <thead>
                <tr>
                    @canPerm('finance.invoices.manage')
                    <th style="width:36px">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="checkAllInvoices">
                            <label class="custom-control-label" for="checkAllInvoices"></label>
                        </div>
                    </th>
                    @endcanPerm
                    <th>Hóa đơn</th>
                    <th>Học viên</th>
                    <th>Số tiền</th>
                    <th>Đã thu / Còn nợ</th>
                    <th>Hạn</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        @canPerm('finance.invoices.manage')
                        <td>
                            @if($invoice->canBeDeleted())
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input js-invoice-check"
                                       form="bulkDeleteInvoices"
                                       id="inv{{ $invoice->id }}" name="ids[]" value="{{ $invoice->id }}">
                                <label class="custom-control-label" for="inv{{ $invoice->id }}"></label>
                            </div>
                            @endif
                        </td>
                        @endcanPerm
                        <td>
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="font-weight-bold text-dark">{{ $invoice->code }}</a>
                            <div class="mt-1 d-flex flex-wrap" style="gap:.35rem">
                                <span class="badge lead-status {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                                @if($invoice->courseClass)
                                    <span class="lead-meta-chip">{{ $invoice->courseClass->name }}</span>
                                @endif
                                @if($invoice->billing_month)
                                    <span class="lead-meta-chip">{{ $invoice->billing_month }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div>{{ $invoice->student?->name }}</div>
                            <div class="small text-muted">{{ $invoice->branch?->name }}</div>
                        </td>
                        <td class="font-weight-bold">{{ $fmt($invoice->amount) }}</td>
                        <td>
                            <div class="text-success">{{ $fmt($invoice->paid_amount) }}</div>
                            <div class="small text-danger">{{ $fmt($invoice->remaining_amount) }}</div>
                        </td>
                        <td class="small text-muted">
                            {{ optional($invoice->due_date)->format('d/m/Y') ?: '—' }}
                            @if($invoice->isOverdue())
                                <div class="text-danger">Quá hạn {{ $invoice->daysOverdue() }} ngày</div>
                            @endif
                        </td>
                        <td class="text-nowrap text-right">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-primary">Chi tiết</a>
                            <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" title="PDF"><i class="bi bi-file-pdf"></i></a>
                            @canPerm('finance.invoices.manage')
                                @if($invoice->canBeDeleted())
                                <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                @endif
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->hasPermission('finance.invoices.manage') ? 7 : 6 }}" class="text-center text-muted py-4">Chưa có hóa đơn.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </div>
</div>

@canPerm('finance.invoices.manage')
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.invoices.store') }}" class="modal-content js-invoice-create-form">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tạo hóa đơn</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.finance._invoice_form', ['invoice'=>null,'salesUsers'=>$salesUsers,'selectedStudent'=>$selectedStudent ?? null,'selectedClass'=>null])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalInvoicesHelp',
    'title' => 'Hướng dẫn — Danh sách hóa đơn',
    'items' => $helpItems,
])

@include('partials.select2')
@endsection

@push('scripts')
<script>
(function () {
    var suggestUrl = @json(route('admin.invoices.suggest'));
    var studentsUrl = @json(route('admin.lookup.students'));
    var classesUrl = @json(route('admin.lookup.classes'));
    var previews = null;
    var unitFee = 0;
    var sessions = [];
    var select2Ready = false;

    function fmt(n) {
        return Math.round(Number(n) || 0).toLocaleString('vi-VN') + ' đ';
    }

    function $form() {
        return $('.js-invoice-create-form');
    }

    function feeType() {
        return $form().find('.js-fee-type-radio:checked').val() || 'monthly';
    }

    function selectedSessionIds() {
        return $form().find('.js-session-pick:checked').map(function () { return $(this).val(); }).get();
    }

    function initInvoiceSelect2() {
        if (select2Ready) return;
        var $modal = $('#modalCreate');
        crmSelect2Ajax($form().find('.js-invoice-student'), studentsUrl, {
            placeholder: 'Tìm học viên theo tên, SĐT...',
            dropdownParent: $modal,
            allowClear: false
        });
        crmSelect2Ajax($form().find('.js-invoice-class'), classesUrl, {
            placeholder: 'Tìm lớp (để trống = nhập tay)',
            dropdownParent: $modal,
            allowClear: true
        });
        select2Ready = true;
    }

    function renderSessions() {
        var $list = $form().find('.js-session-list');
        if (!sessions.length) {
            $list.html('<p class="small text-muted mb-0">Tháng này chưa có buổi trên lịch.</p>');
            return;
        }
        var html = '';
        sessions.forEach(function (s) {
            html += '<div class="custom-control custom-checkbox mb-1">'
                + '<input type="checkbox" class="custom-control-input js-session-pick" id="invSess' + s.id + '" name="session_ids[]" value="' + s.id + '">'
                + '<label class="custom-control-label small" for="invSess' + s.id + '">'
                + s.date + (s.time ? ' · ' + s.time : '') + ' <span class="text-muted">· ' + s.status + '</span>'
                + ' · ' + fmt(unitFee)
                + '</label></div>';
        });
        $list.html(html);
    }

    function applyPreview() {
        var $f = $form();
        var classId = $f.find('.js-invoice-class').val();
        if (!classId || !previews) {
            $f.find('.js-class-billing').addClass('d-none');
            $f.find('.js-manual-amount-wrap').removeClass('d-none');
            $f.find('.js-invoice-amount').prop('required', true);
            recalcManual();
            return;
        }

        $f.find('.js-class-billing').removeClass('d-none');
        $f.find('.js-manual-amount-wrap').addClass('d-none');
        $f.find('.js-invoice-amount').prop('required', false);

        var type = feeType();
        var p = previews[type] || {};
        unitFee = Number(p.unit_fee || 0);
        sessions = p.sessions || [];
        $f.find('.js-fee-hint').text(p.hint || '');
        $f.find('.js-session-picker').toggleClass('d-none', type !== 'per_session');
        if (type === 'per_session') {
            renderSessions();
        }

        var amount = Number(p.amount || 0);
        if (type === 'per_session') {
            amount = selectedSessionIds().length * unitFee;
            $f.find('.js-sessions-count').val(selectedSessionIds().length);
        } else {
            $f.find('.js-sessions-count').val(p.sessions_count || 0);
        }

        var discount = Number($f.find('.js-discount').val() || 0);
        var payable = Math.max(0, amount - discount);
        $f.find('.js-invoice-amount').val(Math.round(amount));
        $f.find('.js-payable-display').val(fmt(payable));
    }

    function recalcManual() {
        var $f = $form();
        var amount = Number($f.find('.js-invoice-amount').val() || 0);
        var discount = Number($f.find('.js-discount').val() || 0);
        $f.find('.js-payable-display').val(fmt(Math.max(0, amount - discount)));
    }

    function loadSuggest() {
        var $f = $form();
        var classId = $f.find('.js-invoice-class').val();
        if (!classId) {
            previews = null;
            applyPreview();
            return;
        }
        $.getJSON(suggestUrl, {
            class_id: classId,
            billing_month: $f.find('.js-invoice-month').val(),
            fee_type: feeType()
        }).done(function (res) {
            previews = res.previews || null;
            if (!$f.data('fee-touched') && res.default_fee_type) {
                $f.find('.js-fee-type-radio[value="' + res.default_fee_type + '"]').prop('checked', true)
                    .closest('label').addClass('active').siblings().removeClass('active');
            }
            applyPreview();
        });
    }

    $(document).on('change', '.js-invoice-class, .js-invoice-month', function () {
        loadSuggest();
    });
    $(document).on('change', '.js-fee-type-radio', function () {
        $form().data('fee-touched', true);
        applyPreview();
    });
    $(document).on('change', '.js-session-pick', applyPreview);
    $(document).on('input', '.js-discount, .js-invoice-amount', function () {
        if ($form().find('.js-invoice-class').val()) applyPreview();
        else recalcManual();
    });
    $(document).on('click', '.js-select-all-sessions', function () {
        $form().find('.js-session-pick').prop('checked', true);
        applyPreview();
    });
    $(document).on('click', '.js-clear-sessions', function () {
        $form().find('.js-session-pick').prop('checked', false);
        applyPreview();
    });
    $(document).on('submit', '.js-invoice-create-form', function () {
        if ($(this).find('.js-invoice-class').val() && feeType() === 'per_session' && selectedSessionIds().length === 0) {
            alert('Thu theo buổi: vui lòng chọn ít nhất một buổi.');
            return false;
        }
    });

    $('#modalCreate').on('shown.bs.modal', function () {
        initInvoiceSelect2();
        if ($form().find('.js-invoice-class').val()) loadSuggest();
    });

    // Lọc danh sách: Select2 lớp
    crmSelect2Ajax($('.js-filter-class'), classesUrl, {
        placeholder: 'Tất cả lớp',
        allowClear: true,
        dropdownParent: $(document.body)
    });
})();

(function () {
    var $btn = $('#bulkDeleteBtn');
    if (!$btn.length) return;

    function syncBulkDelete() {
        var n = $('.js-invoice-check:checked').length;
        var total = $('.js-invoice-check').length;
        $('#bulkSelectedCount').text(n);
        $btn.prop('disabled', n === 0);
        $btn.toggleClass('d-none', n === 0);
        $('#checkAllInvoices').prop('checked', total > 0 && n === total);
        $('#checkAllInvoices').prop('indeterminate', n > 0 && n < total);
    }

    $('#checkAllInvoices').on('change', function () {
        $('.js-invoice-check').prop('checked', this.checked);
        syncBulkDelete();
    });
    $(document).on('change', '.js-invoice-check', syncBulkDelete);
    syncBulkDelete();
})();
</script>
@endpush

    function applyPreview() {
        var $f = $form();
        var classId = $f.find('.js-invoice-class').val();
        if (!classId || !previews) {
            $f.find('.js-class-billing').addClass('d-none');
            $f.find('.js-manual-amount-wrap').removeClass('d-none');
            $f.find('.js-invoice-amount').prop('required', true);
            recalcManual();
            return;
        }

        $f.find('.js-class-billing').removeClass('d-none');
        $f.find('.js-manual-amount-wrap').addClass('d-none');
        $f.find('.js-invoice-amount').prop('required', false).val('');

        var type = feeType();
        var p = previews[type] || {};
        var count, gross, detail;

        if (type === 'per_session') {
            $f.find('.js-session-picker').removeClass('d-none');
            count = selectedSessionIds().length;
            gross = unitFee * count;
            detail = count > 0
                ? (fmt(unitFee) + ' × ' + count + ' buổi đã chọn')
                : ('Chọn buổi cần thu · ' + fmt(unitFee) + '/buổi');
        } else {
            $f.find('.js-session-picker').addClass('d-none');
            count = Number(p.sessions_count || 0);
            gross = Number(p.gross_amount || 0);
            detail = p.detail || '—';
        }

        $f.find('.js-fee-hint').text(detail);
        $f.find('.js-sessions-count').val(count);

        var discount = Math.max(0, Number($f.find('.js-discount').val() || 0));
        if (discount > gross) {
            discount = gross;
            $f.find('.js-discount').val(discount);
        }
        $f.find('.js-payable-display').val(fmt(Math.max(0, gross - discount)));
    }

    function recalcManual() {
        var $f = $form();
        var amount = Math.max(0, Number($f.find('.js-invoice-amount').val() || 0));
        var discount = Math.max(0, Number($f.find('.js-discount').val() || 0));
        if (discount > amount) {
            discount = amount;
            $f.find('.js-discount').val(discount);
        }
        // Không gắn lớp: amount là trước giảm
        $f.find('.js-payable-display').val(fmt(Math.max(0, amount - discount)));
    }

    function loadSuggest() {
        var $f = $form();
        var classId = $f.find('.js-invoice-class').val();
        var month = $f.find('.js-invoice-month').val();
        if (!classId) {
            previews = null;
            sessions = [];
            applyPreview();
            return;
        }
        $.get(suggestUrl, { class_id: classId, billing_month: month, fee_type: feeType() }, function (res) {
            previews = res.previews || {};
            unitFee = Number(res.unit_fee || 0);
            sessions = res.sessions || [];
            var def = res.default_fee_type || 'monthly';
            var $radio = $f.find('.js-fee-type-radio[value="' + def + '"]');
            if ($radio.length && !$f.data('fee-touched')) {
                $f.find('.js-fee-type-radio').prop('checked', false);
                $f.find('.js-fee-type-toggle label').removeClass('active');
                $radio.prop('checked', true).closest('label').addClass('active');
            }
            renderSessions();
            applyPreview();
        });
    }

    $(document).on('change', '.js-invoice-class, .js-invoice-month', function () {
        $form().data('fee-touched', false);
        loadSuggest();
    });
    $(document).on('change', '.js-fee-type-radio', function () {
        $form().data('fee-touched', true);
        applyPreview();
    });
    $(document).on('change', '.js-session-pick', applyPreview);
    $(document).on('input', '.js-discount, .js-invoice-amount', function () {
        if ($form().find('.js-invoice-class').val()) applyPreview();
        else recalcManual();
    });
    $(document).on('click', '.js-select-all-sessions', function () {
        $form().find('.js-session-pick').prop('checked', true);
        applyPreview();
    });
    $(document).on('click', '.js-clear-sessions', function () {
        $form().find('.js-session-pick').prop('checked', false);
        applyPreview();
    });
    $(document).on('submit', '.js-invoice-create-form', function () {
        if ($(this).find('.js-invoice-class').val() && feeType() === 'per_session' && selectedSessionIds().length === 0) {
            alert('Thu theo buổi: vui lòng chọn ít nhất một buổi.');
            return false;
        }
    });

    $('#modalCreate').on('shown.bs.modal', function () {
        if ($form().find('.js-invoice-class').val()) loadSuggest();
    });
})();

(function () {
    var $btn = $('#bulkDeleteBtn');
    if (!$btn.length) return;

    function syncBulkDelete() {
        var n = $('.js-invoice-check:checked').length;
        var total = $('.js-invoice-check').length;
        $('#bulkSelectedCount').text(n);
        $btn.prop('disabled', n === 0);
        $btn.toggleClass('d-none', n === 0);
        $('#checkAllInvoices').prop('checked', total > 0 && n === total);
        $('#checkAllInvoices').prop('indeterminate', n > 0 && n < total);
    }

    $('#checkAllInvoices').on('change', function () {
        $('.js-invoice-check').prop('checked', this.checked);
        syncBulkDelete();
    });
    $(document).on('change', '.js-invoice-check', syncBulkDelete);
    syncBulkDelete();
})();
</script>
@endpush
