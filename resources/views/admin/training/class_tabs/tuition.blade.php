@php
    $billingPreviews = $billingPreviews ?? [];
    $defaultFeeType = $defaultFeeType ?? ($class->isPerSessionFee() ? 'per_session' : 'monthly');
    $suggestion = $suggestion ?? ($billingPreviews[$defaultFeeType] ?? null);
    $selectableSessions = $selectableSessions ?? collect();
    $unitFee = (float) ($unitFee ?? $class->tuition_fee);
    $billableSessionsByStudent = $billableSessionsByStudent ?? [];
    $periodFrom = $periodFrom ?? now()->startOfMonth()->toDateString();
    $periodTo = $periodTo ?? now()->endOfMonth()->toDateString();
    $sessionCountsByStudent = $sessionCountsByStudent ?? [];
    $periodSessionCount = (int) ($periodSessionCount ?? 0);
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Hóa đơn của lớp</h5>
            <small class="text-muted">
                Đơn giá: <strong>{{ number_format($unitFee, 0, ',', '.') }} đ/buổi</strong>
                · {{ $class->tuitionDisplay() }}
            </small>
        </div>
        <div class="d-flex align-items-center flex-wrap" style="gap:.5rem">
            <form method="GET" action="{{ route('admin.classes.show', $class) }}" class="mb-0 d-flex align-items-center flex-wrap" style="gap:.35rem" id="formTuitionPeriod">
                <input type="hidden" name="tab" value="tuition">
                <label class="small mb-0 text-muted">Tháng (tham chiếu HĐ)</label>
                <input type="month" name="billing_month" class="form-control form-control-sm js-billing-month-field" style="width:150px" value="{{ $billingMonth }}">
                <label class="small mb-0 text-muted">Kỳ thu từ</label>
                <input type="date" name="period_from" class="form-control form-control-sm js-period-from-field" value="{{ $periodFrom }}">
                <label class="small mb-0 text-muted">đến</label>
                <input type="date" name="period_to" class="form-control form-control-sm js-period-to-field" value="{{ $periodTo }}">
                <button type="submit" class="btn btn-sm btn-outline-primary">Áp dụng</button>
            </form>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Tất cả HĐ</a>
            @canPerm('finance.invoices.manage')
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalClassTuitionCreate" {{ $classStudents->isEmpty() ? 'disabled' : '' }}>
                <i class="bi bi-receipt"></i> Tạo hóa đơn học phí
            </button>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom border-bottom">
        <p class="small text-muted mb-0">
            <strong>Kỳ thu</strong> có thể trải nhiều tháng (vd. 15/09–15/10).
            Hình thức <em>Theo tháng</em>: đơn giá × số buổi trên TKB trong kỳ;
            mỗi học viên chỉ tính buổi từ <strong>ngày vào học</strong> (tab Học viên — có thể sửa).
            Hiện có <strong>{{ $periodSessionCount }}</strong> buổi lịch trong kỳ đã chọn.
        </p>
    </div>
    <div class="card-body-custom p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Học viên</th>
                    <th>Hình thức</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="classInvoicesBody">
                @forelse($invoices as $invoice)
                    <tr data-invoice-id="{{ $invoice->id }}">
                        <td>{{ $invoice->student?->name }}</td>
                        <td>
                            <div>{{ $invoice->feeTypeLabel() }}</div>
                            <div class="small text-muted">
                                @if($invoice->billing_month){{ $invoice->billing_month }}@endif
                                @if($invoice->sessions_count)
                                    · {{ $invoice->sessions_count }} buổi
                                @endif
                            </div>
                            @if(($invoice->discount_amount ?? 0) > 0)
                                <div class="small text-success">Giảm @vnd($invoice->discount_amount)
                                    @if($invoice->discount_reason) — {{ $invoice->discount_reason }}@endif
                                </div>
                            @endif
                        </td>
                        <td>@vnd($invoice->amount)
                            <div class="small text-muted">Đã thu @vnd($invoice->paid_amount) · Còn @vnd($invoice->remaining_amount)</div>
                        </td>
                        <td>
                            <span class="badge lead-status {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                        </td>
                        <td class="text-nowrap text-right">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-primary">Thu / Chi tiết</a>
                            @canPerm('finance.invoices.manage')
                                @if($invoice->canBeDeleted())
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger js-ajax-delete-invoice"
                                        data-url="{{ route('admin.invoices.destroy', $invoice) }}"
                                        title="Xóa">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr class="js-invoice-empty"><td colspan="5" class="text-center text-muted py-4">Chưa có hóa đơn cho lớp này.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($invoices, 'links'))
            <div class="p-3">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>

