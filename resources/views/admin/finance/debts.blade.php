@extends('layouts.admin')

@section('title', 'Công nợ')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $helpItems = [
        [
            'title' => 'Trang này dùng để làm gì?',
            'body' => '<p class="mb-0">Liệt kê các hóa đơn còn nợ (chưa thu đủ). Dùng để ưu tiên thu hồi học phí và theo dõi hạn thanh toán.</p>',
        ],
        [
            'title' => 'Lọc quá hạn / sắp đến hạn',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><strong>Quá hạn</strong>: đã qua hạn TT mà vẫn còn nợ.</li>'
                .'<li><strong>Sắp đến hạn (7 ngày)</strong>: hạn trong 7 ngày tới — nên nhắc phụ huynh sớm.</li>'
                .'<li><strong>Tất cả</strong>: mọi HĐ còn nợ.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Cách thu tiền',
            'body' => '<p class="mb-0">Bấm <em>Chi tiết</em> trên từng dòng để mở hóa đơn và ghi nhận thanh toán. Sau khi thu đủ, dòng sẽ biến mất khỏi danh sách công nợ.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Công nợ phải thu</h5>
            <small class="text-muted">Học viên còn nợ và số ngày quá hạn.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalDebtsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="filter" class="form-control form-control-sm" style="max-width:180px" onchange="this.form.submit()">
                <option value="all" @selected($filter==='all')>Tất cả</option>
                <option value="overdue" @selected($filter==='overdue')>Quá hạn</option>
                <option value="upcoming" @selected($filter==='upcoming')>Sắp đến hạn (7 ngày)</option>
            </select>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Hóa đơn</th><th>Học viên</th><th>Còn nợ</th><th>Hạn</th><th>Quá hạn</th><th>Sales</th><th></th></tr></thead>
                <tbody>
                @forelse($debts as $inv)
                    <tr>
                        <td>
                            <div class="font-weight-bold">{{ $inv->code }}</div>
                            <span class="badge lead-status {{ $inv->statusBadgeClass() }}">{{ $inv->statusLabel() }}</span>
                        </td>
                        <td>{{ $inv->student?->name }}</td>
                        <td class="font-weight-bold text-danger">{{ $fmt($inv->remaining_amount) }}</td>
                        <td>{{ optional($inv->due_date)->format('d/m/Y') ?: '—' }}</td>
                        <td>@if($inv->isOverdue())<span class="text-danger">{{ $inv->daysOverdue() }} ngày</span>@else<span class="text-muted">—</span>@endif</td>
                        <td>{{ $inv->sales?->name ?: '—' }}</td>
                        <td class="text-right"><a class="btn btn-sm btn-primary" href="{{ route('admin.invoices.show', $inv) }}">Chi tiết</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Không có công nợ phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $debts->links() }}
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalDebtsHelp',
    'title' => 'Hướng dẫn — Công nợ',
    'items' => $helpItems,
])
@endsection
