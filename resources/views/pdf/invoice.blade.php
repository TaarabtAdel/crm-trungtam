<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->code }}</title>
    <style>
        @page { margin: 28px 32px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.45;
        }
        .top-bar {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .brand {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.3px;
        }
        .brand-sub { color: #6b7280; font-size: 11px; margin-top: 2px; }
        .doc-title {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
        }
        .doc-meta { text-align: right; color: #4b5563; font-size: 11px; margin-top: 4px; }
        .doc-code {
            display: inline-block;
            margin-top: 6px;
            padding: 3px 8px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-weight: bold;
            font-size: 12px;
        }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout td { vertical-align: top; }
        .box {
            border: 1px solid #e5e7eb;
            background: #fafafa;
            padding: 10px 12px;
            min-height: 88px;
        }
        .box-title {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #6b7280;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .box strong { color: #111827; }
        .muted { color: #6b7280; }
        .row-gap { height: 12px; }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.items th {
            background: #111827;
            color: #fff;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 8px;
            text-align: left;
            border: 1px solid #111827;
        }
        table.items td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            vertical-align: top;
        }
        table.items tr:nth-child(even) td { background: #f9fafb; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .totals td {
            padding: 5px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .totals .label { color: #4b5563; }
        .totals .grand td {
            border-bottom: none;
            border-top: 2px solid #111827;
            padding-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }
        .section-title {
            margin: 18px 0 8px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }
        .note {
            margin-top: 10px;
            font-size: 11px;
            color: #4b5563;
        }
        .signs {
            width: 100%;
            margin-top: 36px;
            border-collapse: collapse;
        }
        .signs td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
        }
        .signs .role { font-weight: bold; color: #111827; }
        .signs .hint { color: #9ca3af; margin-top: 48px; font-style: italic; }
        .pay-box {
            margin-top: 16px;
            border: 1px solid #e5e7eb;
            background: #fafafa;
            padding: 12px;
        }
        .pay-box .pay-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 8px;
        }
        .pay-box img.qr {
            width: 140px;
            height: 140px;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-unpaid { background: #fef3c7; color: #92400e; }
        .badge-partial { background: #dbeafe; color: #1e40af; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
@php
    $fmt = [\App\Support\VietnameseCurrency::class, 'format'];

    $centerName = \App\Models\Setting::get('logo_text', 'CRM Trung tâm');
    $gross = (float) ($invoice->gross_amount ?? 0);
    $discount = (float) ($invoice->discount_amount ?? 0);
    $amount = (float) $invoice->amount;
    if ($gross <= 0) {
        $gross = $amount + $discount;
    }

    $qty = max(1, (int) ($invoice->sessions_count ?: 1));
    $unit = $qty > 0 ? round($gross / $qty) : $gross;

    $itemDesc = 'Học phí '.$invoice->feeTypeLabel();
    if ($invoice->courseClass) {
        $itemDesc .= ' — Lớp '.$invoice->courseClass->name;
        if ($invoice->courseClass->code) {
            $itemDesc .= ' ('.$invoice->courseClass->code.')';
        }
    }
    if ($invoice->billing_month) {
        $itemDesc .= ' — Kỳ '.$invoice->billing_month;
    }
    if ((int) $invoice->sessions_count > 0) {
        $itemDesc .= ' — '.$invoice->sessions_count.' buổi';
    }

    $statusClass = match ($invoice->status) {
        'paid' => 'badge-paid',
        'partial' => 'badge-partial',
        'cancelled' => 'badge-cancelled',
        default => 'badge-unpaid',
    };

    $student = $invoice->student;
    $branch = $invoice->branch;
    $sessionDates = collect($billedSessions ?? [])
        ->map(function ($s) {
            return optional($s->session_date)->format('d/m');
        })
        ->filter()
        ->implode(', ');
@endphp

<div class="top-bar">
    <table class="layout">
        <tr>
            <td style="width:58%">
                <div class="brand">{{ $centerName }}</div>
                <div class="brand-sub">
                    @if($branch)
                        {{ $branch->name }}
                        @if($branch->address) · {{ $branch->address }}@endif
                        @if($branch->phone) · {{ $branch->phone }}@endif
                    @else
                        Hóa đơn học phí trung tâm
                    @endif
                </div>
            </td>
            <td style="width:42%">
                <div class="doc-title">Hóa đơn học phí</div>
                <div class="doc-meta">Ngày lập: {{ $invoice->created_at?->format('d/m/Y') ?: now()->format('d/m/Y') }}</div>
                <div class="doc-code">{{ $invoice->code }}</div>
            </td>
        </tr>
    </table>
</div>

<table class="layout">
    <tr>
        <td style="width:49%; padding-right:8px">
            <div class="box">
                <div class="box-title">Thông tin trung tâm</div>
                <div><strong>{{ $centerName }}</strong></div>
                <div>{{ $branch?->name ?: '—' }}</div>
                @if($branch?->address)<div class="muted">{{ $branch->address }}</div>@endif
                @if($branch?->phone)<div class="muted">ĐT: {{ $branch->phone }}</div>@endif
            </div>
        </td>
        <td style="width:49%; padding-left:8px">
            <div class="box">
                <div class="box-title">Khách hàng / Học viên</div>
                <div><strong>{{ $student?->name ?: '—' }}</strong></div>
                @if($student?->phone)<div class="muted">ĐT: {{ $student->phone }}</div>@endif
                @if($student?->parent_name)<div class="muted">PH: {{ $student->parent_name }}@if($student->parent_phone) ({{ $student->parent_phone }})@endif</div>@endif
                @if($student?->email)<div class="muted">Email: {{ $student->email }}</div>@endif
                @if($student?->address)<div class="muted">{{ $student->address }}</div>@endif
            </div>
        </td>
    </tr>
</table>

<div class="row-gap"></div>

<table class="layout" style="margin-bottom:10px">
    <tr>
        <td style="width:25%"><span class="muted">Trạng thái</span><br>
            <span class="badge {{ $statusClass }}">{{ $invoice->statusLabel() }}</span>
        </td>
        <td style="width:25%"><span class="muted">Hạn thanh toán</span><br><strong>{{ optional($invoice->due_date)->format('d/m/Y') ?: '—' }}</strong></td>
        <td style="width:25%"><span class="muted">Hình thức</span><br><strong>{{ $invoice->feeTypeLabel() }}</strong></td>
        <td style="width:25%"><span class="muted">Nhân viên Sales</span><br><strong>{{ $invoice->sales?->name ?: '—' }}</strong></td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:40px" class="center">STT</th>
            <th>Hạng mục</th>
            <th style="width:70px" class="center">SL</th>
            <th style="width:110px" class="right">Đơn giá</th>
            <th style="width:120px" class="right">Thành tiền</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="center">1</td>
            <td>
                {{ $itemDesc }}
                @if($sessionDates !== '')
                    <div class="muted" style="margin-top:4px; font-size:10px">
                        Buổi tính phí: {{ $sessionDates }}
                    </div>
                @endif
            </td>
            <td class="center">{{ $qty }}</td>
            <td class="right">{{ $fmt($unit) }}</td>
            <td class="right">{{ $fmt($gross) }}</td>
        </tr>
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="label">Tạm tính</td>
        <td class="right">{{ $fmt($gross) }}</td>
    </tr>
    @if($discount > 0)
    <tr>
        <td class="label">Giảm giá@if($invoice->discount_reason) ({{ $invoice->discount_reason }})@endif</td>
        <td class="right">- {{ $fmt($discount) }}</td>
    </tr>
    @endif
    <tr class="grand">
        <td>Tổng thanh toán</td>
        <td class="right">{{ $fmt($amount) }}</td>
    </tr>
    <tr>
        <td class="label">Đã thu</td>
        <td class="right">{{ $fmt($invoice->paid_amount) }}</td>
    </tr>
    <tr>
        <td class="label"><strong>Còn lại</strong></td>
        <td class="right"><strong>{{ $fmt($invoice->remaining_amount) }}</strong></td>
    </tr>
</table>

@if($invoice->note)
<div class="note"><strong>Ghi chú:</strong> {{ $invoice->note }}</div>
@endif

@php
    $showQr = $branch && $branch->hasPaymentAccount() && (($qrPayAmount ?? 0) > 0);
@endphp
@if($showQr)
<div class="pay-box">
    <div class="pay-title">Thanh toán chuyển khoản (VietQR)</div>
    <table class="layout">
        <tr>
            <td style="width:160px" class="center">
                @if(!empty($qrDataUri))
                    <img class="qr" src="{{ $qrDataUri }}" alt="VietQR">
                @else
                    <div class="muted" style="padding:40px 10px; border:1px dashed #d1d5db;">Không tải được QR</div>
                @endif
            </td>
            <td style="padding-left:14px">
                <div><span class="muted">Ngân hàng:</span> <strong>{{ $branch->bankDisplayName() }}</strong></div>
                <div><span class="muted">Số tài khoản:</span> <strong>{{ $branch->bank_account_number }}</strong></div>
                @if($branch->bank_account_name)
                    <div><span class="muted">Chủ tài khoản:</span> <strong>{{ $branch->bank_account_name }}</strong></div>
                @endif
                <div style="margin-top:6px"><span class="muted">Số tiền:</span> <strong>{{ $fmt($qrPayAmount) }}</strong></div>
                <div><span class="muted">Nội dung CK:</span> <strong>{{ $invoice->code }}</strong></div>
                <div class="muted" style="margin-top:8px; font-size:10px">Quét QR bằng app ngân hàng để thanh toán đúng số còn lại.</div>
            </td>
        </tr>
    </table>
</div>
@endif

@if($invoice->payments->count())
<div class="section-title">Lịch sử thanh toán</div>
<table class="items">
    <thead>
        <tr>
            <th style="width:40px" class="center">STT</th>
            <th>Ngày thu</th>
            <th>Phương thức</th>
            <th class="right">Số tiền</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->payments as $idx => $payment)
        <tr>
            <td class="center">{{ $idx + 1 }}</td>
            <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?: '—' }}</td>
            <td>{{ $payment->methodLabel() }}</td>
            <td class="right">{{ $fmt($payment->amount) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($invoice->installments->count() > 1)
<div class="section-title">Lịch trả góp</div>
<table class="items">
    <thead>
        <tr>
            <th class="center" style="width:50px">Kỳ</th>
            <th>Hạn</th>
            <th class="right">Số tiền</th>
            <th class="right">Đã thu</th>
            <th>Trạng thái</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->installments as $inst)
        <tr>
            <td class="center">{{ $inst->sequence }}</td>
            <td>{{ optional($inst->due_date)->format('d/m/Y') ?: '—' }}</td>
            <td class="right">{{ $fmt($inst->amount) }}</td>
            <td class="right">{{ $fmt($inst->paid_amount) }}</td>
            <td>{{ $inst->statusLabel() }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<table class="signs">
    <tr>
        <td>
            <div class="role">Người lập phiếu</div>
            <div class="muted">(Ký, họ tên)</div>
            <div class="hint">&nbsp;</div>
        </td>
        <td>
            <div class="role">Thu ngân / Kế toán</div>
            <div class="muted">(Ký, họ tên)</div>
            <div class="hint">&nbsp;</div>
        </td>
        <td>
            <div class="role">Học viên / Phụ huynh</div>
            <div class="muted">(Ký, họ tên)</div>
            <div class="hint">&nbsp;</div>
        </td>
    </tr>
</table>

</body>
</html>
