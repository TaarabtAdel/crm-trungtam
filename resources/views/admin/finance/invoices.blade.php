@extends('layouts.admin')

@section('title', 'Hóa đơn học phí')

@section('content')
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Hóa đơn học phí</h5>
            <small class="text-muted">Theo dõi công nợ và thanh toán học viên</small>
        </div>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Tạo hóa đơn</button>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:220px" placeholder="Tìm học viên...">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả</option>
                <option value="unpaid" @selected($status==='unpaid')>Chưa thu</option>
                <option value="paid" @selected($status==='paid')>Đã thu</option>
                <option value="cancelled" @selected($status==='cancelled')>Hủy</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Học viên</th><th>Lớp</th><th>Tháng</th><th>Loại HP</th><th>Số tiền</th><th>Hạn</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->student?->name }}</td>
                        <td>{{ $invoice->courseClass?->name ?? '—' }}</td>
                        <td>
                            {{ $invoice->billing_month }}
                            @if($invoice->fee_type === 'per_session' && $invoice->sessions_count)
                                <br><small class="text-muted">{{ $invoice->sessions_count }} buổi</small>
                            @endif
                        </td>
                        <td>{{ $invoice->feeTypeLabel() }}</td>
                        <td>@vnd($invoice->amount)</td>
                        <td>{{ optional($invoice->due_date)->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge badge-{{ $invoice->status==='paid'?'success':($invoice->status==='unpaid'?'warning':'secondary') }}">{{ $invoice->status }}</span>
                        </td>
                        <td>
                            @if($invoice->status!=='paid')
                            <form action="{{ route('admin.invoices.paid', $invoice) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success">Đã thu</button></form>
                            @endif
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $invoice->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Chưa có hóa đơn.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </div>
</div>

@foreach($invoices as $invoice)
<div class="modal fade" id="edit{{ $invoice->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa hóa đơn</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.finance._invoice_form', ['invoice'=>$invoice,'students'=>$students,'classes'=>$classes])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.invoices.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tạo hóa đơn</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Doanh thu dashboard/báo cáo lấy từ hóa đơn <strong>đã thu</strong>, không lấy trực tiếp từ đơn giá lớp.</p>
                @include('admin.finance._invoice_form', ['invoice'=>null,'students'=>$students,'classes'=>$classes])
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Tạo</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function suggestInvoiceAmount($scope) {
    var classId = $scope.find('.js-invoice-class').val();
    var month = $scope.find('.js-invoice-month').val();
    var $hint = $scope.find('.js-fee-hint');
    if (!classId) {
        $hint.hide().text('');
        return;
    }
    $.get('{{ route('admin.invoices.suggest') }}', { class_id: classId, billing_month: month })
        .done(function (res) {
            $scope.find('.js-fee-type').val(res.fee_type);
            $scope.find('.js-sessions-count').val(res.sessions_count || '');
            $scope.find('.js-invoice-amount').val(res.amount);
            if (res.fee_type === 'per_session') {
                $hint.html('Lớp <strong>' + res.class_name + '</strong>: ' + res.tuition_display +
                    ' × <strong>' + res.sessions_count + '</strong> buổi completed trong tháng = <strong>' +
                    Number(res.amount).toLocaleString('vi-VN') + ' đ</strong>').show();
            } else {
                $hint.html('Lớp <strong>' + res.class_name + '</strong>: học phí tháng = <strong>' +
                    Number(res.amount).toLocaleString('vi-VN') + ' đ</strong>').show();
            }
        });
}

$(document).on('click', '.js-suggest-amount', function () {
    suggestInvoiceAmount($(this).closest('.modal-body'));
});
$(document).on('change', '.js-invoice-class, .js-invoice-month', function () {
    suggestInvoiceAmount($(this).closest('.modal-body'));
});
</script>
@endpush
