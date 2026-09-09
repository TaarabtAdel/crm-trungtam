@extends('pdf.layout')

@php
    $fmt = [\App\Support\VietnameseCurrency::class, 'format'];

    $branch = $invoice->branch;
    $docTitle = 'Hóa đơn học phí';

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

    $student = $invoice->student;
    $sessionDates = collect($billedSessions ?? [])
        ->map(fn ($s) => optional($s->session_date)->format('d/m'))
        ->filter()
        ->implode(', ');
@endphp

@section('title', $docTitle.' — '.$invoice->code)
@section('docTitle', $docTitle)

@section('content')
<table class="kv">
    <tr>
        <td class="lbl">Số hóa đơn:</td>
        <td><strong>{{ $invoice->code ?: ('HD-'.$invoice->id) }}</strong></td>
        <td class="lbl">Ngày lập:</td>
        <td>{{ $invoice->created_at?->format('d/m/Y') ?: now()->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="lbl">Trạng thái:</td>
        <td>{{ $invoice->statusLabel() }}</td>
        <td class="lbl">Hạn thanh toán:</td>
        <td>{{ optional($invoice->due_date)->format('d/m/Y') ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Học viên:</td>
        <td><strong>{{ $student?->name ?: '—' }}</strong></td>
        <td class="lbl">Hình thức:</td>
        <td>{{ $invoice->feeTypeLabel() }}</td>
    </tr>
    <tr>
        <td class="lbl">Điện thoại HV:</td>
        <td>{{ $student?->phone ?: '—' }}</td>
        <td class="lbl">Nhân viên Sales:</td>
        <td>{{ $invoice->sales?->name ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Phụ huynh:</td>
        <td colspan="3">
            {{ $student?->parent_name ?: '—' }}
            @if($student?->parent_phone) ({{ $student->parent_phone }}) @endif
        </td>
    </tr>
    @if($student?->address)
    <tr>
        <td class="lbl">Địa chỉ:</td>
        <td colspan="3">{{ $student->address }}</td>
    </tr>
    @endif
</table>

<table class="items">
    <thead>
    <tr>
        <th style="width:40px">STT</th>
        <th>Hạng mục</th>
        <th style="width:60px">SL</th>
        <th style="width:110px">Đơn giá</th>
        <th style="width:120px">Thành tiền</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td class="center">1</td>
        <td>
            {{ $itemDesc }}
            @if($sessionDates !== '')
                <div class="muted" style="margin-top:4px;font-size:10px">Buổi tính phí: {{ $sessionDates }}</div>
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
        <td>Tạm tính</td>
        <td class="right">{{ $fmt($gross) }}</td>
    </tr>
    @if($discount > 0)
    <tr>
        <td>Giảm giá@if($invoice->discount_reason) ({{ $invoice->discount_reason }})@endif</td>
        <td class="right">- {{ $fmt($discount) }}</td>
    </tr>
    @endif
    <tr class="grand">
        <td>Tổng thanh toán</td>
        <td class="right">{{ $fmt($amount) }}</td>
    </tr>
    <tr>
        <td>Đã thu</td>
        <td class="right">{{ $fmt($invoice->paid_amount) }}</td>
    </tr>
    <tr>
        <td><strong>Còn lại</strong></td>
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
    <div class="bold" style="margin-bottom:8px">Thanh toán chuyển khoản (VietQR)</div>
    <table class="layout">
        <tr>
            <td style="width:150px" class="center">
                @if(!empty($qrDataUri))
                    <img class="qr" src="{{ $qrDataUri }}" alt="VietQR">
                @else
                    <div class="muted" style="padding:30px 8px;border:1px dashed #000">Không tải được QR</div>
                @endif
            </td>
            <td style="padding-left:12px">
                <div>Ngân hàng: <strong>{{ $branch->bankDisplayName() }}</strong></div>
                <div>Số tài khoản: <strong>{{ $branch->bank_account_number }}</strong></div>
                @if($branch->bank_account_name)
                    <div>Chủ tài khoản: <strong>{{ $branch->bank_account_name }}</strong></div>
                @endif
                <div style="margin-top:6px">Số tiền: <strong>{{ $fmt($qrPayAmount) }}</strong></div>
                <div>Nội dung CK: <strong>{{ $invoice->code }}</strong></div>
            </td>
        </tr>
    </table>
</div>
@endif

@if($invoice->payments->count())
    <div class="section">Lịch sử thanh toán</div>
    <table class="items">
        <thead>
        <tr>
            <th style="width:40px">STT</th>
            <th>Ngày thu</th>
            <th>Phương thức</th>
            <th>Số tiền</th>
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
    <div class="section">Lịch trả góp</div>
    <table class="items">
        <thead>
        <tr>
            <th style="width:50px">Kỳ</th>
            <th>Hạn</th>
            <th>Số tiền</th>
            <th>Đã thu</th>
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
@endsection

@section('signs')
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
@endsection
