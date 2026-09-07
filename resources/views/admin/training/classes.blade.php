@extends('layouts.admin')

@section('title', 'Lớp học')

@section('content')
@php
    $days = ['T2','T3','T4','T5','T6','T7','CN'];
    $helpItems = [
        [
            'title' => 'Tạo & quản lý lớp',
            'body' => '<p class="mb-0">Bấm <em>+ Thêm lớp mới</em> để tạo lớp: môn học, giáo viên, lịch học, sĩ số tối đa, loại học phí (tháng / buổi). Lọc theo trạng thái và môn để tìm nhanh.</p>',
        ],
        [
            'title' => 'Trạng thái lớp',
            'body' => '<p class="mb-0">Badge trạng thái (đang mở, tạm dừng, kết thúc…) giúp biết lớp còn nhận học viên hay không. Cập nhật khi sửa lớp.</p>',
        ],
        [
            'title' => 'Mở chi tiết lớp',
            'body' => '<p class="mb-0">Bấm tên lớp hoặc vào chi tiết để quản lý học viên, thời khóa biểu, thu học phí và điểm danh.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Danh sách Lớp học</h5>
            <small class="text-muted">Quản lý lớp, lịch học, sĩ số và học phí theo chi nhánh.</small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalClassesHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
            @canPerm('training.classes.manage')
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreate">+ Thêm lớp mới</button>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Tìm tên, mã lớp, phòng...">
            <select name="status" class="form-control form-control-sm" style="max-width:160px">
                <option value="">Tất cả trạng thái</option>
                @foreach(\App\Models\CourseClass::statusOptions() as $k=>$v)
                    <option value="{{ $k }}" @selected(($status ?? '')===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <select name="subject_id" class="form-control form-control-sm" style="max-width:200px">
                <option value="">Tất cả môn học</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected(($subjectId ?? '')==$subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-outline-secondary">Lọc</button>
        </form>
        <div class="table-responsive">
            <table class="table table-hover mb-0 classes-table">
                <thead>
                <tr>
                    <th>Lớp học</th>
                    <th>Phân bổ</th>
                    <th>Lịch học</th>
                    <th>Sĩ số</th>
                    <th>Học phí</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($classes as $class)
                    @php
                        $enrolled = (int) ($class->students_count ?? 0);
                        $max = (int) ($class->max_students ?? 0);
                        $full = $max > 0 && $enrolled >= $max;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-start" style="gap:.5rem">
                                <div class="class-avatar">{{ strtoupper(mb_substr($class->name, 0, 1)) }}</div>
                                <div>
                                    <a href="{{ route('admin.classes.show', $class) }}" class="font-weight-bold text-dark">{{ $class->name }}</a>
                                    <div class="mt-1 d-flex align-items-center flex-wrap" style="gap:.35rem">
                                        <span class="badge lead-status {{ $class->statusBadgeClass() }}">{{ $class->statusLabel() }}</span>
                                        @if($class->code)
                                            <span class="lead-meta-chip">{{ $class->code }}</span>
                                        @endif
                                        <span class="lead-meta-chip">{{ $class->tuitionTypeLabel() }}</span>
                                        @if(($class->sessions_count ?? 0) > 0)
                                            <span class="lead-meta-chip"><i class="bi bi-calendar3"></i> {{ $class->sessions_count }} buổi</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $class->branch?->name ?? '—' }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-book mr-1"></i>{{ $class->subject?->name ?? 'Chưa có môn' }}
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-person-badge mr-1"></i>{{ $class->teacher?->name ?? 'Chưa gán GV' }}
                            </div>
                        </td>
                        <td>
                            @if(!empty($class->schedule_days))
                                <div>{{ implode(', ', $class->schedule_days) }}</div>
                                <div class="small text-muted">
                                    {{ $class->start_time ? substr($class->start_time, 0, 5) : '' }}
                                    @if($class->start_time || $class->end_time)–@endif
                                    {{ $class->end_time ? substr($class->end_time, 0, 5) : '' }}
                                </div>
                            @else
                                <span class="text-muted">Chưa xếp lịch</span>
                            @endif
                            @if($class->room)
                                <div class="small text-muted"><i class="bi bi-door-open mr-1"></i>{{ $class->room }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="font-weight-bold {{ $full ? 'text-danger' : '' }}">{{ $enrolled }}@if($max > 0)<span class="text-muted font-weight-normal">/{{ $max }}</span>@endif</div>
                            <div class="small text-muted">{{ $full ? 'Đã đủ' : 'học viên' }}</div>
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ number_format((float) $class->tuition_fee, 0, ',', '.') }} đ</div>
                            <div class="small text-muted">{{ $class->isPerSessionFee() ? '/ buổi' : '/ tháng' }}</div>
                        </td>
                        <td class="text-nowrap text-right">
                            <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-sm btn-primary">Chi tiết</a>
                            @canPerm('training.classes.manage')
                            <button class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#edit{{ $class->id }}" title="Sửa nhanh"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('admin.classes.destroy', $class) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa"><i class="bi bi-trash"></i></button></form>
                            @endcanPerm
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không tìm thấy dữ liệu phù hợp.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $classes->links() }}
    </div>
</div>

@canPerm('training.classes.manage')
@foreach($classes as $class)
<div class="modal fade" id="edit{{ $class->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('admin.classes.update', $class) }}" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Sửa lớp học</h5><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
            <div class="modal-body">@include('admin.training._class_form', compact('class','branches','subjects','teachers','days'))</div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thay đổi</button></div>
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
            <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu lớp</button></div>
        </form>
    </div>
</div>
@endcanPerm

@include('partials.page_help', [
    'modalId' => 'modalClassesHelp',
    'title' => 'Hướng dẫn — Danh sách Lớp học',
    'items' => $helpItems,
])
@endsection

@push('scripts')
<script>
$(document).on('change', '.js-tuition-type', function () {
    var label = $(this).closest('.row').find('.js-tuition-fee-label');
    label.text(this.value === 'per_session' ? 'Học phí / buổi' : 'Học phí / tháng');
});
</script>
@endpush
