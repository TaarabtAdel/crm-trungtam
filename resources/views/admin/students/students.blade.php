@extends('layouts.admin')

@section('title', 'Học viên')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Trang này dùng để làm gì?',
            'body' => '<p class="mb-0">Danh sách học viên toàn trung tâm: tìm kiếm, lọc theo trạng thái/lớp, thêm mới và mở <strong>Chi tiết</strong> để quản lý hồ sơ, lớp, học phí và điểm danh.</p>',
        ],
        [
            'title' => 'Thêm học viên mới',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Bấm <strong>+ Thêm học viên</strong> (cần quyền quản lý).</li>'
                .'<li>Điền thông tin HV, người thân, chi nhánh, trạng thái.</li>'
                .'<li>Lưu → học viên xuất hiện trong danh sách.</li>'
                .'</ol>',
        ],
        [
            'title' => 'Lọc và mở Chi tiết',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Ô tìm: tên HV, người thân, SĐT…</li>'
                .'<li>Lọc theo <em>trạng thái</em> hoặc <em>lớp</em> rồi bấm Lọc.</li>'
                .'<li>Bấm tên HV hoặc nút <strong>Chi tiết</strong> để xem các tab thông tin / lớp / học phí / điểm danh.</li>'
                .'</ul>',
        ],
        [
            'title' => 'Xóa học viên',
            'body' => '<p class="mb-0">Nút thùng rác chỉ hiện khi có quyền quản lý. Xóa cần xác nhận — cân nhắc trước vì ảnh hưởng dữ liệu liên quan.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Danh sách Học viên</h5>
            <small class="text-muted">Quản lý hồ sơ học viên, người thân và lớp đang theo học.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @canPerm('students.manage')
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm học viên</button>
            @endcanPerm
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalStudentsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Tìm tên HV, người thân, SĐT...">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\Student::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="class_id" class="form-control form-control-sm" style="max-width:200px">
                <option value="">Tất cả lớp</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" @selected(($classId ?? '')==$class->id)>{{ $class->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0 students-table">
                <thead>
                <tr>
                    <th>Học viên</th>
                    <th>Người thân</th>
                    <th>Lớp học</th>
                    <th>Chi nhánh</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($students as $student)
                    <tr>
                        <td>
                            <a href="{{ route('admin.students.show', $student) }}" class="font-weight-bold text-dark">{{ $student->name }}</a>
                            <div class="mt-1 d-flex align-items-center flex-wrap" style="gap:.35rem">
                                <span class="badge lead-status {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span>
                                @if($student->gender)
                                    <span class="lead-meta-chip">{{ $student->gender }}</span>
                                @endif
                                @if($student->dob)
                                    <span class="lead-meta-chip">{{ $student->dob->format('d/m/Y') }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($student->parent_name || $student->parent_phone || $student->parent_email)
                                <div>{{ $student->parent_name ?: '—' }}</div>
                                @if($student->parent_phone)
                                    <div class="small text-muted"><i class="bi bi-telephone mr-1"></i>{{ $student->parent_phone }}</div>
                                @endif
                                @if($student->parent_email)
                                    <div class="small text-muted"><i class="bi bi-envelope mr-1"></i>{{ $student->parent_email }}</div>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($student->classes->isNotEmpty())
                                <div class="d-flex align-items-center flex-wrap" style="gap:.35rem;max-width:260px">
                                    @foreach($student->classes->take(3) as $class)
                                        <span class="lead-meta-chip">{{ $class->name }}</span>
                                    @endforeach
                                    @if($student->classes->count() > 3)
                                        <span class="lead-meta-chip">+{{ $student->classes->count() - 3 }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Chưa xếp lớp</span>
                            @endif
                        </td>
                        <td>{{ $student->branch?->name ?? '—' }}</td>
                        <td class="text-nowrap text-right">
                            <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-primary">Chi tiết</a>
                            @canPerm('students.manage')
                            <form action="{{ route('admin.students.destroy', $student) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button></form>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Không tìm thấy dữ liệu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </div>
</div>

@canPerm('students.manage')
<div class="modal fade" id="modalCreate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.students.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Thêm học viên mới</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.students._student_form', ['student'=>null,'branches'=>$branches,'classes'=>$classes])</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu học viên</button></div>
        </form>
    </div>
</div>
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalStudentsHelp',
    'title' => 'Hướng dẫn — Danh sách học viên',
    'items' => $helpItems,
])
@endsection
