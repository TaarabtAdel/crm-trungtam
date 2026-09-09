@extends('pdf.layout')

@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $docTitle = 'Báo cáo tài chính';
@endphp

@section('title', 'Báo cáo tài chính')
@section('docTitle', $docTitle)

@section('content')
<table class="kv">
    <tr>
        <td class="lbl">Kỳ báo cáo:</td>
        <td>{{ $label }}</td>
        <td class="lbl">Thời gian:</td>
        <td>{{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}</td>
    </tr>
</table>
<table class="items">
    <thead>
    <tr>
        <th>Chỉ tiêu</th>
        <th style="width:160px">Giá trị</th>
    </tr>
    </thead>
    <tbody>
    <tr><td>Doanh thu</td><td class="right">{{ $fmt($data['revenue']) }}</td></tr>
    <tr><td>Hoàn tiền</td><td class="right">{{ $fmt($data['refunds']) }}</td></tr>
    <tr><td>Thu ròng</td><td class="right">{{ $fmt($data['net_revenue']) }}</td></tr>
    <tr><td>Chi phí</td><td class="right">{{ $fmt($data['expenses']) }}</td></tr>
    <tr><td>Trong đó lương GV đã chi</td><td class="right">{{ $fmt($data['salary_paid'] ?? 0) }}</td></tr>
    <tr><td>Lương GV tạm tính (buổi HT)</td><td class="right">{{ $fmt($data['payroll_accrued'] ?? 0) }}</td></tr>
    <tr><td>Trong đó lương NV đã chi</td><td class="right">{{ $fmt($data['staff_salary_paid'] ?? 0) }}</td></tr>
    <tr><td>Lương NV tạm tính (công)</td><td class="right">{{ $fmt($data['staff_payroll_accrued'] ?? 0) }}</td></tr>
    <tr>
        <td><strong>Lãi / Lỗ</strong></td>
        <td class="right"><strong>{{ $fmt($data['profit']) }}</strong></td>
    </tr>
    </tbody>
</table>
@endsection
