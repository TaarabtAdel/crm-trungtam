@extends('pdf.layout')

@php
    use App\Services\ClassTimetableGenerator;
    use Carbon\Carbon;

    $branch = $class->branch;
    $scheduleDays = $class->schedule_days ?? [];
    $timeRange = trim(
        ($class->start_time ? substr((string) $class->start_time, 0, 5) : '')
        .' – '.
        ($class->end_time ? substr((string) $class->end_time, 0, 5) : ''),
        ' –'
    );
    $periodLabel = $month !== ''
        ? 'Tháng '.Carbon::createFromFormat('Y-m', $month)->format('m/Y')
        : 'Toàn bộ lịch học';

    $docTitle = 'Thời khóa biểu';

    $weekdayFull = [
        1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm',
        5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 7 => 'Chủ Nhật',
    ];
@endphp

@section('title', 'Thời khóa biểu — '.$class->name)
@section('docTitle', $docTitle)

@section('content')
<table class="kv">
    <tr>
        <td class="lbl">Lớp:</td>
        <td colspan="3"><strong>{{ $class->name }}@if($class->code) ({{ $class->code }})@endif</strong></td>
    </tr>
    <tr>
        <td class="lbl">Kỳ lịch:</td>
        <td>{{ $periodLabel }}</td>
        <td class="lbl">Môn học:</td>
        <td>{{ $class->subject?->name ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Giáo viên:</td>
        <td>{{ $class->teacher?->name ?: 'Chưa phân công' }}</td>
        <td class="lbl">Phòng:</td>
        <td>{{ $class->room ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Lịch cố định:</td>
        <td>
            {{ $scheduleDays ? implode(', ', $scheduleDays) : '—' }}
            @if($timeRange) · {{ $timeRange }} @endif
        </td>
        <td class="lbl">Số buổi:</td>
        <td>
            {{ $sessions->count() }}
            (đã học {{ $sessions->where('status', 'completed')->count() }},
            nghỉ {{ $sessions->where('status', 'cancelled')->count() }})
        </td>
    </tr>
    @if($class->start_date || $class->end_date)
    <tr>
        <td class="lbl">Thời gian khóa:</td>
        <td colspan="3">
            {{ optional($class->start_date)->format('d/m/Y') ?: '…' }}
            →
            {{ optional($class->end_date)->format('d/m/Y') ?: '…' }}
        </td>
    </tr>
    @endif
</table>

@if($month !== '' && ! empty($calendarWeeks))
    <div class="section">Lịch tháng {{ Carbon::createFromFormat('Y-m', $month)->format('m/Y') }}</div>
    <div class="legend">Ghi chú: chữ gạch ngang = nghỉ/hủy · “Bù” = buổi học bù</div>
    <table class="cal">
        <thead>
        <tr>
            <th>Thứ 2</th><th>Thứ 3</th><th>Thứ 4</th><th>Thứ 5</th><th>Thứ 6</th><th>Thứ 7</th><th>CN</th>
        </tr>
        </thead>
        <tbody>
        @foreach($calendarWeeks as $week)
            <tr>
                @foreach($week as $cell)
                    <td class="{{ $cell['in_month'] ? '' : 'out' }}">
                        <div class="day-num">{{ $cell['date']->format('d') }}</div>
                        @foreach($cell['sessions'] as $s)
                            @php
                                $t = $s->start_time ? substr((string) $s->start_time, 0, 5) : '';
                                $t2 = $s->end_time ? substr((string) $s->end_time, 0, 5) : '';
                            @endphp
                            <div class="slot {{ $s->status === 'cancelled' ? 'slot-cancel' : '' }}">
                                {{ $t }}{{ $t2 ? '–'.$t2 : '' }}
                                @if($s->isMakeup()) · Bù @endif
                                @if($s->status === 'cancelled') · Nghỉ @endif
                            </div>
                        @endforeach
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<div class="section">Chi tiết các buổi học</div>

@if($sessions->isEmpty())
    <div class="empty">Chưa có buổi học trong khoảng thời gian này.</div>
@else
    <table class="items">
        <thead>
        <tr>
            <th style="width:40px">STT</th>
            <th style="width:90px">Ngày</th>
            <th style="width:90px">Thứ</th>
            <th style="width:100px">Giờ học</th>
            <th>Giáo viên</th>
            <th style="width:120px">Ghi chú</th>
        </tr>
        </thead>
        <tbody>
        @foreach($sessions as $i => $session)
            @php
                $iso = $session->session_date->dayOfWeekIso;
                $time = trim(
                    ($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
                    .' – '.
                    ($session->end_time ? substr((string) $session->end_time, 0, 5) : ''),
                    ' –'
                );
                $isSub = $session->teacher_id
                    && $class->teacher_id
                    && (int) $session->teacher_id !== (int) $class->teacher_id;
                $note = match ($session->status) {
                    'cancelled' => 'Nghỉ / Hủy',
                    'completed' => 'Đã học',
                    default => 'Đã lên lịch',
                };
                if ($session->isMakeup()) {
                    $note .= $session->makeupOf?->session_date
                        ? ' · Bù ('.$session->makeupOf->session_date->format('d/m').')'
                        : ' · Buổi bù';
                }
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center">{{ $session->session_date->format('d/m/Y') }}</td>
                <td>{{ $weekdayFull[$iso] ?? ClassTimetableGenerator::dayLabel($session->session_date) }}</td>
                <td class="center">{{ $time ?: '—' }}</td>
                <td>
                    {{ $session->teacher?->name ?: ($class->teacher?->name ?: '—') }}
                    @if($isSub) <span class="muted">(dạy thay)</span> @endif
                </td>
                <td>{{ $note }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<div class="note">
    <strong>Lưu ý:</strong> Quý phụ huynh vui lòng đưa học viên đến lớp đúng giờ theo lịch trên.
    Mọi thay đổi lịch sẽ được trung tâm thông báo lại.
</div>
@endsection

@section('signs')
<table class="signs">
    <tr>
        <td>
            <div class="role">Phụ huynh xác nhận</div>
            <div class="muted">(Ký, ghi rõ họ tên)</div>
            <div class="hint">&nbsp;</div>
        </td>
        <td></td>
        <td>
            <div class="role">Đại diện trung tâm</div>
            <div class="muted">(Ký, đóng dấu)</div>
            <div class="hint">&nbsp;</div>
        </td>
    </tr>
</table>
@endsection
