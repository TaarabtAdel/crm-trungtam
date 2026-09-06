@extends('layouts.admin')

@section('title', 'Giáo viên')

@section('content')
<div class="page-card">
    <div class="card-header-custom">
        <div><h5 class="mb-0 font-weight-bold">Quản lý Giáo viên</h5></div>
        <div>
            @canPerm('training.teachers.payroll')
            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalPayroll"><i class="bi bi-calculator"></i> Bảng lương</button>
            @endcanPerm
            @canPerm('training.teachers.manage')
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm mới</button>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="status" class="form-control form-control-sm" style="max-width:140px">
                <option value="">Tất cả</option>
                <option value="active" @selected($status==='active')>Hoạt động</option>
                <option value="inactive" @selected($status==='inactive')>Ngưng</option>
            </select>
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Tìm kiếm giáo viên...">
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Họ và tên</th><th>Email</th><th>SĐT</th><th>Chuyên môn</th><th>Chi nhánh</th><th>Lương/h</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                <tbody>
                @forelse($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher->name }}</td>
                        <td>{{ $teacher->email }}</td>
                        <td>{{ $teacher->phone }}</td>
                        <td>{{ $teacher->specialty }}</td>
                        <td>{{ $teacher->branch?->name }}</td>
                        <td>@vnd($teacher->hourly_rate)</td>
                        <td><span class="badge badge-{{ $teacher->status==='active'?'success':'secondary' }}">{{ $teacher->status==='active'?'Hoạt động':'Ngưng' }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $teacher->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Không có dữ liệu giáo viên.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $teachers->links() }}
    </div>
</div>

@foreach($teachers as $teacher)
<div class="modal fade" id="edit{{ $teacher->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa giáo viên</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.training._teacher_form', ['teacher'=>$teacher,'branches'=>$branches])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.teachers.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm mới Giáo viên</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.training._teacher_form', ['teacher'=>null,'branches'=>$branches])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPayroll" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0"><i class="bi bi-calculator"></i> Bảng Lương Giáo Viên</h5>
                    <small class="text-muted">Tổng hợp giờ dạy và thù lao thực tế theo tháng</small>
                </div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form method="GET" class="form-inline mb-3">
                    <label class="mr-2">Tháng</label>
                    <select name="payroll_month" class="form-control form-control-sm mr-2">
                        @for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month==$m)>Tháng {{ $m }}</option>@endfor
                    </select>
                    <label class="mr-2">Năm</label>
                    <select name="payroll_year" class="form-control form-control-sm mr-2">
                        @for($y=now()->year-1;$y<=now()->year+1;$y++)<option value="{{ $y }}" @selected($year==$y)>{{ $y }}</option>@endfor
                    </select>
                    <button class="btn btn-sm btn-primary">Xem</button>
                    <a href="{{ route('admin.teachers.payroll.export', ['payroll_month'=>$month,'payroll_year'=>$year]) }}" class="btn btn-sm btn-success ml-2">Xuất Excel/CSV</a>
                </form>
                <p class="small text-muted">* Lương = giờ dạy × đơn giá/giờ, lấy từ các buổi <strong>Hoàn thành</strong> trên thời khóa biểu (theo GV gắn trên từng buổi, kể cả dạy thay).</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Giáo viên</th><th>Chi nhánh</th><th>Đơn giá (lương/h)</th><th>Số buổi hoàn thành</th><th>Tổng giờ dạy</th><th>Thực nhận</th></tr></thead>
                        <tbody>
                        @forelse($payroll as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['branch'] }}</td>
                                <td>@vnd($row['rate'])</td>
                                <td>{{ $row['sessions'] }} buổi</td>
                                <td>{{ $row['hours'] }}h</td>
                                <td>@vnd($row['total'])</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">Không có dữ liệu giờ dạy trong tháng {{ $month }}/{{ $year }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button></div>
        </div>
    </div>
</div>
@endsection

@if(request()->has('payroll_month'))
@push('scripts')
<script>$(function(){ $('#modalPayroll').modal('show'); });</script>
@endpush
@endif
