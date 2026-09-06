<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->code }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .header { margin-bottom: 20px; }
    </style>
</head>
<body>
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp
<div class="header">
    <h1>{{ \App\Models\Setting::get('logo_text', 'CRM Trung tâm') }}</h1>
    <div class="muted">Hóa đơn học phí</div>
</div>
<p><strong>Mã:</strong> {{ $invoice->code }}<br>
<strong>Học viên:</strong> {{ $invoice->student?->name }}<br>
<strong>Lớp:</strong> {{ $invoice->courseClass?->name ?: '—' }}<br>
<strong>Chi nhánh:</strong> {{ $invoice->branch?->name ?: '—' }}<br>
<strong>Hạn thanh toán:</strong> {{ optional($invoice->due_date)->format('d/m/Y') ?: '—' }}</p>

<table>
    <tr><th>Hạng mục</th><th class="right">Số tiền</th></tr>
    <tr><td>Tổng học phí {{ $invoice->billing_month ? '('.$invoice->billing_month.')' : '' }}</td><td class="right">{{ $fmt($invoice->amount) }}</td></tr>
    <tr><td>Đã thu</td><td class="right">{{ $fmt($invoice->paid_amount) }}</td></tr>
    <tr><td><strong>Còn nợ</strong></td><td class="right"><strong>{{ $fmt($invoice->remaining_amount) }}</strong></td></tr>
</table>

@if($invoice->payments->count())
<h3 style="margin-top:24px">Thanh toán</h3>
<table>
    <tr><th>Ngày</th><th>Phương thức</th><th class="right">Số tiền</th></tr>
    @foreach($invoice->payments as $p)
        <tr>
            <td>{{ $p->paid_at?->format('d/m/Y H:i') }}</td>
            <td>{{ $p->methodLabel() }}</td>
            <td class="right">{{ $fmt($p->amount) }}</td>
        </tr>
    @endforeach
</table>
@endif

<p class="muted" style="margin-top:30px">In lúc {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
