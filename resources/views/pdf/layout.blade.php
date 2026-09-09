<!DOCTYPE html>
<html lang="vi">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', $docTitle ?? 'Tài liệu')</title>
    <style>
        @page { margin: 22mm 18mm 18mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.45;
            margin: 0;
        }

        /* —— Header: trái = trung tâm, phải = tiêu đề —— */
        .doc-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .doc-header td {
            vertical-align: middle;
            padding: 0;
        }
        .doc-header .org-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 3px;
        }
        .doc-header .org-address {
            font-size: 10.5px;
            color: #222;
            margin: 0;
        }
        .doc-header .doc-title {
            text-align: right;
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin: 0;
        }

        .doc-rule {
            border: 0;
            border-top: 1.5px solid #000;
            margin: 8px 0 12px;
        }

        table.layout { width: 100%; border-collapse: collapse; }
        table.layout td { vertical-align: top; }

        .muted { color: #333; }
        .right { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }

        p { margin: 0 0 8px; }

        /* —— Bảng kiểu Word —— */
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px;
        }
        table.items th,
        table.items td {
            border: 1px solid #000;
            padding: 6px 7px;
            vertical-align: top;
        }
        table.items th {
            background: #f0f0f0;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }

        .kv {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .kv td {
            padding: 3px 0;
            vertical-align: top;
        }
        .kv .lbl {
            width: 140px;
            color: #222;
        }

        .section {
            margin: 14px 0 6px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .note {
            margin-top: 8px;
            font-size: 11px;
        }

        .totals {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
            margin-top: 4px;
            margin-bottom: 10px;
        }
        .totals td {
            padding: 4px 0;
            border-bottom: 1px solid #ccc;
        }
        .totals .grand td {
            border-bottom: none;
            border-top: 1.5px solid #000;
            padding-top: 6px;
            font-weight: bold;
            font-size: 12.5px;
        }

        /* —— Lịch tháng —— */
        table.cal {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 8px 0 12px;
        }
        table.cal th,
        table.cal td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
        }
        table.cal th {
            background: #f0f0f0;
            font-size: 10px;
            text-align: center;
            font-weight: bold;
        }
        table.cal td { height: 64px; font-size: 9px; }
        table.cal td.out { background: #f7f7f7; color: #666; }
        .day-num { font-weight: bold; font-size: 10px; margin-bottom: 2px; }
        .slot { display: block; margin-bottom: 2px; line-height: 1.25; }
        .slot-cancel { text-decoration: line-through; }

        .legend { font-size: 10px; margin: 0 0 8px; }

        .pay-box {
            border: 1px solid #000;
            padding: 10px;
            margin: 12px 0;
        }
        .pay-box img.qr { width: 130px; height: 130px; }

        .badge {
            display: inline-block;
            padding: 1px 5px;
            border: 1px solid #000;
            font-size: 10px;
            font-weight: bold;
        }

        .signs {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
        }
        .signs td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            font-size: 11px;
            padding: 0 8px;
        }
        .signs .role { font-weight: bold; }
        .signs .hint {
            margin-top: 48px;
            font-style: italic;
            color: #444;
        }

        .empty {
            text-align: center;
            padding: 20px;
            border: 1px dashed #000;
            margin: 10px 0;
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    use App\Models\Setting;

    $orgName = Setting::get('center_name', Setting::get('logo_text', config('app.name', 'Trung tâm')));
    $orgAddress = Setting::get('center_address');
    $orgPhone = Setting::get('center_hotline') ?: Setting::get('center_phone');
    $branch = $branch ?? null;
    $addressLine = $branch?->address ?: $orgAddress;
    $phoneLine = $branch?->phone ?: $orgPhone;
@endphp

<table class="doc-header">
    <tr>
        <td style="width:58%">
            <div class="org-name">{{ $orgName }}</div>
            @if($addressLine)
                <div class="org-address">{{ $addressLine }}</div>
            @endif
            @if($phoneLine)
                <div class="org-address">Điện thoại: {{ $phoneLine }}</div>
            @endif
        </td>
        <td style="width:42%">
            <div class="doc-title">@yield('docTitle', $docTitle ?? 'Tài liệu')</div>
        </td>
    </tr>
</table>

<hr class="doc-rule">

@yield('content')

@hasSection('signs')
    @yield('signs')
@endif
</body>
</html>
