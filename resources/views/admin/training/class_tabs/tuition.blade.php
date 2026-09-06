<div class="row mb-3">
    <div class="col-lg-5 mb-3">
        <div class="border rounded p-3 h-100">
            <h6 class="font-weight-bold mb-2">Tạo hóa đơn hàng loạt</h6>
            <p class="small text-muted mb-2">
                Tạo HĐ cho toàn bộ học viên trong lớp theo đơn giá
                <strong>{{ $class->tuitionDisplay() }}</strong>.
                @if($suggestion)
                    <br>Gợi ý tháng {{ $billingMonth }}:
                    @if(($suggestion['fee_type'] ?? '') === 'per_session')
                        {{ number_format($suggestion['unit_fee'], 0, ',', '.') }} × {{ $suggestion['sessions_count'] ?? 0 }} buổi =
                    @endif
                    <strong>{{ number_format($suggestion['amount'] ?? 0, 0, ',', '.') }} đ</strong>/HV
                @endif
            </p>
            <form method="GET" action="{{ route('admin.classes.show', $class) }}" class="mb-2">
                <input type="hidden" name="tab" value="tuition">
                <label class="small mb-1">Tháng thu</label>
                <input type="month" name="billing_month" class="form-control form-control-sm" value="{{ $billingMonth }}" onchange="this.form.submit()">
            </form>
            <form method="POST" action="{{ route('admin.classes.invoices.generate', $class) }}">
                @csrf
                <input type="hidden" name="billing_month" value="{{ $billingMonth }}">
                <div class="form-check mb-3">
                    <input type="hidden" name="skip_existing" value="0">
                    <input type="checkbox" class="form-check-input" name="skip_existing" value="1" id="skipExisting" checked>
                    <label class="form-check-label small" for="skipExisting">Bỏ qua học viên đã có hóa đơn tháng này</label>
                </div>
                <button class="btn btn-success btn-sm btn-block" {{ $classStudents->isEmpty() ? 'disabled' : '' }}>
                    <i class="bi bi-receipt"></i> Tạo HĐ cho {{ $classStudents->count() }} học viên
                </button>
            </form>

            <hr>
            <h6 class="font-weight-bold mb-2">Tạo hóa đơn 1 học viên</h6>
            @if($classStudents->isEmpty())
                <p class="text-muted small mb-0">Chưa có học viên trong lớp.</p>
            @else
                <form method="POST" action="{{ route('admin.classes.invoices.store', $class) }}">
                    @csrf
                    <div class="form-group">
                        <label>Học viên *</label>
                        <select name="student_id" class="form-control" required>
                            @foreach($classStudents as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tháng</label>
                        <input type="month" name="billing_month" class="form-control" value="{{ $billingMonth }}">
                    </div>
                    <div class="form-group">
                        <label>Số tiền <small class="text-muted">(để trống = gợi ý)</small></label>
                        <input type="number" name="amount" class="form-control" min="0" placeholder="{{ (int) ($suggestion['amount'] ?? 0) }}">
                    </div>
                    <div class="form-group">
                        <label>Hạn thanh toán</label>
                        <input type="date" name="due_date" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                    </div>
                    <button class="btn btn-primary btn-sm btn-block">Tạo hóa đơn</button>
                </form>
            @endif
        </div>
    </div>

    <div class="col-lg-7 mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Hóa đơn của lớp</strong>
            <a href="{{ route('admin.invoices.index') }}" class="small">Xem tất cả hóa đơn →</a>
        </div>
        <div class="table-responsive border rounded">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Học viên</th>
                    <th>Tháng</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->student?->name }}</td>
                        <td>
                            {{ $invoice->billing_month }}
                            @if($invoice->fee_type === 'per_session' && $invoice->sessions_count)
                                <br><small class="text-muted">{{ $invoice->sessions_count }} buổi</small>
                            @endif
                        </td>
                        <td>@vnd($invoice->amount)
                            <div class="small text-muted">Đã thu @vnd($invoice->paid_amount) · Còn @vnd($invoice->remaining_amount)</div>
                        </td>
                        <td>
                            <span class="badge lead-status {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-primary">Thu / Chi tiết</a>
                            @canPerm('finance.invoices.manage')
                            <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa HĐ?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Chưa có hóa đơn cho lớp này.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($invoices, 'links'))
            <div class="mt-2">{{ $invoices->links() }}</div>
        @endif
    </div>
</div>
