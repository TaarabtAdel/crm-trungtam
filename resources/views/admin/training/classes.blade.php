@extends('layouts.admin')

@section('title', 'Lớp học')

@section('content')
@php $days = ['T2','T3','T4','T5','T6','T7','CN']; @endphp
<div class="page-card">
    <div class="card-header-custom">
        <div><h5 class="mb-0 font-weight-bold">Quản lý Lớp học</h5></div>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm lớp</button>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:280px" placeholder="Tìm kiếm tên hoặc mã lớp...">
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Lớp / Mã</th><th>Chi nhánh</th><th>Môn học</th><th>Giáo viên</th><th>Lịch</th><th>Sĩ số</th><th>Học phí</th><th>Trạng thái</th><th>Chức năng</th></tr></thead>
                <tbody>
                @forelse($classes as $class)
                    <tr>
                        <td>
                            <a href="{{ route('admin.classes.show', $class) }}" class="font-weight-bold text-dark">{{ $class->name }}</a>
                            <br><small class="text-muted">{{ $class->code }}</small>
                        </td>
                        <td>{{ $class->branch?->name }}</td>
                        <td>{{ $class->subject?->name }}</td>
                        <td>{{ $class->teacher?->name }}</td>
                        <td>
                            @if(!empty($class->schedule_days))
                                {{ implode(', ', $class->schedule_days) }}
                                <br><small class="text-muted">
                                    {{ $class->start_time ? substr($class->start_time,0,5) : '' }}
                                    @if($class->start_time || $class->end_time)–@endif
                                    {{ $class->end_time ? substr($class->end_time,0,5) : '' }}
                                </small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                            <br><small class="text-muted">{{ $class->sessions_count }} buổi TKB</small>
                        </td>
                        <td>{{ $class->students->count() }}/{{ $class->max_students }}</td>
                        <td>
                            {{ $class->tuitionDisplay() }}
                            <br><small class="text-muted">{{ $class->tuitionTypeLabel() }}</small>
                        </td>
                        <td><span class="badge badge-info">{{ $class->status }}</span></td>
                        <td class="text-nowrap">
                            <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-sm btn-primary" title="Chi tiết">Chi tiết</a>
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#edit{{ $class->id }}" title="Sửa nhanh"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Không có dữ liệu lớp học.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $classes->links() }}
    </div>
</div>

@foreach($classes as $class)
<div class="modal fade" id="edit{{ $class->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('admin.classes.update', $class) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa lớp học</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.training._class_form', compact('class','branches','subjects','teachers','days'))</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button><button class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
    </div>
</div>
@endforeach

<div class="modal fade" id="modalCreate" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('admin.classes.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm lớp học mới</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.training._class_form', ['class'=>null,'branches'=>$branches,'subjects'=>$subjects,'teachers'=>$teachers,'days'=>$days])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button><button class="btn btn-primary">Lưu thay đổi</button></div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).on('change', '.js-tuition-type', function () {
    var label = $(this).closest('.row').find('.js-tuition-fee-label');
    label.text(this.value === 'per_session' ? 'Học phí / buổi' : 'Học phí / tháng');
});
</script>
@endpush
