<div class="form-group"><label>Học viên *</label>
    <select name="student_id" class="form-control" required>
        @foreach($students as $s)
            <option value="{{ $s->id }}" @selected(old('student_id', $invoice->student_id ?? '')==$s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Lớp</label>
    <select name="class_id" class="form-control js-invoice-class">
        <option value="">-- Không --</option>
        @foreach($classes as $c)
            <option value="{{ $c->id }}"
                data-type="{{ $c->tuition_type ?? 'monthly' }}"
                data-fee="{{ (float) $c->tuition_fee }}"
                @selected(old('class_id', $invoice->class_id ?? '')==$c->id)>
                {{ $c->name }} ({{ $c->tuitionDisplay() }})
            </option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Tháng (YYYY-MM)</label>
    <input name="billing_month" class="form-control js-invoice-month" value="{{ old('billing_month', $invoice->billing_month ?? now()->format('Y-m')) }}" placeholder="2026-09">
</div>
<div class="alert alert-light border js-fee-hint py-2" style="display:none"></div>
<input type="hidden" name="fee_type" class="js-fee-type" value="{{ old('fee_type', $invoice->fee_type ?? '') }}">
<div class="form-row">
    <div class="form-group col-md-6">
        <label>Số buổi <small class="text-muted">(nếu theo buổi)</small></label>
        <input type="number" name="sessions_count" class="form-control js-sessions-count" value="{{ old('sessions_count', $invoice->sessions_count ?? '') }}" min="0">
    </div>
    <div class="form-group col-md-6">
        <label>Số tiền *</label>
        <input type="number" name="amount" class="form-control js-invoice-amount" value="{{ old('amount', $invoice->amount ?? 0) }}" min="0" required>
    </div>
</div>
<button type="button" class="btn btn-sm btn-outline-primary mb-3 js-suggest-amount">Gợi ý số tiền từ lớp</button>
<div class="form-group"><label>Hạn thanh toán</label><input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($invoice->due_date ?? null)->format('Y-m-d')) }}"></div>
<div class="form-group"><label>Trạng thái</label>
    <select name="status" class="form-control">
        @foreach(['unpaid'=>'Chưa thu','paid'=>'Đã thu','cancelled'=>'Hủy'] as $k=>$v)
            <option value="{{ $k }}" @selected(old('status', $invoice->status ?? 'unpaid')===$k)>{{ $v }}</option>
        @endforeach
    </select>
</div>
<div class="form-group"><label>Ghi chú</label><textarea name="note" class="form-control" rows="2">{{ old('note', $invoice->note ?? '') }}</textarea></div>
