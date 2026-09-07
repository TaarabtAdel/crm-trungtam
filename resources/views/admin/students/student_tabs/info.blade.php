@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Trạng thái</div>
            <span class="badge lead-status {{ $student->statusBadgeClass() }}">{{ $student->statusLabel() }}</span>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Số lớp</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $student->classes_count }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Đã thu học phí</div>
            <div class="stat-value text-success" style="font-size:1.15rem">{{ $fmt($tuitionSummary['paid']) }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Còn nợ</div>
            <div class="stat-value text-danger" style="font-size:1.15rem">{{ $fmt($tuitionSummary['remaining']) }}</div>
            @if(($tuitionSummary['overdue'] ?? 0) > 0)
                <small class="text-danger">{{ $tuitionSummary['overdue'] }} HĐ quá hạn</small>
            @endif
        </div>
    </div>
</div>

@canPerm('students.manage')
<form method="POST" action="{{ route('admin.students.update', $student) }}">
    @csrf @method('PUT')
    <input type="hidden" name="from_detail" value="1">
    @include('admin.students._student_form', [
        'student' => $student,
        'branches' => $branches,
        'classes' => $classes,
        'hideClasses' => true,
    ])
    <div class="text-right mt-2">
        <button class="btn btn-primary">Lưu thông tin</button>
    </div>
</form>
@else
<div class="border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-2"><strong>Họ tên:</strong> {{ $student->name }}</div>
        <div class="col-md-6 mb-2"><strong>Chi nhánh:</strong> {{ $student->branch?->name }}</div>
        <div class="col-md-6 mb-2"><strong>Ngày sinh:</strong> {{ optional($student->dob)->format('d/m/Y') ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Giới tính:</strong> {{ $student->gender ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Người thân:</strong> {{ $student->parent_name ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>SĐT:</strong> {{ $student->parent_phone ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Email:</strong> {{ $student->parent_email ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Địa chỉ:</strong> {{ $student->address ?: '—' }}</div>
        <div class="col-12 mb-0"><strong>Ghi chú:</strong> {{ $student->notes ?: '—' }}</div>
    </div>
</div>
@endcanPerm
