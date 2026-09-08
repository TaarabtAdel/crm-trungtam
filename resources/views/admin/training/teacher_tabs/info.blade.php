@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Trạng thái</div>
            <span class="badge lead-status {{ $teacher->statusBadgeClass() }}">{{ $teacher->statusLabel() }}</span>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Lớp phụ trách</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $teacher->classes_count }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Buổi trên TKB</div>
            <div class="stat-value" style="font-size:1.4rem">{{ $teacher->sessions_count }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Đơn giá / giờ</div>
            <div class="stat-value" style="font-size:1.15rem">{{ $fmt($teacher->hourly_rate) }}</div>
        </div>
    </div>
</div>

@canPerm('training.teachers.manage')
<form method="POST" action="{{ route('admin.teachers.update', $teacher) }}">
    @csrf @method('PUT')
    <input type="hidden" name="from_detail" value="1">
    @include('admin.training._teacher_form', ['teacher' => $teacher, 'branches' => $branches])
    <div class="text-right mt-2">
        <button class="btn btn-primary">Lưu thông tin</button>
    </div>
</form>
@else
<div class="border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-2"><strong>Họ tên:</strong> {{ $teacher->name }}</div>
        <div class="col-md-6 mb-2"><strong>Chi nhánh:</strong> {{ $teacher->branch?->name ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Email:</strong> {{ $teacher->email }}</div>
        <div class="col-md-6 mb-2"><strong>SĐT:</strong> {{ $teacher->phone ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Chuyên môn:</strong> {{ $teacher->specialty ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Trình độ:</strong> {{ $teacher->qualification ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Đơn giá/giờ:</strong> {{ $fmt($teacher->hourly_rate) }}</div>
        <div class="col-md-6 mb-2"><strong>Ngày vào làm:</strong> {{ optional($teacher->joined_at)->format('d/m/Y') ?: '—' }}</div>
        <div class="col-12 mb-0"><strong>Ghi chú:</strong> {{ $teacher->notes ?: '—' }}</div>
    </div>
</div>
@endcanPerm
