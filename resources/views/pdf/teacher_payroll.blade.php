@extends('pdf.layout')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $docTitle = 'Bảng lương giáo viên';
@endphp

@section('title', 'Bảng lương giáo viên '.$report['month'].'/'.$report['year'])
@section('docTitle', $docTitle)

@section('content')
<table class="kv">
    <tr>
        <td class="lbl">Tháng:</td>
        <td><strong>{{ $report['month'] }}/{{ $report['year'] }}</strong></td>
        <td class="lbl">Kỳ tính:</td>
        <td>{{ $report['from']->format('d/m/Y') }} — {{ $report['to']->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="lbl">Tổng gốc:</td>
        <td>{{ $fmt($report['totals']['accrued']) }}</td>
        <td class="lbl">Tổng phải trả (net):</td>
        <td><strong>{{ $fmt($report['totals']['net'] ?? $report['totals']['accrued']) }}</strong></td>
    </tr>
    <tr>
        <td class="lbl">Thưởng / Phạt / Ứng:</td>
        <td>
            {{ $fmt($report['totals']['bonus'] ?? 0) }}
            / {{ $fmt($report['totals']['penalty'] ?? 0) }}
            / {{ $fmt($report['totals']['advance'] ?? 0) }}
        </td>
        <td class="lbl">Tổng giờ / buổi:</td>
        <td>{{ $report['totals']['hours'] }}h / {{ $report['totals']['sessions'] }} buổi</td>
    </tr>
</table>

<p class="note muted">
    Phải trả (net) = Gốc buổi hoàn thành + Thưởng − Phạt − Ứng trước.
</p>

@if(empty($report['rows']))
    <div class="empty">Không có dữ liệu lương trong tháng này.</div>
@else
    <table class="items">
        <thead>
        <tr>
            <th style="width:32px">STT</th>
            <th>Giáo viên</th>
            <th style="width:52px">Buổi</th>
            <th style="width:52px">Giờ</th>
            <th style="width:150px">Gốc / Thưởng / Phạt / Ứng</th>
            <th style="width:95px">Phải trả</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['rows'] as $i => $row)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $row['name'] }}</td>
                <td class="center">{{ $row['sessions'] }}</td>
                <td class="center">{{ $row['hours'] }}</td>
                <td class="right" style="font-size:10.5px;line-height:1.4">
                    Gốc: {{ $fmt($row['accrued']) }}<br>
                    Thưởng: {{ $fmt($row['bonus'] ?? 0) }}<br>
                    Phạt: {{ $fmt($row['penalty'] ?? 0) }}<br>
                    Ứng: {{ $fmt($row['advance'] ?? 0) }}
                </td>
                <td class="right bold">{{ $fmt($row['net'] ?? $row['accrued']) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="2" class="right bold">Tổng</td>
            <td class="center bold">{{ $report['totals']['sessions'] }}</td>
            <td class="center bold">{{ $report['totals']['hours'] }}</td>
            <td class="right bold" style="font-size:10.5px;line-height:1.4">
                Gốc: {{ $fmt($report['totals']['accrued']) }}<br>
                Thưởng: {{ $fmt($report['totals']['bonus'] ?? 0) }}<br>
                Phạt: {{ $fmt($report['totals']['penalty'] ?? 0) }}<br>
                Ứng: {{ $fmt($report['totals']['advance'] ?? 0) }}
            </td>
            <td class="right bold">{{ $fmt($report['totals']['net'] ?? $report['totals']['accrued']) }}</td>
        </tr>
        </tbody>
    </table>
@endif
@endsection

@section('signs')
<table class="signs">
    <tr>
        <td>
            <div class="role">Người lập</div>
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
