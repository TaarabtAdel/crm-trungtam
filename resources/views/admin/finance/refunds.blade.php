@extends('layouts.admin')

@section('title', 'Hoàn tiền')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Yêu cầu hoàn tiền từ đâu?',
            'body' => '<p class="mb-0">Không tạo trực tiếp trên trang này. Vào <strong>chi tiết hóa đơn</strong> → dòng thanh toán → bấm <em>Hoàn</em> để gửi yêu cầu gắn với khoản thu gốc.</p>',
        ],
        [
            'title' => 'Duyệt hoặc từ chối',
            'body' => '<p class="mb-0">Yêu cầu ở trạng thái <em>Chờ duyệt</em> có nút <strong>Duyệt</strong> / <strong>Từ chối</strong>. Duyệt sẽ cập nhật số tiền có thể hoàn và liên quan đến công nợ HĐ.</p>',
        ],
        [
            'title' => 'Lọc theo trạng thái',
            'body' => '<p class="mb-0">Dùng bộ lọc phía trên để xem nhanh yêu cầu đang chờ, đã duyệt hoặc đã từ chối. Bấm mã HĐ để mở lại chi tiết thanh toán.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Hoàn tiền</h5>
            <small class="text-muted">Duyệt các yêu cầu hoàn gắn với thanh toán gốc.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalRefundsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="status" class="form-control form-control-sm" style="max-width:160px" onchange="this.form.submit()">
                <option value="">Tất cả</option>
                @foreach(\App\Models\Refund::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>HĐ / HV</th><th>Số tiền</th><th>Lý do</th><th>Người yêu cầu</th><th>TT</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            <a href="{{ route('admin.invoices.show', $item->payment->invoice_id) }}">{{ $item->payment?->invoice?->code }}</a>
                            <div class="small text-muted">{{ $item->payment?->invoice?->student?->name }}</div>
                        </td>
                        <td class="font-weight-bold">{{ $fmt($item->amount) }}</td>
                        <td class="small">{{ $item->reason ?: '—' }}</td>
                        <td>{{ $item->requester?->name }}</td>
                        <td><span class="badge lead-status {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span></td>
                        <td class="text-nowrap text-right">
                            @if($item->status==='pending')
                            <form action="{{ route('admin.refunds.approve', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success">Duyệt</button></form>
                            <form action="{{ route('admin.refunds.reject', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Từ chối</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có yêu cầu hoàn tiền.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalRefundsHelp',
    'title' => 'Hướng dẫn — Hoàn tiền',
    'items' => $helpItems,
])
@endsection
