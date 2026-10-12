@php
    $canEditEnrollment = \Illuminate\Support\Facades\Schema::hasColumn('class_student', 'enrolled_from');
    $studentProgressById = $studentProgressById ?? [];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <strong>Danh sách học viên ({{ $classStudents->count() }})</strong>
        @if($class->max_students > 0)
            <span class="text-muted small ml-1">/ sĩ số tối đa {{ $class->max_students }}</span>
        @endif
    </div>
    <div class="d-flex align-items-center" style="gap:.5rem">
        <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'progress']) }}" class="btn btn-sm btn-outline-info">
            <i class="bi bi-clipboard-data"></i> Tình hình học tập
        </a>
        <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-secondary">Quản lý học viên</a>
        @canPerm('training.classes.manage')
        <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalAttachStudents">
            <i class="bi bi-person-plus"></i> Thêm học viên vào lớp
        </button>
        @endcanPerm
    </div>
</div>

@if($class->max_students > 0 && $classStudents->count() >= $class->max_students)
    <div class="alert alert-warning py-2 small">Lớp đã đạt sĩ số tối đa ({{ $class->max_students }}).</div>
@endif

<div class="table-responsive border rounded">
    <table class="table table-hover mb-0">
        <thead>
        <tr>
            <th>#</th>
            <th>Họ tên</th>
            <th>Người thân</th>
            <th>SĐT</th>
            <th class="text-nowrap">Ngày vào học</th>
            <th class="text-center text-nowrap" title="Có mặt + Muộn / buổi đã qua (tính đến hôm nay)">Đã học</th>
            <th>Trạng thái</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @forelse($classStudents as $i => $student)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <a href="{{ route('admin.students.show', $student) }}" class="font-weight-bold text-dark">{{ $student->name }}</a>
                </td>
                <td>{{ $student->parent_name ?: '—' }}</td>
                <td>{{ $student->phone ?: ($student->parent_phone ?: '—') }}</td>
                <td class="text-nowrap small">
                    @php
                        $enrolledFrom = $student->pivot->enrolled_from ?? null;
                    @endphp
                    @canPerm('training.classes.manage')
                        @if($canEditEnrollment)
                        <form method="POST" action="{{ route('admin.classes.students.enrollment', [$class, $student]) }}" class="d-inline-flex align-items-center" style="gap:.25rem">
                            @csrf @method('PATCH')
                            <input type="date" name="enrolled_from" class="form-control form-control-sm" style="width:9.5rem" value="{{ $enrolledFrom }}" title="Ngày bắt đầu học lớp này">
                            <button type="submit" class="btn btn-sm btn-outline-secondary py-0" title="Lưu ngày vào học"><i class="bi bi-check-lg"></i></button>
                        </form>
                        @else
                        {{ $enrolledFrom ? \Carbon\Carbon::parse($enrolledFrom)->format('d/m/Y') : '—' }}
                        @endif
                    @else
                        {{ $enrolledFrom ? \Carbon\Carbon::parse($enrolledFrom)->format('d/m/Y') : '—' }}
                    @endcanPerm
                </td>
                @php
                    $prog = $studentProgressById[$student->id] ?? [];
                @endphp
                <td class="text-center text-nowrap small">
                    <span class="font-weight-bold">{{ (int) ($prog['attended'] ?? 0) }}</span><span class="text-muted">/{{ (int) ($prog['eligible'] ?? 0) }}</span>
                    @if(($prog['needs_makeup'] ?? 0) > 0)
                        <br><span class="badge badge-warning" title="Vắng, vắng có phép hoặc chưa điểm danh">{{ $prog['needs_makeup'] }} cần bù</span>
                    @endif
                </td>
                <td><span class="badge lead-status {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span></td>
                <td class="text-right">
                    @canPerm('training.classes.manage')
                    <form method="POST" action="{{ route('admin.classes.students.detach', [$class, $student]) }}" class="d-inline" onsubmit="return confirm('Gỡ học viên khỏi lớp?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" title="Gỡ khỏi lớp"><i class="bi bi-person-dash"></i></button>
                    </form>
                    @endcanPerm
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Chưa có học viên trong lớp.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@canPerm('training.classes.manage')
<div class="modal fade" id="modalAttachStudents" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.classes.students.attach', $class) }}" class="modal-content" id="formAttachStudents">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Thêm học viên vào lớp</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3 flex-wrap" style="gap:.5rem">
                    <input type="search" id="attachStudentSearch" class="form-control form-control-sm" style="max-width:280px" placeholder="Tìm tên, SĐT, người thân...">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="attachSelectAll">Chọn tất cả</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="attachClearAll">Bỏ chọn</button>
                    <span class="small text-muted ml-auto" id="attachSelectedCount">Đã chọn: 0</span>
                </div>

                <div id="attachStudentsList" class="border rounded student-class-checks p-2" style="min-height:180px;max-height:320px;overflow-y:auto">
                    <div class="text-muted small text-center py-4">Đang tải danh sách...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary" id="attachSubmitBtn" disabled>Thêm vào lớp</button>
            </div>
        </form>
    </div>
</div>
@endcanPerm
