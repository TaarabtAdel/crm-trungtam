@extends('layouts.admin')

@section('title', 'Hoa hồng')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Trang này dùng để làm gì?',
            'body' => '<p class="mb-0">Theo dõi hoa hồng đã phát sinh cho từng Sales sau khi hóa đơn <strong>thu đủ</strong>. Bấm <em>Đã trả HH</em> khi trung tâm đã chi trả hoa hồng cho nhân viên.</p>',
        ],
        [
            'title' => 'Vì sao chưa thấy hoa hồng?',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Hóa đơn chưa thu đủ (còn <em>Chưa thu</em> / <em>Thu một phần</em>).</li>'
                .'<li>Hóa đơn chưa gắn <strong>Sales phụ trách</strong>.</li>'
                .'<li>Chưa có quy tắc % (vào <a href="'.route('admin.commission-rules.index').'">Quy tắc hoa hồng</a> để cấu hình).</li>'
                .'<li>% rule = 0 hoặc rule đang tắt Active.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Luồng làm việc chuẩn',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Cấu hình % tại <strong>Quy tắc HH</strong>.</li>'
                .'<li>Tạo HĐ và chọn Sales.</li>'
                .'<li>Thu đủ tiền trên chi tiết hóa đơn.</li>'
                .'<li>Quay lại trang này → thấy dòng HH → đánh dấu đã trả.</li>'
                .'</ol>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Hoa hồng Sales</h5>
            <small class="text-muted">Danh sách hoa hồng phát sinh khi hóa đơn thu đủ.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @canPerm('finance.commissions.manage')
            <a href="{{ route('admin.commission-rules.index') }}" class="btn btn-sm btn-outline-secondary">Quy tắc HH</a>
            @endcanPerm
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalCommissionsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="status" class="form-control form-control-sm" style="max-width:180px" onchange="this.form.submit()">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\Commission::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Sales</th><th>Hóa đơn</th><th>%</th><th>Số tiền</th><th>Trạng thái</th><th></th></tr></thead>
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
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có hoa hồng. Bấm <em>Hướng dẫn</em> nếu bạn đang tìm hiểu cách phát sinh.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalCommissionsHelp',
    'title' => 'Hướng dẫn — Hoa hồng Sales',
    'items' => $helpItems,
])
@endsection
