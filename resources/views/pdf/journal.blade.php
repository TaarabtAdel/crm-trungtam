@extends('pdf.layout')

@php
    $branch = $class->branch;
    $docTitle = 'Nhật ký buổi học';
    $teacherName = $session->teacher?->name ?? $class->teacher?->name ?? '—';
    $timeRange = trim(
        ($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
        .' – '.
        ($session->end_time ? substr((string) $session->end_time, 0, 5) : ''),
        ' –'
    );
@endphp

@section('title', 'Nhật ký — '.$class->name.' — '.$journal->session_date->format('d/m/Y'))
@section('docTitle', $docTitle)

@section('content')
<table class="kv">
    <tr>
        <td class="lbl">Lớp:</td>
        <td colspan="3"><strong>{{ $class->name }}@if($class->code) ({{ $class->code }})@endif</strong></td>
    </tr>
    <tr>
        <td class="lbl">Ngày học:</td>
        <td>{{ $journal->session_date->format('d/m/Y') }}@if($timeRange) · {{ $timeRange }}@endif</td>
        <td class="lbl">Giáo viên:</td>
        <td>{{ $teacherName }}</td>
    </tr>
    <tr>
        <td class="lbl">Môn học:</td>
        <td>{{ $class->subject?->name ?: '—' }}</td>
        <td class="lbl">Chi nhánh:</td>
        <td>{{ $branch?->name ?: '—' }}</td>
    </tr>
</table>

<div class="section">Sĩ số & điểm danh</div>
<table class="items">
    <thead>
    <tr>
        <th>Sĩ số</th>
        <th>Có mặt</th>
        <th>Vắng KP</th>
        <th>Vắng CP</th>
        <th>Đi muộn</th>
    </tr>
    </thead>
    <tbody>
    <tr class="center">
        <td>{{ $journal->enrollment_count }}</td>
        <td>{{ $journal->present_count }}</td>
        <td>{{ $journal->absent_count }}</td>
        <td>{{ $journal->excused_count }}</td>
        <td>{{ $journal->late_count }}</td>
    </tr>
    </tbody>
</table>

<div class="section">Nội dung buổi học</div>
<table class="kv">
    <tr>
        <td class="lbl">Bài học:</td>
        <td>{{ $journal->lesson_title ?: '—' }}</td>
    </tr>
</table>

<p class="bold" style="margin:8px 0 4px">Nội dung giảng dạy</p>
<div class="note" style="border:1px solid #000;padding:8px;min-height:48px;white-space:pre-wrap">{{ $journal->content ?: '—' }}</div>

<p class="bold" style="margin:12px 0 4px">Nhận xét</p>
<div class="note" style="border:1px solid #000;padding:8px;min-height:40px;white-space:pre-wrap">{{ $journal->remarks ?: '—' }}</div>

<p class="bold" style="margin:12px 0 4px">Bài tập về nhà</p>
<div class="note" style="border:1px solid #000;padding:8px;min-height:56px;white-space:pre-wrap">{{ $journal->homework ?: '—' }}</div>

@if($journal->filled_at)
    <p class="note muted" style="margin-top:12px">
        Cập nhật: {{ $journal->filled_at->format('d/m/Y H:i') }}
        @if($journal->filledByUser)
            · {{ $journal->filledByUser->name }}
        @endif
    </p>
@endif

<p class="note muted" style="margin-top:10px">
    Tài liệu dành cho phụ huynh theo dõi buổi học của học viên.
</p>
@endsection

@section('signs')
<table class="signs">
    <tr>
        <td>
            <div class="role">Giáo viên</div>
            <div class="hint">(Ký, ghi rõ họ tên)</div>
        </td>
        <td>
            <div class="role">Phụ huynh</div>
            <div class="hint">(Ký xác nhận đã nhận)</div>
        </td>
        <td>
            <div class="role">Trung tâm</div>
            <div class="hint">(Ký, đóng dấu)</div>
        </td>
    </tr>
</table>
@endsection
