@php
    $studentProgressById = $studentProgressById ?? [];
    $month = $month ?? now()->format('Y-m');
    $availableMonths = $availableMonths ?? [];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <strong>Tình hình học tập</strong>
        <p class="small text-muted mb-0">
            Buổi đã học = <em>Có mặt</em> / <em>Muộn</em>.
            Thiếu buổi = <em>Vắng</em> hoặc chưa điểm danh (buổi đã qua, trong kỳ ghi danh).
            Sắp xếp bù trên tab <strong>Thời khóa biểu</strong> → thêm buổi → chọn <em>Buổi bù cho</em>.
        </p>
    </div>
    <form method="GET" action="{{ route('admin.classes.show', $class) }}" class="d-flex align-items-center mb-0" style="gap:.5rem">
        <input type="hidden" name="tab" value="progress">
        <label class="small text-muted mb-0">Tháng</label>
        <select name="month" class="form-control form-control-sm" style="width:170px" onchange="this.form.submit()">
            @foreach($availableMonths as $ym)
                <option value="{{ $ym }}" @selected($month === $ym)>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('m/Y') }}
                </option>
            @endforeach
            @if($month !== '' && ! in_array($month, $availableMonths, true))
                <option value="{{ $month }}" selected>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}</option>
            @endif
        </select>
        <a href="{{ route('admin.classes.show', ['class' => $class, 'tab' => 'timetable', 'month' => $month]) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-calendar-plus"></i> TKB tháng này
        </a>
    </form>
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
        <tr>
            <th>Học viên</th>
            <th class="text-center text-nowrap" title="Có mặt + Muộn / tổng buổi đã qua trong tháng">Đã học</th>
            <th class="text-center">Vắng</th>
            <th class="text-center">Vắng CP</th>
            <th class="text-center">Chưa ĐD</th>
            <th class="text-center text-nowrap">Cần bù</th>
            <th>Buổi thiếu (gợi ý bù)</th>
        </tr>
        </thead>
        <tbody>
        @forelse($classStudents as $student)
            @php
                $p = $studentProgressById[$student->id] ?? [];
                $attended = (int) ($p['attended'] ?? 0);
                $eligible = (int) ($p['eligible'] ?? 0);
                $missing = $p['missing_sessions'] ?? [];
            @endphp
            <tr>
                <td>
                    <a href="{{ route('admin.students.show', $student) }}" class="font-weight-bold text-dark">{{ $student->name }}</a>
                </td>
                <td class="text-center">
                    <span class="font-weight-bold text-success">{{ $attended }}</span>
                    <span class="text-muted">/ {{ $eligible }}</span>
                </td>
                <td class="text-center">{{ (int) ($p['absent'] ?? 0) }}</td>
                <td class="text-center">{{ (int) ($p['excused'] ?? 0) }}</td>
                <td class="text-center">{{ (int) ($p['not_marked'] ?? 0) }}</td>
                <td class="text-center">
                    @if(($p['needs_makeup'] ?? 0) > 0)
                        <span class="badge badge-warning">{{ $p['needs_makeup'] }}</span>
                    @else
                        <span class="text-muted">0</span>
                    @endif
                </td>
                <td class="small">
                    @if($missing === [])
                        <span class="text-muted">—</span>
                    @else
                        @foreach($missing as $m)
                            <span class="d-inline-block mr-2 mb-1">
                                <span class="badge badge-light border {{ !empty($m['has_makeup']) ? 'text-success' : 'text-dark' }}">
                                    {{ $m['date_label'] }}@if(!empty($m['time'])) · {{ $m['time'] }}@endif
                                    @if(!empty($m['has_makeup']))
                                        · đã có lịch bù
                                    @endif
                                </span>
                            </span>
                        @endforeach
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">Chưa có học viên trong lớp.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