@canPerm('finance.invoices.manage')
<div class="modal fade" id="modalClassTuitionCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tạo hóa đơn học phí</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Đơn giá: <strong>{{ number_format($unitFee, 0, ',', '.') }} đ/buổi</strong>
                    · Tháng tham chiếu: <strong>{{ $billingMonth }}</strong>
                    <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'tuition', 'billing_month' => $billingMonth]) }}" class="ml-1">Đổi tháng</a>
                </p>

                <div class="form-group mb-2">
                    <label class="small font-weight-bold mb-1">Hình thức thu *</label>
                    <div class="btn-group btn-group-toggle d-flex flex-wrap" data-toggle="buttons" id="feeTypeToggle">
                        @foreach(\App\Models\CourseClass::feeTypeOptions() as $k => $label)
                            <label class="btn btn-outline-primary btn-sm flex-fill mb-1 {{ $defaultFeeType === $k ? 'active' : '' }}">
                                <input type="radio" name="fee_type_ui" value="{{ $k }}" autocomplete="off" {{ $defaultFeeType === $k ? 'checked' : '' }}> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="alert alert-light border small py-2 mb-2" id="tuitionPreviewBox">
                    <div id="tuitionPreviewDetail">{{ data_get($suggestion, 'detail', '—') }}</div>
                    <div class="mt-1">
                        Số buổi: <strong id="tuitionPreviewSessions">{{ data_get($suggestion, 'sessions_count', 0) }}</strong>
                        · Trước giảm: <strong id="tuitionPreviewGross">{{ number_format((float) data_get($suggestion, 'gross_amount', 0), 0, ',', '.') }} đ</strong>
                    </div>
                </div>

                <div id="sessionPickerWrap" class="border rounded p-2 mb-3 {{ $defaultFeeType === 'per_session' ? '' : 'd-none' }}">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap:.35rem">
                        <label class="small font-weight-bold mb-0">Chọn buổi cần thu</label>
                        <div class="d-flex align-items-center flex-wrap" style="gap:.25rem">
                            <button type="button" class="btn btn-outline-success btn-sm py-0 js-pick-attendance-single" title="Tab 1 học viên">
                                <i class="bi bi-clipboard-check"></i> Gợi ý từ điểm danh
                            </button>
                            <button type="button" class="btn btn-link btn-sm p-0" id="selectAllSessions">Chọn tất cả</button>
                            <button type="button" class="btn btn-link btn-sm p-0" id="clearAllSessions">Bỏ chọn</button>
                        </div>
                    </div>
                    <p class="small text-muted mb-2 mb-md-2">
                        Gợi ý: buổi có điểm danh <strong>Có mặt</strong> hoặc <strong>Muộn</strong> trong kỳ thu đang chọn,
                        chưa nằm trong hóa đơn thu theo buổi. <strong>Vắng</strong> / <strong>Vắng có phép</strong> không tính.
                    </p>
                    @if($selectableSessions->isEmpty())
                        <p class="small text-muted mb-0">Tháng này chưa có buổi trên lịch (hoặc toàn bộ đã Hủy). Hãy tạo TKB hoặc đổi tháng.</p>
                    @else
                        <div class="tuition-session-list" style="max-height:180px;overflow:auto">
                            @foreach($selectableSessions as $s)
                                @php
                                    $time = trim(
                                        ($s->start_time ? substr((string) $s->start_time, 0, 5) : '')
                                        .(($s->start_time || $s->end_time) ? '–' : '')
                                        .($s->end_time ? substr((string) $s->end_time, 0, 5) : ''),
                                        '–'
                                    );
                                @endphp
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input js-session-pick" id="sess{{ $s->id }}" value="{{ $s->id }}">
                                    <label class="custom-control-label small" for="sess{{ $s->id }}">
                                        {{ optional($s->session_date)->format('d/m/Y') ?: '—' }}
                                        @if($time) · {{ $time }}@endif
                                        <span class="text-muted">· {{ $s->statusLabel() }}</span>
                                        · {{ number_format($unitFee, 0, ',', '.') }} đ
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-batch" data-toggle="tab" href="#paneBatch" role="tab">Hàng loạt</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-single" data-toggle="tab" href="#paneSingle" role="tab">1 học viên</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="paneBatch" role="tabpanel">
                        <form method="POST" action="{{ route('admin.classes.invoices.generate', $class) }}" class="js-tuition-form" id="formTuitionBatch">
                            @csrf
                            <input type="hidden" name="fee_type" class="js-fee-type" value="{{ $defaultFeeType }}">
                            <input type="hidden" name="billing_month" class="js-billing-month" value="{{ $billingMonth }}">
                            <input type="hidden" name="period_from" class="js-period-from" value="{{ $periodFrom }}">
                            <input type="hidden" name="period_to" class="js-period-to" value="{{ $periodTo }}">
                            <input type="hidden" name="sessions_count" class="js-sessions-count" value="{{ data_get($suggestion, 'sessions_count', 0) }}">
                            <div class="js-session-ids"></div>

                            <div class="form-check mb-2 js-pro-rata-wrap">
                                <input type="hidden" name="pro_rata_enrollment" value="0">
                                <input type="checkbox" class="form-check-input" name="pro_rata_enrollment" value="1" id="proRataBatch" checked>
                                <label class="form-check-label" for="proRataBatch">
                                    Theo tháng: mỗi HV chỉ tính buổi từ <strong>ngày vào học</strong> trong kỳ
                                </label>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Giảm giá (đ)</label>
                                    <input type="number" name="discount_amount" class="form-control js-discount" min="0" value="0">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Thành tiền / HV</label>
                                    <input type="text" class="form-control js-payable-display" readonly value="{{ number_format((float) data_get($suggestion, 'amount', 0), 0, ',', '.') }} đ">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Lý do giảm</label>
                                <input type="text" name="discount_reason" class="form-control" placeholder="VD: Ưu đãi anh/chị em, học thử…">
                            </div>
                            <div class="form-group">
                                <label>Hạn thanh toán</label>
                                <input type="date" name="due_date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                            </div>
                            <div class="form-check mb-2">
                                <input type="hidden" name="skip_existing" value="0">
                                <input type="checkbox" class="form-check-input" name="skip_existing" value="1" id="skipExisting" checked>
                                <label class="form-check-label" for="skipExisting">Bỏ qua học viên đã có hóa đơn (cùng tháng / cùng khóa)</label>
                            </div>
                            <div class="form-check mb-3 js-batch-attendance-wrap d-none">
                                <input type="hidden" name="bill_from_attendance" value="0">
                                <input type="checkbox" class="form-check-input" name="bill_from_attendance" value="1" id="billFromAttendance">
                                <label class="form-check-label" for="billFromAttendance">
                                    Thu theo buổi <strong>theo điểm danh từng học viên</strong> (mỗi HĐ khác số buổi; bỏ qua danh sách chọn buổi chung phía trên)
                                </label>
                            </div>
                            <button class="btn btn-success" {{ $classStudents->isEmpty() ? 'disabled' : '' }}>
                                <i class="bi bi-receipt"></i> Tạo HĐ cho {{ $classStudents->count() }} học viên
                            </button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="paneSingle" role="tabpanel">
                        @if($classStudents->isEmpty())
                            <p class="text-muted mb-0">Chưa có học viên trong lớp.</p>
                        @else
                            <form method="POST" action="{{ route('admin.classes.invoices.store', $class) }}" class="js-tuition-form" id="formTuitionSingle">
                                @csrf
                                <input type="hidden" name="fee_type" class="js-fee-type" value="{{ $defaultFeeType }}">
                                <input type="hidden" name="billing_month" class="js-billing-month" value="{{ $billingMonth }}">
                                <input type="hidden" name="period_from" class="js-period-from" value="{{ $periodFrom }}">
                                <input type="hidden" name="period_to" class="js-period-to" value="{{ $periodTo }}">
                                <input type="hidden" name="sessions_count" class="js-sessions-count" value="{{ data_get($suggestion, 'sessions_count', 0) }}">
                                <div class="js-session-ids"></div>

                                <div class="form-check mb-2 js-pro-rata-wrap">
                                    <input type="hidden" name="pro_rata_enrollment" value="0">
                                    <input type="checkbox" class="form-check-input" name="pro_rata_enrollment" value="1" id="proRataSingle" checked>
                                    <label class="form-check-label" for="proRataSingle">
                                        Theo tháng: chỉ tính buổi từ ngày vào học trong kỳ
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>Học viên *</label>
                                    <select name="student_id" class="form-control js-tuition-student" required>
                                        @foreach($classStudents as $s)
                                            @php
                                                $billCount = count($billableSessionsByStudent[$s->id] ?? []);
                                                $periodCount = (int) ($sessionCountsByStudent[$s->id] ?? 0);
                                            @endphp
                                            <option value="{{ $s->id }}" data-period-sessions="{{ $periodCount }}">
                                                {{ $s->name }}
                                                @if($periodCount > 0)
                                                    — {{ $periodCount }} buổi trong kỳ
                                                @endif
                                                @if($billCount > 0)
                                                    · {{ $billCount }} buổi gợi ý (điểm danh)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label>Giảm giá (đ)</label>
                                        <input type="number" name="discount_amount" class="form-control js-discount" min="0" value="0">
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label>Thành tiền</label>
                                        <input type="text" class="form-control js-payable-display" readonly value="{{ number_format((float) data_get($suggestion, 'amount', 0), 0, ',', '.') }} đ">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Lý do giảm</label>
                                    <input type="text" name="discount_reason" class="form-control" placeholder="VD: Ưu đãi anh/chị em, học thử…">
                                </div>
                                <div class="form-group">
                                    <label>Hạn thanh toán</label>
                                    <input type="date" name="due_date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                                </div>
                                <button class="btn btn-primary">Tạo hóa đơn</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endcanPerm

