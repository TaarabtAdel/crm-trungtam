<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cài đặt') — CRM Trung Tâm</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f6f8; color: #1a1a1a; margin: 0; line-height: 1.5; }
        .wrap { max-width: 560px; margin: 2.5rem auto; padding: 0 1rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.5rem 1.75rem; box-shadow: 0 4px 16px rgba(0,0,0,.04); }
        h1 { font-size: 1.35rem; margin: 0 0 .35rem; }
        .muted { color: #64748b; font-size: .95rem; }
        .steps { display: flex; gap: .5rem; margin: 1rem 0 1.25rem; flex-wrap: wrap; }
        .steps span { font-size: .8rem; padding: .25rem .6rem; border-radius: 999px; background: #e2e8f0; color: #475569; }
        .steps span.on { background: #0d7a6f; color: #fff; }
        label { display: block; font-weight: 600; font-size: .9rem; margin: .75rem 0 .3rem; }
        input[type=text], input[type=password], input[type=email], input[type=url], input[type=number] {
            width: 100%; box-sizing: border-box; padding: .55rem .7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem;
        }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        .btn { display: inline-block; margin-top: 1.1rem; background: #0d7a6f; color: #fff; border: 0; border-radius: 6px; padding: .65rem 1.1rem; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn:hover { background: #085a52; }
        .btn-ghost { background: #fff; color: #0d7a6f; border: 1px solid #0d7a6f; }
        .alert { padding: .75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: .95rem; }
        .alert-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        ul.check { padding-left: 1.1rem; margin: .75rem 0; }
        ul.check li.ok { color: #047857; }
        ul.check li.bad { color: #b91c1c; }
        .check-box { display: flex; align-items: center; gap: .5rem; margin-top: .85rem; font-weight: 500; }
        .check-box input { width: auto; }
        .footer { text-align: center; margin-top: 1.25rem; font-size: .85rem; color: #94a3b8; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        @yield('content')
    </div>
    <p class="footer">CRM Trung Tâm · Wizard cài đặt</p>
</div>
</body>
</html>
