@extends('layouts.admin')

@section('title', 'Người dùng')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Nhiều vai trò trên 1 tài khoản',
            'body' => '<p class="mb-0">Mỗi user có thể gắn <strong>nhiều role</strong> (vd Giáo viên + Đào tạo). Quyền truy cập = <em>hợp</em> quyền của các role đã chọn.</p>',
        ],
        [
            'title' => 'Thêm / sửa tài khoản',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Bấm <strong>Thêm người dùng</strong> hoặc nút sửa trên dòng.</li>'
                .'<li>Tick một hoặc nhiều vai trò.</li>'
                .'<li>Gắn chi nhánh nếu cần giới hạn phạm vi.</li>'
                .'<li>Lưu — đăng nhập ngay nếu đang Active.</li>'
                .'</ol>',
        ],
        [
            'title' => 'Khóa hoặc xóa',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Tắt Active = khóa đăng nhập, giữ hồ sơ.</li>'
                .'<li>Ưu tiên khóa thay vì xóa nếu chỉ tạm ngưng.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Phân quyền chi tiết',
            'body' => '<p class="mb-0">Ma trận quyền theo từng role tại menu <strong>Phân quyền</strong>.</p>',
        ],
        [
            'title' => 'Chấm công & lương NV',
            'body' => '<p class="mb-0">Đặt <em>Lương ngày</em> trên hồ sơ → chấm công nhiều ngày tại <a href="'.route('admin.staff-attendances.index').'">Chấm công NV</a> → chi lương tại <a href="'.route('admin.finance.staff-payroll').'">Lương NV</a>.</p>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Quản lý người dùng</h5>
            <small class="text-muted">Tài khoản đăng nhập · hỗ trợ nhiều vai trò trên mỗi user</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @canPerm('system.staff_attendances.view')
            <a href="{{ route('admin.staff-attendances.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-calendar2-check"></i> Chấm công
            </a>
            @endcanPerm
            <a href="{{ route('admin.permissions.edit') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-shield-lock"></i> Phân quyền
            </a>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalUsersHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalCreate">
                <i class="bi bi-person-plus"></i> Thêm người dùng
            </button>
        </div>
    </div>
</div>

<div class="dash-kpis row mb-3">
    <div class="col-6 col-md-6 col-xl-3 mb-3 mb-xl-0">
        <div class="dash-kpi dash-kpi-blue">
            <div class="dash-kpi-icon"><i class="bi bi-people"></i></div>
            <div>
                <div class="dash-kpi-value">{{ $stats['total'] }}</div>
                <div class="dash-kpi-label">Tổng tài khoản</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3 mb-3 mb-xl-0">
        <div class="dash-kpi dash-kpi-green">
            <div class="dash-kpi-icon"><i class="bi bi-person-check"></i></div>
            <div>
                <div class="dash-kpi-value">{{ $stats['active'] }}</div>
                <div class="dash-kpi-label">Đang hoạt động</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="dash-kpi dash-kpi-orange">
            <div class="dash-kpi-icon"><i class="bi bi-person-x"></i></div>
            <div>
                <div class="dash-kpi-value">{{ $stats['inactive'] }}</div>
                <div class="dash-kpi-label">Đã khóa</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-xl-3">
        <div class="dash-kpi dash-kpi-indigo">
            <div class="dash-kpi-icon"><i class="bi bi-mortarboard"></i></div>
            <div>
                <div class="dash-kpi-value">{{ $stats['teachers'] }}</div>
                <div class="dash-kpi-label">Có role GV</div>
            </div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Tìm tên, email, SĐT...">
            <select name="role" class="form-control form-control-sm" style="max-width:180px">
                <option value="">Tất cả vai trò</option>
                @foreach($roleOptions as $k => $v)
                    <option value="{{ $k }}" @selected($role === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="status" class="form-control form-control-sm" style="max-width:140px">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Khóa</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit">
                <i class="bi bi-funnel"></i> Lọc
            </button>
            @if($q !== '' || $role !== '' || $status !== '')
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Xóa lọc</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Người dùng</th>
                    <th>Vai trò</th>
                    <th>Chi nhánh</th>
                    <th>Lương ngày</th>
                    <th style="width:110px">Trạng thái</th>
                    <th class="text-right" style="width:140px">Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2 font-weight-bold"
                                     style="width:40px;height:40px;font-size:.8rem;flex-shrink:0">
                                    {{ $user->initials() }}
                                </div>
                                <div>
                                    <div class="font-weight-bold">{{ $user->name }}</div>
                                    <div class="small text-muted">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div class="small text-muted"><i class="bi bi-telephone"></i> {{ $user->phone }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @foreach($user->roleKeys() as $rk)
                                <span class="badge badge-role badge-role-{{ $rk }} mr-1 mb-1">{{ config('permissions.roles.'.$rk, $rk) }}</span>
                            @endforeach
                        </td>
                        <td>{{ $user->branch?->name ?? '—' }}</td>
                        <td>{{ number_format((float) $user->daily_rate, 0, ',', '.') }} đ</td>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Khóa</span>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-secondary" title="Chi tiết">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $user->id }}" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa người dùng {{ $user->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="bi bi-people" style="font-size:2rem"></i>
                            <div class="mt-2">Không có người dùng phù hợp bộ lọc.</div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>

@foreach($users as $user)
<div class="modal fade" id="edit{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Sửa — {{ $user->name }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                @include('admin.system._user_form', ['user' => $user, 'branches' => $branches])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.users.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Thêm người dùng</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                @include('admin.system._user_form', ['user' => null, 'branches' => $branches])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button class="btn btn-primary">Tạo tài khoản</button>
            </div>
        </form>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalUsersHelp',
    'title' => 'Hướng dẫn — Người dùng',
    'items' => $helpItems,
])
@endsection