@push('scripts')
<script>
(function () {
    var previews = @json($billingPreviews);
    var billableByStudent = @json($billableSessionsByStudent);
    var sessionCountsByStudent = @json($sessionCountsByStudent);
    var periodSessionCount = {{ (int) $periodSessionCount }};
    var unitFee = {{ (float) $unitFee }};
    var fmt = function (n) {
        return Math.round(Number(n) || 0).toLocaleString('vi-VN') + ' đ';
    };

    function selectedSessionIds() {
        return $('#modalClassTuitionCreate .js-session-pick:checked').map(function () { return $(this).val(); }).get();
    }

    function pickSessionsForStudent(studentId) {
        var ids = billableByStudent[String(studentId)] || billableByStudent[studentId] || [];
        $('#modalClassTuitionCreate .js-session-pick').prop('checked', false);
        ids.forEach(function (id) {
            $('#sess' + id).prop('checked', true);
        });
        if (ids.length === 0) {
            alert('Không có buổi gợi ý: chưa điểm danh Có mặt/Muộn trong tháng, hoặc các buổi đó đã thu.');
        }
        applyPreview();
    }

    function batchUsesAttendance() {
        return $('#billFromAttendance').is(':checked');
    }

    function toggleBatchAttendanceUi() {
        var perSession = currentFeeType() === 'per_session';
        $('.js-batch-attendance-wrap').toggleClass('d-none', !perSession);
        if (!perSession) {
            $('#billFromAttendance').prop('checked', false);
        }
        var dim = perSession && batchUsesAttendance();
        $('#sessionPickerWrap').toggleClass('opacity-50', dim);
        $('#modalClassTuitionCreate .js-session-pick').prop('disabled', dim);
    }

    function syncSessionIdsToForms() {
        var ids = selectedSessionIds();
        $('#modalClassTuitionCreate .js-tuition-form').each(function () {
            var $box = $(this).find('.js-session-ids');
            $box.empty();
            ids.forEach(function (id) {
                $box.append($('<input>', { type: 'hidden', name: 'session_ids[]', value: id }));
            });
        });
    }

    function currentFeeType() {
        return $('#feeTypeToggle input[name="fee_type_ui"]:checked').val() || @json($defaultFeeType);
    }

    function monthlySessionsForActiveForm() {
        var feeType = currentFeeType();
        if (feeType !== 'monthly') {
            return Number((previews.monthly || {}).sessions_count || 0);
        }
        var $single = $('#formTuitionSingle');
        if ($('#paneSingle').hasClass('active') && $single.length) {
            var proRata = $single.find('[name="pro_rata_enrollment"][value="1"]').is(':checked');
            var sid = $single.find('.js-tuition-student').val();
            if (proRata && sid) {
                var n = sessionCountsByStudent[String(sid)] ?? sessionCountsByStudent[sid];
                if (n !== undefined) {
                    return Number(n);
                }
            }
        }
        if ($('#formTuitionBatch').length && $('#paneBatch').hasClass('active')) {
            var batchPro = $('#proRataBatch').is(':checked');
            if (!batchPro) {
                return periodSessionCount;
            }
        }
        return periodSessionCount;
    }

    function applyPreview() {
        var feeType = currentFeeType();
        var p = previews[feeType] || previews.monthly || {};
        var sessions, gross, detail;

        if (feeType === 'per_session') {
            sessions = selectedSessionIds().length;
            gross = unitFee * sessions;
            detail = sessions > 0
                ? (unitFee.toLocaleString('vi-VN') + ' đ × ' + sessions + ' buổi đã chọn')
                : ('Chọn buổi cần thu · ' + unitFee.toLocaleString('vi-VN') + ' đ/buổi');
            $('#sessionPickerWrap').removeClass('d-none');
            $('.js-pro-rata-wrap').addClass('d-none');
        } else if (feeType === 'monthly') {
            sessions = monthlySessionsForActiveForm();
            gross = unitFee * sessions;
            detail = unitFee.toLocaleString('vi-VN') + ' đ × ' + sessions + ' buổi (theo kỳ & ngày vào học nếu bật)';
            $('#sessionPickerWrap').addClass('d-none');
            $('.js-pro-rata-wrap').removeClass('d-none');
        } else {
            sessions = Number(p.sessions_count || 0);
            gross = Number(p.gross_amount || 0);
            detail = p.detail || '—';
            $('#sessionPickerWrap').addClass('d-none');
            $('.js-pro-rata-wrap').addClass('d-none');
        }

        $('#tuitionPreviewDetail').text(detail);
        $('#tuitionPreviewSessions').text(sessions);
        $('#tuitionPreviewGross').text(fmt(gross));

        $('#modalClassTuitionCreate .js-tuition-form').each(function () {
            var $form = $(this);
            $form.find('.js-fee-type').val(feeType);
            $form.find('.js-sessions-count').val(sessions);
            recalcForm($form, gross);
        });
        syncSessionIdsToForms();
    }

    function recalcForm($form, gross) {
        gross = Number(gross);
        if (isNaN(gross)) {
            var feeType = currentFeeType();
            if (feeType === 'per_session') {
                gross = unitFee * selectedSessionIds().length;
            } else {
                gross = Number((previews[feeType] || {}).gross_amount || 0);
            }
        }
        var discount = Math.max(0, Number($form.find('.js-discount').val() || 0));
        if (discount > gross) {
            discount = gross;
            $form.find('.js-discount').val(discount);
        }
        var payable = Math.max(0, gross - discount);
        $form.find('.js-payable-display').val(fmt(payable));
    }

    $('#feeTypeToggle input[name="fee_type_ui"]').on('change', function () {
        applyPreview();
        toggleBatchAttendanceUi();
    });
    $('#billFromAttendance').on('change', toggleBatchAttendanceUi);
    $('.js-pick-attendance-single').on('click', function () {
        if (currentFeeType() !== 'per_session') {
            alert('Chọn hình thức thu Theo buổi trước.');
            return;
        }
        var $sel = $('#formTuitionSingle .js-tuition-student');
        if (!$sel.length) {
            alert('Dùng tab 1 học viên để gợi ý theo từng em.');
            return;
        }
        $('#tab-single').tab('show');
        pickSessionsForStudent($sel.val());
    });
    $(document).on('change', '#formTuitionSingle .js-tuition-student', function () {
        applyPreview();
    });
    $(document).on('change', '#proRataBatch, #proRataSingle', applyPreview);
    $('a[data-toggle="tab"]').on('shown.bs.tab', applyPreview);
    $('#formTuitionPeriod input[name="billing_month"]').on('change', function () {
        var ym = $(this).val();
        if (!ym || ym.indexOf('-') < 0) {
            return;
        }
        var parts = ym.split('-');
        var y = parseInt(parts[0], 10);
        var m = parseInt(parts[1], 10);
        var last = new Date(y, m, 0).getDate();
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        $('#formTuitionPeriod .js-period-from-field').val(y + '-' + pad(m) + '-01');
        $('#formTuitionPeriod .js-period-to-field').val(y + '-' + pad(m) + '-' + pad(last));
    });
    $(document).on('change', '#modalClassTuitionCreate .js-session-pick', applyPreview);
    $(document).on('input', '#modalClassTuitionCreate .js-discount', applyPreview);
    $('#selectAllSessions').on('click', function () {
        $('#modalClassTuitionCreate .js-session-pick').prop('checked', true);
        applyPreview();
    });
    $('#clearAllSessions').on('click', function () {
        $('#modalClassTuitionCreate .js-session-pick').prop('checked', false);
        applyPreview();
    });

    $('#modalClassTuitionCreate .js-tuition-form').on('submit', function () {
        var $form = $(this);
        syncSessionIdsToForms();
        if (currentFeeType() === 'per_session') {
            if ($form.is('#formTuitionBatch') && batchUsesAttendance()) {
                return true;
            }
            if (selectedSessionIds().length === 0) {
                alert('Thu theo buổi: vui lòng chọn ít nhất một buổi (hoặc bấm Gợi ý từ điểm danh).');
                return false;
            }
        }
    });

    function syncPeriodFieldsToModal() {
        var from = $('#formTuitionPeriod .js-period-from-field').val() || @json($periodFrom);
        var to = $('#formTuitionPeriod .js-period-to-field').val() || @json($periodTo);
        var month = $('#formTuitionPeriod .js-billing-month-field').val() || @json($billingMonth);
        $('#modalClassTuitionCreate .js-period-from').val(from);
        $('#modalClassTuitionCreate .js-period-to').val(to);
        $('#modalClassTuitionCreate .js-billing-month').val(month);
    }

    $('#modalClassTuitionCreate').on('shown.bs.modal', function () {
        syncPeriodFieldsToModal();
        applyPreview();
        toggleBatchAttendanceUi();
    });
    applyPreview();
    toggleBatchAttendanceUi();

    $(document).on('click', '.js-ajax-delete-invoice', function () {
        var $btn = $(this);
        var url = $btn.data('url');
        var $row = $btn.closest('tr');
        if (!url || !confirm('Xóa hóa đơn này?')) {
            return;
        }
        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                _method: 'DELETE'
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).done(function () {
            $row.fadeOut(200, function () {
                $(this).remove();
                var $body = $('#classInvoicesBody');
                if ($body.find('tr[data-invoice-id]').length === 0 && $body.find('.js-invoice-empty').length === 0) {
                    $body.append('<tr class="js-invoice-empty"><td colspan="5" class="text-center text-muted py-4">Chưa có hóa đơn cho lớp này.</td></tr>');
                }
            });
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Không xóa được hóa đơn.';
            alert(msg);
            $btn.prop('disabled', false);
        });
    });
})();
</script>
@endpush
