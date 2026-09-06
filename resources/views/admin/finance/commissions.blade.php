@extends('layouts.admin')

@section('title', 'Hoa hồng')

@section('content')
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Hoa hồng Sales</h5>
            <small class="text-muted">Tính khi hóa đơn thu đủ theo quy tắc %.</small>
        </div>
        @canPerm('finance.commissions.manage')
        <a href="{{ route('admin.commission-rules.index') }}" class="btn btn-sm btn-outline-secondary">Quy tắc</a>
        @endcanPerm
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="status" class="form-control form-control-sm" style="max-width:180px" onchange="this.form.submit()">
                <option value="">Tất cả</option>
                @foreach(\App\Models\Commission::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Sales</th><th>Hóa đơn</th><th>%</th><th>Số tiền</th><th>TT</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->sales?->name }}</td>
                        <td>
                            <a href="{{ route('admin.invoices.show', $item->invoice_id) }}">{{ $item->invoice?->code }}</a>
                            <div class="small text-muted">{{ $item->invoice?->student?->name }}</div>
                        </td>
                        <td>{{ $item->percent }}%</td>
                        <td class="font-weight-bold">{{ $fmt($item->amount) }}</td>
                        <td><span class="lead-meta-chip">{{ $item->statusLabel() }}</span></td>
                        <td class="text-right">
                            @canPerm('finance.commissions.manage')
                            @if($item->status==='unpaid')
                            <form action="{{ route('admin.commissions.paid', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success">Đã trả HH</button></form>
                            @endif
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có hoa hồng.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>
@endsection
