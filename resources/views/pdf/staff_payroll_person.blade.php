@extends('pdf.layout')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $user = $detail['user'];
    $branch = $user->branch;
    $docTitle = 'Bảng lương nhân viên';
@endphp

@section('title', 'Bảng lương — '.$user->name.' '.$detail['month'].'/'.$detail['year'])
@section('docTitle', $docTitle)

@section('content')
<table class="kv">
    <tr>
        <td class="lbl">Nhân viên:</td>
        <td><strong>{{ $user->name }}</strong></td>
        <td class="lbl">Tháng:</td>
        <td><strong>{{ $detail['month'] }}/{{ $detail['year'] }}</strong></td>
    </tr>
    <tr>
        <td class="lbl">Chi nhánh:</td>
        <td>{{ $user->branch?->name ?: '—' }}</td>
        <td class="lbl">Kỳ tính:</td>
        <td>{{ $detail['from']->format('d/m/Y') }} — {{ $detail['to']->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="lbl">Lương/ngày:</td>
        <td>{{ $fmt($detail['rate']) }}</td>
        <td class="lbl">Tổng công:</td>
        <td>
            {{ $detail['days'] }} ngày
            ({{ $detail['present_count'] }} đủ · {{ $detail['half_count'] }} nửa)
        </td>
    </tr>
    <tr>
        <td class="lbl">Gốc (công):</td>
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

<div class="section">Chi tiết ngày công</div>

@if($detail['attendances']->isEmpty())
    <div class="empty">Tháng này chưa có chấm công.</div>
@else
    <table class="items">
        <thead>
        <tr>
            <th style="width:36px">STT</th>
            <th style="width:95px">Ngày</th>
            <th>Trạng thái</th>
            <th style="width:70px">Công</th>
            <th style="width:110px">Thành tiền</th>
            <th>Ghi chú</th>
        </tr>
        </thead>
        <tbody>
        @foreach($detail['attendances'] as $i => $att)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center">{{ $att->work_date->format('d/m/Y') }}</td>
                <td>{{ $att->statusLabel() }}</td>
                <td class="center">{{ number_format($att->dayUnits(), 1, ',', '.') }}</td>
                <td class="right">{{ $fmt($att->payAmount($detail['rate'])) }}</td>
                <td>{{ $att->note ?: '—' }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="3" class="right bold">Tổng</td>
            <td class="center bold">{{ number_format($detail['days'], 1, ',', '.') }}</td>
            <td class="right bold">{{ $fmt($detail['accrued']) }}</td>
            <td></td>
        </tr>
        </tbody>
    </table>
@endif

<div class="section">Tổng kết</div>
<table class="items">
    <tbody>
    <tr><td>Gốc ngày công</td><td class="right">{{ $fmt($detail['accrued']) }}</td></tr>
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
            <div class="role">Nhân viên xác nhận</div>
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
