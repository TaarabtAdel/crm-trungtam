@extends('layouts.admin')

@section('title', 'Chi nhánh')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Chi nhánh dùng để làm gì?',
            'body' => '<p class="mb-0">Mỗi cơ sở (tên, mã, địa chỉ, SĐT) dùng để gắn học viên, lớp, người dùng và lọc dữ liệu theo địa điểm.</p>',
        ],
        [
            'title' => 'Thêm / sửa chi nhánh',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Bấm <strong>Thêm chi nhánh</strong> → điền tên (bắt buộc), mã, địa chỉ, SĐT.</li>'
                .'<li>Sửa bằng nút bút chì trên từng dòng.</li>'
                .'<li>Bỏ tick <em>Đang hoạt động</em> để ngưng chi nhánh (không xóa).</li>'
                .'</ul>',
        ],
        [
            'title' => 'Xóa chi nhánh',
            'body' => '<p class="mb-0">Chỉ xóa khi không còn dữ liệu phụ thuộc quan trọng. Hệ thống sẽ hỏi xác nhận trước khi xóa.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Quản lý chi nhánh</h5>
            <small class="text-muted">Danh sách cơ sở / chi nhánh</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate"><i class="bi bi-plus"></i> Thêm chi nhánh</button>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalBranchesHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Tìm chi nhánh...">
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Tên</th><th>Mã</th><th>Địa chỉ</th><th>SĐT</th><th>Trạng thái</th><th>Chức năng</th>
                </tr>
                </thead>
                <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->code }}</td>
                        <td>{{ $branch->address }}</td>
                        <td>{{ $branch->phone }}</td>
                        <td>
                            <span class="badge badge-{{ $branch->is_active ? 'success' : 'secondary' }} badge-status">
                                {{ $branch->is_active ? 'Hoạt động' : 'Ngưng' }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $branch->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa chi nhánh?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không có dữ liệu chi nhánh.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $branches->links() }}
    </div>
</div>

@foreach($branches as $branch)
<div class="modal fade" id="edit{{ $branch->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.branches.update', $branch) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa chi nhánh</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group"><label>Tên *</label><input name="name" class="form-control" value="{{ $branch->name }}" required></div>
                <div class="form-group"><label>Mã</label><input name="code" class="form-control" value="{{ $branch->code }}"></div>
                <div class="form-group"><label>Địa chỉ</label><input name="address" class="form-control" value="{{ $branch->address }}"></div>
                <div class="form-group"><label>SĐT</label><input name="phone" class="form-control" value="{{ $branch->phone }}"></div>
                <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="active{{ $branch->id }}" {{ $branch->is_active ? 'checked' : '' }}><label class="form-check-label" for="active{{ $branch->id }}">Đang hoạt động</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.branches.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm chi nhánh</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group"><label>Tên *</label><input name="name" class="form-control" required></div>
                <div class="form-group"><label>Mã</label><input name="code" class="form-control"></div>
                <div class="form-group"><label>Địa chỉ</label><input name="address" class="form-control"></div>
                <div class="form-group"><label>SĐT</label><input name="phone" class="form-control"></div>
                <input type="hidden" name="is_active" value="1">
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalBranchesHelp',
    'title' => 'Hướng dẫn — Chi nhánh',
    'items' => $helpItems,
])
@endsection
