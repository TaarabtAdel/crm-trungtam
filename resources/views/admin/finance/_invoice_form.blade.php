@php
    $isCreate = empty($invoice?->id);
    $defaultMonth = old('billing_month', $invoice->billing_month ?? now()->format('Y-m'));
@endphp

<div class="form-group"><label>Học viên *</label>
    <select name="student_id" class="form-control js-invoice-student" required data-placeholder="Tìm học viên theo tên, SĐT...">
        <option value=""></option>
        @php
            $prefillStudent = $selectedStudent ?? null;
            if (! $prefillStudent && ! empty($invoice?->student_id)) {
                $prefillStudent = $invoice->student ?? null;
            }
            if (! $prefillStudent && old('student_id') && ! empty($students)) {
                $prefillStudent = $students->firstWhere('id', (int) old('student_id'));
            }
        @endphp
        @if($prefillStudent)
            <option value="{{ $prefillStudent->id }}" selected>
                {{ $prefillStudent->name }}@if($prefillStudent->phone || $prefillStudent->parent_phone) · {{ $prefillStudent->phone ?: $prefillStudent->parent_phone }}@endif
            </option>
        @endif
    </select>
</div>
<div class="form-group"><label>Lớp</label>
    <select name="class_id" class="form-control js-invoice-class" data-placeholder="Tìm lớp (để trống = nhập tay)">
        <option value=""></option>
        @php
            $prefillClass = $selectedClass ?? null;
            if (! $prefillClass && ! empty($invoice?->courseClass)) {
                $prefillClass = $invoice->courseClass;
            }
        @endphp
        @if($prefillClass)
            <option value="{{ $prefillClass->id }}"
                    data-type="{{ $prefillClass->isPerSessionFee() ? 'per_session' : 'monthly' }}"
                    data-fee="{{ (float) $prefillClass->tuition_fee }}"
                    selected>
                {{ $prefillClass->name }} ({{ $prefillClass->tuitionDisplay() }})
            </option>
        @endif
    </select>
    <small class="text-muted">Không chọn lớp → nhập số tiền thủ công.</small>
</div>
@if(!empty($salesUsers))
<div class="form-group"><label>Sales phụ trách (hoa hồng)</label>
    <select name="sales_id" class="form-control">
        <option value="">-- Không --</option>
        @foreach($salesUsers as $u)
            <option value="{{ $u->id }}" @selected(old('sales_id', $invoice->sales_id ?? '')==$u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
</div>
@endif

<div class="form-group">
    <label>Tháng tham chiếu</label>
    <input type="month" name="billing_month" class="form-control js-invoice-month" value="{{ $defaultMonth }}">
    <small class="text-muted">Dùng để lấy buổi theo lịch khi thu tháng / buổi.</small>
</div>

<div class="js-class-billing {{ old('class_id', $invoice->class_id ?? '') ? '' : 'd-none' }}">
    <div class="form-group mb-2">
        <label class="small font-weight-bold mb-1">Hình thức thu *</label>
        <div class="btn-group btn-group-toggle d-flex flex-wrap js-fee-type-toggle" data-toggle="buttons">
            @foreach(\App\Models\CourseClass::feeTypeOptions() as $k => $label)
                <label class="btn btn-outline-primary btn-sm flex-fill mb-1 {{ old('fee_type', 'monthly') === $k ? 'active' : '' }}">
                    <input type="radio" name="fee_type" class="js-fee-type-radio" value="{{ $k }}" autocomplete="off"
                           {{ old('fee_type', 'monthly') === $k ? 'checked' : '' }}> {{ $label }}
                </label>
            @endforeach
        </div>
    </div>

    <div class="alert alert-light border small py-2 mb-2 js-fee-hint">Chọn lớp để tải gợi ý.</div>

    <div class="border rounded p-2 mb-3 js-session-picker d-none">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="small font-weight-bold mb-0">Chọn buổi cần thu</label>
            <div>
                <button type="button" class="btn btn-link btn-sm p-0 mr-2 js-select-all-sessions">Chọn tất cả</button>
                <button type="button" class="btn btn-link btn-sm p-0 js-clear-sessions">Bỏ chọn</button>
            </div>
        </div>
        <div class="js-session-list" style="max-height:160px;overflow:auto"></div>
    </div>

    <input type="hidden" name="sessions_count" class="js-sessions-count" value="{{ old('sessions_count', $invoice->sessions_count ?? 0) }}">
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label>Giảm giá (đ)</label>
        <input type="number" name="discount_amount" class="form-control js-discount" value="{{ old('discount_amount', $invoice->discount_amount ?? 0) }}" min="0">
    </div>
    <div class="form-group col-md-4 js-manual-amount-wrap {{ old('class_id', $invoice->class_id ?? '') ? 'd-none' : '' }}">
        <label>Số tiền *</label>
        <input type="number" name="amount" class="form-control js-invoice-amount" value="{{ old('amount', $invoice->amount ?? 0) }}" min="0" {{ old('class_id', $invoice->class_id ?? '') ? '' : 'required' }}>
    </div>
    <div class="form-group col-md-4">
        <label>Thành tiền</label>
        <input type="text" class="form-control js-payable-display" readonly value="{{ number_format((float) old('amount', $invoice->amount ?? 0), 0, ',', '.') }} đ">
    </div>
</div>
<div class="form-group">
    <label>Lý do giảm</label>
    <input type="text" name="discount_reason" class="form-control" value="{{ old('discount_reason', $invoice->discount_reason ?? '') }}" placeholder="VD: Ưu đãi anh/chị em, học thử…">
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label>Số kỳ trả góp</label>
        <input type="number" name="installment_count" class="form-control" value="{{ old('installment_count', $invoice->installment_count ?? 1) }}" min="1" max="24">
    </div>
    <div class="form-group col-md-6">
        <label>Hạn thanh toán</label>
        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($invoice->due_date ?? null)->format('Y-m-d') ?: now()->endOfMonth()->toDateString()) }}">
    </div>
</div>

@if($isCreate)
<div class="form-group"><label>Trạng thái ban đầu</label>
    <select name="status" class="form-control">
        <option value="unpaid">Chưa thu</option>
        <option value="paid">Thu đủ ngay</option>
        <option value="cancelled">Hủy</option>
    </select>
</div>
@endif
<div class="form-group mb-0"><label>Ghi chú</label><textarea name="note" class="form-control" rows="2">{{ old('note', $invoice->note ?? '') }}</textarea></div>
