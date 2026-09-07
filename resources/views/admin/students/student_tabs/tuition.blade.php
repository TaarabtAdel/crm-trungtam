@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="row mb-3">
    <div class="col-md-4 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Tổng phát sinh</div>
            <div class="stat-value" style="font-size:1.2rem">{{ $fmt($tuitionSummary['total']) }}</div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Đã thu</div>
            <div class="stat-value text-success" style="font-size:1.2rem">{{ $fmt($tuitionSummary['paid']) }}</div>
        </div>
    </div>
    <div class="col-md-4 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Còn nợ</div>
            <div class="stat-value text-danger" style="font-size:1.2rem">{{ $fmt($tuitionSummary['remaining']) }}</div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Hóa đơn học phí</strong>
    @canPerm('finance.invoices.manage')
    <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-primary">+ Tạo / quản lý HĐ</a>
    @endcanPerm
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>Mã HĐ</th>
            <th>Lớp / Tháng</th>
            <th>Số tiền</th>
            <th>Đã thu / Còn</th>
            <th>Hạn</th>
            <th>Trạng thái</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($invoices as $invoice)
            <tr>
                <td class="font-weight-bold">{{ $invoice->code }}</td>
                <td>
                    <div>{{ $invoice->courseClass?->name ?: '—' }}</div>
                    <div class="small text-muted">{{ $invoice->billing_month ?: '—' }} · {{ $invoice->feeTypeLabel() }}</div>
                </td>
                <td>{{ $fmt($invoice->amount) }}</td>
                <td>
                    <div class="text-success">{{ $fmt($invoice->paid_amount) }}</div>
                    <div class="small text-danger">{{ $fmt($invoice->remaining_amount) }}</div>
                </td>
                <td class="small">
                    {{ optional($invoice->due_date)->format('d/m/Y') ?: '—' }}
                    @if($invoice->isOverdue())
                        <div class="text-danger">Quá hạn {{ $invoice->daysOverdue() }} ngày</div>
                    @endif
                </td>
                <td><span class="badge lead-status {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span></td>
                <td class="text-right">
                    @canPerm('finance.invoices.view')
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-primary">Chi tiết</a>
                    @endcanPerm
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Chưa có hóa đơn học phí.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if(method_exists($invoices, 'links'))
    <div class="mt-2">{{ $invoices->appends(['tab' => 'tuition'])->links() }}</div>
@endif
