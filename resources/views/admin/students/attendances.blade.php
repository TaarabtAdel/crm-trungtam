@extends('layouts.admin')

@section('title', 'Điểm danh')

@section('content')
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Điểm danh</h5>
            <small class="text-muted">Chọn lớp và ngày để điểm danh. Có thể đánh dấu buổi học hoàn thành để tính lương GV.</small>
        </div>
    </div>
    <div class="card-body-custom">
        <form method="GET" class="filter-bar mb-3">
            <select name="class_id" class="form-control form-control-sm" style="max-width:280px" onchange="this.form.submit()">
                <option value="">-- Chọn lớp --</option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected($classId==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <input type="date" name="session_date" value="{{ $date }}" class="form-control form-control-sm" style="max-width:180px" onchange="this.form.submit()">
        </form>

        @if($courseClass)
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
                <div class="form-check mt-3 mb-3">
                    <input type="checkbox" class="form-check-input" name="mark_session_completed" value="1" id="markSession" checked>
                    <label class="form-check-label" for="markSession">Đánh dấu buổi học hoàn thành (tính lương GV)</label>
                </div>
                <button class="btn btn-primary">Lưu điểm danh</button>
                @endif
            </form>
        @else
            <p class="text-muted mb-0">Chọn lớp để bắt đầu điểm danh.</p>
        @endif
    </div>
</div>
@endsection
