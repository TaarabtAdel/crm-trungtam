@extends('layouts.admin')

@section('title', 'Môn học')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Môn học dùng để làm gì?',
            'body' => '<p class="mb-0">Danh mục chương trình / môn gắn với lớp học (ví dụ Toán, Tiếng Anh). Khi tạo lớp bạn chọn môn từ danh sách này.</p>',
        ],
        [
            'title' => 'Thêm & sửa môn',
            'body' => '<p class="mb-0">Bấm <em>+ Thêm môn học</em> hoặc <em>Sửa</em> để cập nhật tên, mô tả, chi nhánh và trạng thái (đang dùng / ngưng).</p>',
        ],
        [
            'title' => 'Lớp liên quan',
            'body' => '<p class="mb-0">Cột <em>Lớp liên quan</em> cho biết số lớp đang dùng môn đó — cân nhắc trước khi xóa môn đã gắn lớp.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Danh sách Môn học</h5>
            <small class="text-muted">Quản lý chương trình / môn học theo chi nhánh.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalSubjectsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
            @canPerm('training.subjects.manage')
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm môn học</button>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:260px" placeholder="Tìm tên hoặc mô tả môn học...">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\Subject::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0 subjects-table">
                <thead>
                <tr>
                    <th>Môn học</th>
                    <th>Mô tả</th>
                    <th>Chi nhánh</th>
                    <th>Lớp liên quan</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($subjects as $subject)
                    <tr>
                        <td>
                            <div class="font-weight-bold text-dark">{{ $subject->name }}</div>
                            <div class="mt-1">
                                <span class="badge lead-status {{ $subject->statusBadgeClass() }}">{{ $subject->statusLabel() }}</span>
                            </div>
                        </td>
                        <td>
                            @if($subject->description)
                                <div class="small text-muted" style="max-width:320px">{{ \Illuminate\Support\Str::limit($subject->description, 100) }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $subject->branch?->name ?? '—' }}</td>
                        <td>
                            <div class="font-weight-bold">{{ $subject->classes_count }}</div>
                            <div class="small text-muted">lớp</div>
                        </td>
                        <td class="text-nowrap text-right">
                            @canPerm('training.subjects.manage')
                            <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#edit{{ $subject->id }}">Sửa</button>
                            <form action="{{ route('admin.subjects.destroy', $subject) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button></form>
                            @else
                            <span class="text-muted small">—</span>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Không tìm thấy dữ liệu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $subjects->links() }}
    </div>
</div>

@canPerm('training.subjects.manage')
@foreach($subjects as $subject)
<div class="modal fade" id="edit{{ $subject->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa môn học</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.training._subject_form', ['subject' => $subject, 'branches' => $branches])</div>
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
            <div class="modal-body">@include('admin.training._subject_form', ['subject' => null, 'branches' => $branches])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu môn học</button></div>
        </form>
    </div>
</div>
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalSubjectsHelp',
    'title' => 'Hướng dẫn — Danh sách Môn học',
    'items' => $helpItems,
])
@endsection
