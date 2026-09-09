@extends('pdf.layout')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $teacher = $detail['teacher'];
    $branch = $teacher->branch;
    $docTitle = 'Bảng lương giáo viên';
@endphp

@section('title', 'Bảng lương — '.$teacher->name.' '.$detail['month'].'/'.$detail['year'])
@section('docTitle', $docTitle)

@section('content')
<table class="kv">
    <tr>
        <td class="lbl">Giáo viên:</td>
        <td><strong>{{ $teacher->name }}</strong></td>
        <td class="lbl">Tháng:</td>
        <td><strong>{{ $detail['month'] }}/{{ $detail['year'] }}</strong></td>
    </tr>
    <tr>
        <td class="lbl">Chi nhánh:</td>
        <td>{{ $teacher->branch?->name ?: '—' }}</td>
        <td class="lbl">Kỳ tính:</td>
        <td>{{ $detail['from']->format('d/m/Y') }} — {{ $detail['to']->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="lbl">Đơn giá/giờ:</td>
        <td>{{ $fmt($detail['rate']) }}@if($detail['rate_mixed']) * @endif</td>
        <td class="lbl">Số buổi / giờ:</td>
        <td>{{ $detail['sessions']->count() }} buổi · {{ $detail['hours'] }}h</td>
    </tr>
    <tr>
        <td class="lbl">Gốc (buổi HT):</td>
        <td>{{ $fmt($detail['accrued']) }}</td>
        <td class="lbl">Thưởng / Phạt / Ứng:</td>
        <td>
            {{ $fmt($detail['bonus'] ?? 0) }}
            / {{ $fmt($detail['penalty'] ?? 0) }}
            / {{ $fmt($detail['advance'] ?? 0) }}
        </td>
    </tr>
    <tr>
        <td class="lbl">Phải trả (net):</td>
        <td colspan="3"><strong>{{ $fmt($detail['net'] ?? $detail['accrued']) }}</strong></td>
    </tr>
</table>

@if(($detail['adjustments'] ?? collect())->isNotEmpty())
    <div class="section">Điều chỉnh (thưởng / phạt / ứng)</div>
    <table class="items">
        <thead>
        <tr>
            <th style="width:40px">STT</th>
            <th style="width:90px">Loại</th>
            <th style="width:100px">Số tiền</th>
            <th>Ghi chú</th>
        </tr>
        </thead>
        <tbody>
        @foreach($detail['adjustments'] as $i => $adj)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $adj->typeLabel() }}</td>
                <td class="right">{{ $fmt($adj->amount) }}</td>
                <td>{{ $adj->note }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<div class="section">Chi tiết buổi dạy</div>

@if($detail['sessions']->isEmpty())
    <div class="empty">Tháng này chưa có buổi hoàn thành.</div>
@else
    <table class="items">
        <thead>
        <tr>
            <th style="width:36px">STT</th>
            <th style="width:90px">Ngày</th>
            <th>Lớp</th>
            <th style="width:95px">Giờ học</th>
            <th style="width:50px">Giờ</th>
            <th style="width:90px">Đơn giá</th>
            <th style="width:100px">Thành tiền</th>
        </tr>
        </thead>
        <tbody>
        @foreach($detail['sessions'] as $i => $session)
            @php
                $time = trim(
                    ($session->start_time ? substr((string) $session->start_time, 0, 5) : '')
                    .' – '.
                    ($session->end_time ? substr((string) $session->end_time, 0, 5) : ''),
                    ' –'
                );
                $className = $session->courseClass?->name ?: '—';
                if ($session->courseClass?->code) {
                    $className .= ' ('.$session->courseClass->code.')';
                }
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center">{{ $session->session_date->format('d/m/Y') }}</td>
                <td>{{ $className }}</td>
                <td class="center">{{ $time ?: '—' }}</td>
                <td class="center">{{ number_format($session->hours(), 2, ',', '.') }}</td>
                <td class="right">{{ $fmt($session->teacherPayRate()) }}</td>
                <td class="right">{{ $fmt($session->teacherPayAmount()) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="4" class="right bold">Tổng</td>
            <td class="center bold">{{ number_format($detail['hours'], 2, ',', '.') }}</td>
            <td></td>
            <td class="right bold">{{ $fmt($detail['accrued']) }}</td>
        </tr>
        </tbody>
    </table>
    @if($detail['rate_mixed'])
        <div class="note">(*) Một số buổi dùng đơn giá theo lớp (khác mức mặc định của GV).</div>
    @endif
@endif

<div class="section">Tổng kết</div>
<table class="items">
    <tbody>
    <tr><td>Gốc buổi dạy</td><td class="right">{{ $fmt($detail['accrued']) }}</td></tr>
    <tr><td>+ Thưởng</td><td class="right">{{ $fmt($detail['bonus'] ?? 0) }}</td></tr>
    <tr><td>− Phạt</td><td class="right">{{ $fmt($detail['penalty'] ?? 0) }}</td></tr>
    <tr><td>− Ứng trước</td><td class="right">{{ $fmt($detail['advance'] ?? 0) }}</td></tr>
    <tr><td class="bold">Phải trả (net)</td><td class="right bold">{{ $fmt($detail['net'] ?? $detail['accrued']) }}</td></tr>
    </tbody>
</table>
@endsection

@section('signs')
<table class="signs">
    <tr>
        <td>
            <div class="role">Giáo viên xác nhận</div>
            <div class="muted">(Ký, họ tên)</div>
            <div class="hint">&nbsp;</div>
        </td>
        <td>
            <div class="role">Kế toán</div>
            <div class="muted">(Ký, họ tên)</div>
            <div class="hint">&nbsp;</div>
        </td>
        <td>
            <div class="role">Ban giám đốc</div>
            <div class="muted">(Ký, đóng dấu)</div>
            <div class="hint">&nbsp;</div>
        </td>
    </tr>
</table>
@endsection
