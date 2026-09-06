@extends('layouts.admin')

@section('title', 'Học viên')

@section('content')
<div class="page-card">
    <div class="card-header-custom">
        <div><h5 class="mb-0 font-weight-bold">Quản lý Học viên</h5></div>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm</button>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:220px" placeholder="Tìm kiếm...">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả</option>
                @foreach(['studying'=>'Đang học','paused'=>'Bảo lưu','graduated'=>'Hoàn thành','dropped'=>'Nghỉ'] as $k=>$v)
                    <option value="{{ $k }}" @selected($status===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Họ và tên</th><th>Chi nhánh</th><th>Ngày sinh</th><th>Giới tính</th><th>Lớp</th><th>SĐT phụ huynh</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>{{ $student->name }}</td>
                        <td>{{ $student->branch?->name }}</td>
                        <td>{{ optional($student->dob)->format('d/m/Y') }}</td>
                        <td>{{ $student->gender }}</td>
                        <td>{{ $student->classes->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>{{ $student->parent_phone }}</td>
                        <td><span class="badge badge-primary">{{ $student->status }}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $student->id }}"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Không có dữ liệu học viên.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </div>
</div>

@foreach($students as $student)
<div class="modal fade" id="edit{{ $student->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.students.update', $student) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa học viên</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.students._student_form', ['student'=>$student,'branches'=>$branches,'classes'=>$classes])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.students.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm sinh viên</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.students._student_form', ['student'=>null,'branches'=>$branches,'classes'=>$classes])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu</button></div>
        </form>
    </div>
</div>
@endsection
