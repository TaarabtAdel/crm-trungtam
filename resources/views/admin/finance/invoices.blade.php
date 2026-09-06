@extends('layouts.admin')

@section('title', 'Hóa đơn')

@section('content')
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Danh sách Hóa đơn</h5>
            <small class="text-muted">Theo dõi công nợ, thanh toán và trả góp học phí.</small>
        </div>
        @canPerm('finance.invoices.manage')
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Tạo hóa đơn</button>
        @endcanPerm
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:220px" placeholder="Mã HĐ, học viên...">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\Invoice::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0 invoices-table">
                <thead>
                <tr>
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
                            <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có hóa đơn.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </div>
</div>

@canPerm('finance.invoices.manage')
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.invoices.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tạo hóa đơn</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.finance._invoice_form', ['invoice'=>null,'students'=>$students,'classes'=>$classes,'salesUsers'=>$salesUsers])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endcanPerm
@endsection

@push('scripts')
<script>
$(document).on('click', '.js-suggest-amount', function () {
    var wrap = $(this).closest('form');
    var classId = wrap.find('.js-invoice-class').val();
    var month = wrap.find('.js-invoice-month').val();
    if (!classId) { alert('Chọn lớp trước'); return; }
    $.get('{{ route('admin.invoices.suggest') }}', {class_id: classId, billing_month: month}, function (res) {
        wrap.find('.js-invoice-amount').val(res.amount);
        wrap.find('.js-sessions-count').val(res.sessions_count || '');
        wrap.find('.js-fee-type').val(res.fee_type || '');
        wrap.find('.js-fee-hint').show().text('Gợi ý: ' + res.tuition_type_label + ' — ' + res.tuition_display);
    });
});
</script>
@endpush
