@extends('layouts.admin')

@section('title', 'Chi phí')

@section('content')
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Quản lý chi phí</h5>
            <small class="text-muted">Đề xuất → duyệt → thực chi.</small>
        </div>
        @canPerm('finance.expenses.manage')
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Đề xuất chi</button>
        @endcanPerm
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả TT</option>
                @foreach(\App\Models\Expense::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="category" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả loại</option>
                @foreach(\App\Models\Expense::categoryOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($category ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Ngày</th><th>Loại</th><th>Số tiền</th><th>Chi nhánh</th><th>Người tạo</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->expense_date?->format('d/m/Y') }}</td>
                        <td>
                            <div>{{ $item->categoryLabel() }}</div>
                            @if($item->note)<div class="small text-muted">{{ \Illuminate\Support\Str::limit($item->note, 50) }}</div>@endif
                            @if($item->attachment_path)<a class="small" href="{{ asset('storage/'.$item->attachment_path) }}" target="_blank">Chứng từ</a>@endif
                        </td>
                        <td class="font-weight-bold">{{ $fmt($item->amount) }}</td>
                        <td>{{ $item->branch?->name ?: '—' }}</td>
                        <td>{{ $item->creator?->name }}</td>
                        <td><span class="badge lead-status {{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span></td>
                        <td class="text-nowrap text-right">
                            @canPerm('finance.expenses.approve')
                            @if($item->status==='pending')
                            <form action="{{ route('admin.expenses.approve', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-success">Duyệt</button></form>
                            <form action="{{ route('admin.expenses.reject', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Từ chối</button></form>
                            @endif
                            @endcanPerm
                            @canPerm('finance.expenses.manage')
                            @if($item->status==='approved')
                            <form action="{{ route('admin.expenses.paid', $item) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-primary">Đã chi</button></form>
                            @endif
                            @if(in_array($item->status, ['pending','rejected'], true))
                            <form action="{{ route('admin.expenses.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                            @endif
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có khoản chi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </div>
</div>

@canPerm('finance.expenses.manage')
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.expenses.store') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Đề xuất chi</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="form-group"><label>Chi nhánh</label>
                    <select name="branch_id" class="form-control">
                        <option value="">-- Theo mặc định --</option>
                        @foreach($branches as $b)<option value="{{ $b->id }}" @selected(($currentBranchId ?? '')==$b->id)>{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Loại chi *</label>
                    <select name="category" class="form-control" required>
                        @foreach(\App\Models\Expense::categoryOptions() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Số tiền *</label><input type="number" name="amount" class="form-control" min="1" required></div>
                    <div class="form-group col-md-6"><label>Ngày chi *</label><input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                </div>
                <div class="form-group"><label>Ghi chú</label><textarea name="note" class="form-control" rows="2"></textarea></div>
                <div class="form-group mb-0"><label>Chứng từ</label><input type="file" name="attachment" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Gửi đề xuất</button></div>
        </form>
    </div>
</div>
@endcanPerm
@endsection
