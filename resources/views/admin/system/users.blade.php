@extends('layouts.admin')

@section('title', 'Người dùng')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Người dùng & vai trò',
            'body' => '<p class="mb-0">Mỗi tài khoản có email đăng nhập, <strong>vai trò</strong> (quyền truy cập), chi nhánh gắn (nếu có) và trạng thái Active/Khóa.</p>',
        ],
        [
            'title' => 'Thêm / sửa tài khoản',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Bấm <strong>Thêm người dùng</strong> hoặc nút bút chì để sửa.</li>'
                .'<li>Chọn vai trò phù hợp (Sales, Giáo vụ, Admin…).</li>'
                .'<li>Gắn chi nhánh nếu cần giới hạn phạm vi làm việc.</li>'
                .'<li>Lưu — tài khoản có thể đăng nhập ngay (nếu Active).</li>'
                .'</ol>',
        ],
        [
            'title' => 'Khóa hoặc xóa',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Bỏ Active = khóa đăng nhập mà vẫn giữ hồ sơ.</li>'
                .'<li>Xóa tài khoản cần xác nhận; ưu tiên khóa nếu chỉ tạm ngưng.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Phân quyền chi tiết',
            'body' => '<p class="mb-0">Quyền theo vai trò cấu hình tại menu <strong>Phân quyền</strong> (ma trận tick quyền). Thay đổi role ở đây chỉ đổi vai trò gắn user.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Quản lý người dùng</h5>
            <small class="text-muted">Tài khoản đăng nhập hệ thống</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate"><i class="bi bi-plus"></i> Thêm người dùng</button>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalUsersHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Tìm tên, email...">
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Chi nhánh</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roleLabel() }}</td>
                        <td>{{ $user->branch?->name ?? '—' }}</td>
                        <td><span class="badge badge-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Active' : 'Khóa' }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $user->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa người dùng?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không có người dùng.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->links() }}
    </div>
</div>

@foreach($users as $user)
<div class="modal fade" id="edit{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa người dùng</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">
                @include('admin.system._user_form', ['user' => $user, 'branches' => $branches])
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.users.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm người dùng</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.system._user_form', ['user' => null, 'branches' => $branches])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalUsersHelp',
    'title' => 'Hướng dẫn — Người dùng',
    'items' => $helpItems,
])
@endsection
