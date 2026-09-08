@extends('layouts.admin')

@section('title', 'Điểm danh')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Cách điểm danh',
            'body' => '<ol class="mb-0 pl-3">'
                .'<li>Chọn <strong>lớp</strong>.</li>'
                .'<li>Chọn <strong>ngày buổi học</strong> — chỉ hiện các ngày có trong <em>Thời khóa biểu</em> của lớp (không gồm buổi Hủy).</li>'
                .'<li>Với từng học viên, chọn: Có mặt / Muộn / Vắng / Vắng có phép.</li>'
                .'<li>Nhập ghi chú nếu cần → bấm <strong>Lưu điểm danh</strong>.</li>'
                .'</ol>',
        ],
        [
            'title' => 'Không thấy ngày cần điểm danh?',
            'body' => '<p class="mb-0">Vào chi tiết lớp → tab <strong>Thời khóa biểu</strong> để thêm buổi hoặc sinh TKB. Buổi ở trạng thái <em>Hủy</em> không cho điểm danh.</p>',
        ],
        [
            'title' => 'Hoàn thành buổi / lương GV',
            'body' => '<p class="mb-0">Đánh dấu buổi hoàn thành (tính lương) ở <strong>Thời khóa biểu lớp → Sửa buổi</strong>: chọn trạng thái <em>Hoàn thành</em>.</p>',
        ],
    ];
@endphp
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Điểm danh</h5>
            <small class="text-muted">Chỉ điểm danh đúng các ngày có trong thời khóa biểu của lớp.</small>
        </div>
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalAttendancesHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>
    <div class="card-body-custom">
        <form method="GET" class="filter-bar mb-3" id="attendanceFilterForm">
            <select name="class_id" class="form-control form-control-sm" style="max-width:280px" onchange="this.form.submit()">
                <option value="">-- Chọn lớp --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected((string) $classId === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            @if($courseClass)
                @if($sessionOptions->isNotEmpty())
                    <select name="session_date" class="form-control form-control-sm" style="max-width:340px" onchange="this.form.submit()">
                        @foreach($sessionOptions as $opt)
                            <option value="{{ $opt['date'] }}" @selected($date === $opt['date'])>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                @else
                    <span class="text-danger small">Lớp chưa có buổi trong TKB — hãy tạo thời khóa biểu trước.</span>
                @endif
            @endif
        </form>

        @if($courseClass && $date && $sessionOptions->isNotEmpty())
            <form method="POST" action="{{ route('admin.attendances.store') }}">
                @csrf
                <input type="hidden" name="class_id" value="{{ $courseClass->id }}">
                <input type="hidden" name="session_date" value="{{ $date }}">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 attendances-table">
                        <thead>
                        <tr>
                            <th>Học viên</th>
                            @foreach(\App\Models\Attendance::statusOptions() as $label)
                                <th class="text-center text-nowrap">{{ $label }}</th>
                            @endforeach
                            <th style="min-width:220px">Ghi chú</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($courseClass->students as $student)
                            @php
                                $row = $attendances->get($student->id);
                                $st = $row?->status ?? 'present';
                                $note = $row?->note ?? '';
                            @endphp
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $student->name }}</td>
                                @foreach(array_keys(\App\Models\Attendance::statusOptions()) as $opt)
                                    <td class="text-center align-middle">
                                        <input type="radio" name="statuses[{{ $student->id }}]" value="{{ $opt }}" {{ $st===$opt?'checked':'' }}>
                                    </td>
                                @endforeach
                                <td>
                                    <input type="text" name="notes[{{ $student->id }}]" value="{{ $note }}" class="form-control form-control-sm" placeholder="Ghi chú...">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Lớp chưa có học viên.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($courseClass->students->count())
                <button class="btn btn-primary mt-3">Lưu điểm danh</button>
                @endif
            </form>
        @elseif($courseClass && $sessionOptions->isEmpty())
            <div class="alert alert-warning mb-0">
                Lớp <strong>{{ $courseClass->name }}</strong> chưa có buổi học (hoặc toàn bộ đã Hủy).
                Vào <a href="{{ route('admin.classes.show', ['class' => $courseClass, 'tab' => 'timetable']) }}">Thời khóa biểu lớp</a> để tạo buổi trước khi điểm danh.
            </div>
        @else
            <p class="text-muted mb-0">Chọn lớp để bắt đầu điểm danh.</p>
        @endif
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalAttendancesHelp',
    'title' => 'Hướng dẫn — Điểm danh',
    'items' => $helpItems,
])
@endsection
