<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Báo cáo tài chính</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        .right { text-align: right; }
    </style>
</head>
<body>
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp
<h1>Báo cáo tài chính</h1>
<p>{{ $label }} ({{ $from->format('d/m/Y') }} - {{ $to->format('d/m/Y') }})</p>
<table>
    <tr><th>Chỉ tiêu</th><th class="right">Giá trị</th></tr>
    <tr><td>Doanh thu</td><td class="right">{{ $fmt($data['revenue']) }}</td></tr>
    <tr><td>Hoàn tiền</td><td class="right">{{ $fmt($data['refunds']) }}</td></tr>
    <tr><td>Thu ròng</td><td class="right">{{ $fmt($data['net_revenue']) }}</td></tr>
    <tr><td>Chi phí</td><td class="right">{{ $fmt($data['expenses']) }}</td></tr>
    <tr><td><strong>Lãi/Lỗ</strong></td><td class="right"><strong>{{ $fmt($data['profit']) }}</strong></td></tr>
</table>
</body>
</html>
