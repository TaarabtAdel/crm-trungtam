@extends('layouts.admin')

@section('title', 'Lịch hẹn / Tương tác')

@section('content')
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Lịch hẹn / Tương tác</h5>
            <small class="text-muted">Quản lý lịch gọi điện chăm sóc khách hàng và lịch hẹn test năng lực.</small>
        </div>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm lịch hẹn mới</button>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Tìm theo tên khách, SĐT...">
            <select name="type" class="form-control form-control-sm" style="max-width:200px">
                <option value="">Tất cả</option>
                @foreach(['Cuộc gọi','Lịch hẹn Test','Nhắn tin','Gặp trực tiếp'] as $t)
                    <option value="{{ $t }}" @selected($type===$t)>{{ $t }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Khách hàng</th><th>Loại tương tác</th><th>Thời gian</th><th>Chi nhánh</th><th>Ghi chú</th><th>Sales phụ trách</th><th>Trạng thái</th><th>Hành động</th></tr></thead>
                <tbody>
                @forelse($interactions as $item)
                    <tr>
                        <td>{{ $item->lead?->name }}<br><small>{{ $item->lead?->phone }}</small></td>
                        <td>{{ $item->type }}</td>
                        <td>{{ optional($item->scheduled_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->branch?->name }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($item->notes, 40) }}</td>
                        <td>{{ $item->sales?->name }}</td>
                        <td><span class="badge badge-warning">{{ $item->status }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $item->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.interactions.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Không có dữ liệu.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $interactions->links() }}
    </div>
</div>

@foreach($interactions as $item)
<div class="modal fade" id="edit{{ $item->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.interactions.update', $item) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa lịch hẹn</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.crm._interaction_form', ['item'=>$item,'leads'=>$leads,'salesUsers'=>$salesUsers])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.interactions.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm lịch hẹn / tương tác mới</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.crm._interaction_form', ['item'=>null,'leads'=>$leads,'salesUsers'=>$salesUsers])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endsection
