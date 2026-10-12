@php
    $studentProgressById = $studentProgressById ?? [];
    $month = $month ?? '';
    $availableMonths = $availableMonths ?? [];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:.5rem">
    <div>
        <strong>Tình hình học tập</strong>
        <p class="small text-muted mb-0">
            Buổi đã học = <em>Có mặt</em> / <em>Muộn</em>.
            Cột <strong>Danh sách ngày vắng</strong>: từng ngày điểm danh <em>Vắng</em> (đỏ); phía dưới là buổi <em>chưa điểm danh</em> nếu có.
            @if($month === '')<span class="d-block">Đang xem <strong>tất cả</strong> buổi đã qua trên lịch lớp.</span>@endif
            Sắp xếp bù trên tab <strong>Thời khóa biểu</strong> → thêm buổi → chọn <em>Buổi bù cho</em>.
        </p>
    </div>
    <form method="GET" action="{{ route('admin.classes.show', $class) }}" class="d-flex align-items-center mb-0" style="gap:.5rem">
        <input type="hidden" name="tab" value="progress">
        <label class="small text-muted mb-0">Tháng</label>
        <select name="month" class="form-control form-control-sm" style="width:170px" onchange="this.form.submit()">
            <option value="" @selected($month === '')>Tất cả</option>
            @foreach($availableMonths as $ym)
                <option value="{{ $ym }}" @selected($month === $ym)>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('m/Y') }}
                </option>
            @endforeach
            @if($month !== '' && ! in_array($month, $availableMonths, true))
                <option value="{{ $month }}" selected>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}</option>
            @endif
        </select>
        <a href="{{ route('admin.classes.show', array_filter(['class' => $class, 'tab' => 'timetable', 'month' => $month !== '' ? $month : null])) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-calendar-plus"></i> {{ $month !== '' ? 'TKB tháng này' : 'TKB' }}
        </a>
    </form>
</div>

<div class="table-responsive border rounded">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
        <tr>
            <th>Học viên</th>
            <th>Tên người thân</th>
            <th class="text-center text-nowrap" title="Có mặt + Muộn / tổng buổi đã qua trong tháng">Đã học</th>
            <th class="text-center">Vắng</th>
            <th class="text-center">Vắng CP</th>
            <th class="text-center">Chưa ĐD</th>
            <th class="text-center text-nowrap" title="Buổi vắng hoặc chưa điểm danh (không tính vắng có phép)">Cần bù</th>
            <th style="min-width:9rem">Danh sách ngày vắng</th>
        </tr>
        </thead>
        <tbody>
        @forelse($classStudents as $student)
            @php
                $p = $studentProgressById[$student->id] ?? [];
                $attended = (int) ($p['attended'] ?? 0);
                $eligible = (int) ($p['eligible'] ?? 0);
                $missing = $p['missing_sessions'] ?? [];
                $absentDays = collect($missing)
                    ->filter(fn ($m) => ($m['reason'] ?? '') === 'vắng')
                    ->sortBy('date')
                    ->values();
                $unmarkedDays = collect($missing)
                    ->filter(fn ($m) => ($m['reason'] ?? '') !== 'vắng')
                    ->sortBy('date')
                    ->values();
            @endphp
            <tr>
                <td>
                    <a href="{{ route('admin.students.show', $student) }}" class="font-weight-bold text-dark">{{ $student->name }}</a>
                </td>
                <td class="small">{{ $student->parent_name ?: '—' }}</td>
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
                    @if($absentDays->isEmpty() && $unmarkedDays->isEmpty())
                        <span class="text-muted">—</span>
                    @else
                        @if($absentDays->isNotEmpty())
                            <ul class="list-unstyled mb-0 pl-2">
                                @foreach($absentDays as $m)
                                    <li class="{{ !empty($m['has_makeup']) ? 'text-success' : 'text-danger' }}">
                                        {{ $m['date_label'] }}
                                        @if(!empty($m['time']))<span class="text-muted"> · {{ $m['time'] }}</span>@endif
                                        @if(!empty($m['has_makeup']))<span class="text-muted"> · đã lên bù</span>@endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if($unmarkedDays->isNotEmpty())
                            <div class="text-muted mt-1 {{ $absentDays->isNotEmpty() ? 'border-top pt-1' : '' }}">
                                <span class="d-block small font-weight-bold text-secondary">Chưa điểm danh</span>
                                <ul class="list-unstyled mb-0 pl-2">
                                    @foreach($unmarkedDays as $m)
                                        <li>
                                            {{ $m['date_label'] }}
                                            @if(!empty($m['time'])) · {{ $m['time'] }}@endif
                                            @if(!empty($m['has_makeup'])) · đã lên bù@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Chưa có học viên trong lớp.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
