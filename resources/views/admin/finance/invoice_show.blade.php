@extends('layouts.admin')

@section('title', 'Chi tiết hóa đơn')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Ghi nhận thanh toán',
            'body' => '<p class="mb-0">Nhập số tiền, phương thức và ngày thu rồi bấm <strong>Ghi nhận thu</strong>. Có thể đính kèm biên lai. Thu từng phần được phép cho đến khi hết nợ.</p>',
        ],
        [
            'title' => 'Trả góp & PDF',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Trả góp</strong>: đặt số kỳ và hạn kỳ đầu; hệ thống chia lịch. Khi thu có thể chọn kỳ hoặc để tự phân bổ.</li>'
                .'<li><strong>PDF</strong>: xuất hóa đơn để in / gửi phụ huynh.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Hoàn tiền & hoa hồng',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Trên từng dòng thanh toán, bấm <em>Hoàn</em> để gửi yêu cầu hoàn (cần quyền hoàn tiền).</li>'
                .'<li>Khi HĐ <strong>thu đủ</strong> và đã gắn Sales, hoa hồng tự phát sinh — xem khối Hoa hồng bên phải hoặc menu Hoa hồng.</li>'
                .'</ul>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">{{ $invoice->code }}</h5>
            <div class="mt-1">
                <span class="badge lead-status {{ $invoice->statusBadgeClass() }}">{{ $invoice->statusLabel() }}</span>
                <span class="lead-meta-chip">{{ $invoice->student?->name }}</span>
                @if($invoice->courseClass)<span class="lead-meta-chip">{{ $invoice->courseClass->name }}</span>@endif
            </div>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Quay lại</a>
            <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-pdf"></i> PDF</a>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalInvoiceShowHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <div class="row">
            <div class="col-md-3 mb-2"><div class="small text-muted">Tổng tiền</div><div class="h5 mb-0">{{ $fmt($invoice->amount) }}</div></div>
            <div class="col-md-3 mb-2"><div class="small text-muted">Đã thu</div><div class="h5 mb-0 text-success">{{ $fmt($invoice->paid_amount) }}</div></div>
            <div class="col-md-3 mb-2"><div class="small text-muted">Còn nợ</div><div class="h5 mb-0 text-danger">{{ $fmt($invoice->remaining_amount) }}</div></div>
            <div class="col-md-3 mb-2"><div class="small text-muted">Hạn TT</div><div class="h5 mb-0">{{ optional($invoice->due_date)->format('d/m/Y') ?: '—' }}</div></div>
        </div>
        <div class="small text-muted mt-2">
            {{ $invoice->feeTypeLabel() }}
            @if($invoice->billing_month)
                · {{ $invoice->billing_month }}
            @endif
            @if($invoice->sessions_count)
                · {{ $invoice->sessions_count }} buổi
            @endif
            @if(($invoice->gross_amount ?? null) !== null && (float) $invoice->gross_amount > 0)
                · Trước giảm {{ $fmt($invoice->gross_amount) }}
            @endif
            @if(($invoice->discount_amount ?? 0) > 0)
                · Giảm {{ $fmt($invoice->discount_amount) }}
                @if($invoice->discount_reason)
                    ({{ $invoice->discount_reason }})
                @endif
            @endif
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-3">
        <div class="page-card h-100">
            <div class="card-header-custom"><strong>Lịch sử thanh toán</strong></div>
            <div class="card-body-custom">
                @canPerm('finance.payments.manage')
                @if($invoice->remaining_amount > 0 && $invoice->status !== 'cancelled')
                <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice) }}" enctype="multipart/form-data" class="border rounded p-3 mb-3">
                    @csrf
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Số tiền *</label><input type="number" name="amount" class="form-control" min="1" max="{{ (int) $invoice->remaining_amount }}" value="{{ (int) $invoice->remaining_amount }}" required></div>
                        <div class="form-group col-md-4"><label>Phương thức</label>
                            <select name="method" class="form-control">
                                @foreach(\App\Models\Payment::methodOptions() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4"><label>Ngày thu</label><input type="datetime-local" name="paid_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}"></div>
                        <div class="form-group col-md-6"><label>Kỳ trả góp</label>
                            <select name="installment_id" class="form-control">
                                <option value="">-- Tự phân bổ --</option>
                                @foreach($invoice->installments as $inst)
                                    <option value="{{ $inst->id }}">Kỳ {{ $inst->sequence }} — còn {{ $fmt($inst->remainingAmount()) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6"><label>Biên lai (ảnh/PDF)</label><input type="file" name="receipt" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf"></div>
                        <div class="form-group col-12"><label>Ghi chú</label><input name="note" class="form-control"></div>
                    </div>
                    <button class="btn btn-success btn-sm">Ghi nhận thu</button>
                </form>
                @endif
                @endcanPerm

                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Ngày</th><th>Số tiền</th><th>PTTT</th><th>Người thu</th><th>Biên lai</th></tr></thead>
                        <tbody>
                        @forelse($invoice->payments as $p)
                            <tr>
                                <td>{{ $p->paid_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $fmt($p->amount) }}</td>
                                <td>{{ $p->methodLabel() }}</td>
                                <td>{{ $p->receiver?->name ?: '—' }}</td>
                                <td>
                                    @if($p->receipt_path)
                                        <a href="{{ \App\Support\TenantStorage::url($p->receipt_path) }}" target="_blank">Xem</a>
                                    @else —
                                    @endif
                                    @canPerm('finance.refunds.manage')
                                    <button class="btn btn-link btn-sm p-0 ml-2" data-toggle="modal" data-target="#refund{{ $p->id }}">Hoàn</button>
                                    @endcanPerm
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center">Chưa có thanh toán.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-3">
        <div class="page-card mb-3">
            <div class="card-header-custom"><strong>Trả góp</strong></div>
            <div class="card-body-custom">
                @canPerm('finance.invoices.manage')
                <form method="POST" action="{{ route('admin.invoices.installments.store', $invoice) }}" class="form-inline mb-3">
                    @csrf
                    <input type="number" name="installment_count" class="form-control form-control-sm mr-2" style="width:80px" value="{{ $invoice->installment_count }}" min="1" max="24">
                    <input type="date" name="first_due" class="form-control form-control-sm mr-2" value="{{ optional($invoice->due_date)->format('Y-m-d') }}">
                    <button class="btn btn-sm btn-outline-primary">Cập nhật kỳ</button>
                </form>
                @endcanPerm
                <table class="table table-sm mb-0">
                    <thead><tr><th>Kỳ</th><th>Hạn</th><th>Số tiền</th><th>Đã thu</th><th>TT</th></tr></thead>
                    <tbody>
                    @foreach($invoice->installments as $inst)
                        <tr>
                            <td>{{ $inst->sequence }}</td>
                            <td>{{ optional($inst->due_date)->format('d/m/Y') }}</td>
                            <td>{{ $fmt($inst->amount) }}</td>
                            <td>{{ $fmt($inst->paid_amount) }}</td>
                            <td><span class="lead-meta-chip">{{ $inst->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($invoice->commissions->count())
        <div class="page-card">
            <div class="card-header-custom"><strong>Hoa hồng</strong></div>
            <div class="card-body-custom">
                @foreach($invoice->commissions as $c)
                    <div class="d-flex justify-content-between mb-2">
                        <div>{{ $c->sales?->name }} · {{ $c->percent }}%</div>
                        <div>{{ $fmt($c->amount) }} <span class="lead-meta-chip">{{ $c->statusLabel() }}</span></div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

@canPerm('finance.refunds.manage')
@foreach($invoice->payments as $p)
<div class="modal fade" id="refund{{ $p->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.refunds.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="payment_id" value="{{ $p->id }}">
            <div class="modal-header"><h5 class="modal-title">Hoàn tiền — {{ $fmt($p->netAmount()) }} có thể hoàn</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="form-group"><label>Số tiền hoàn *</label><input type="number" name="amount" class="form-control" min="1" max="{{ (int) $p->netAmount() }}" required></div>
                <div class="form-group mb-0"><label>Lý do</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-warning">Gửi yêu cầu</button></div>
        </form>
    </div>
</div>
@endforeach
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalInvoiceShowHelp',
    'title' => 'Hướng dẫn — Chi tiết hóa đơn',
    'items' => $helpItems,
])
@endsection
