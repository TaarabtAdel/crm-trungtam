@extends('layouts.admin')

@section('title', 'Môn học')

@section('content')
<div class="page-card">
    <div class="card-header-custom">
        <div><h5 class="mb-0 font-weight-bold">Quản lý Môn học</h5></div>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm môn học</button>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Tìm kiếm môn học...">
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Tên môn</th><th>Mô tả</th><th>Chi nhánh</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                <tbody>
                @forelse($subjects as $subject)
                    <tr>
                        <td>{{ $subject->name }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($subject->description, 60) }}</td>
                        <td>{{ $subject->branch?->name }}</td>
                        <td><span class="badge badge-{{ $subject->status==='active'?'success':'secondary' }}">{{ $subject->status==='active'?'Hoạt động':'Ngưng' }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $subject->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Không có dữ liệu môn học.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $subjects->links() }}
    </div>
</div>

@foreach($subjects as $subject)
<div class="modal fade" id="edit{{ $subject->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa môn học</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group"><label>Tên môn học *</label><input name="name" class="form-control" value="{{ $subject->name }}" required></div>
                <div class="form-group"><label>Chi nhánh *</label>
                    <select name="branch_id" class="form-control" required>
                        @foreach($branches as $b)<option value="{{ $b->id }}" @selected($subject->branch_id==$b->id)>{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="3">{{ $subject->description }}</textarea></div>
                <div class="form-group"><label>Trạng thái</label>
                    <select name="status" class="form-control"><option value="active" @selected($subject->status==='active')>Hoạt động</option><option value="inactive" @selected($subject->status==='inactive')>Ngưng</option></select>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.subjects.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm môn học mới</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group"><label>Tên môn học *</label><input name="name" class="form-control" required placeholder="Nhập tên môn học..."></div>
                <div class="form-group"><label>Chi nhánh *</label>
                    <select name="branch_id" class="form-control" required>
                        <option value="">-- Chọn chi nhánh --</option>
                        @foreach($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $currentBranchId)==$b->id)>{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="3" placeholder="Nhập mô tả môn học..."></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
    </div>
</div>
@endsection
