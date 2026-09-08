<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CRM Trung Tâm')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
<div class="admin-wrapper">
    @include('partials.sidebar')
    <div class="admin-main">
        @include('partials.header')
        <div class="admin-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <ul class="mb-0 pl-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if(session('import_errors'))
                <div class="alert alert-warning alert-dismissible fade show">
                    <strong>Chi tiết dòng lỗi:</strong>
                    <ul class="mb-0 mt-2 pl-3">
                        @foreach(session('import_errors') as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @yield('content')
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    $('#sidebarToggle').on('click', function () {
        $('.admin-sidebar').toggleClass('show');
    });

    // Chạy nhắc lịch ngầm (thay cron trên cPanel): gọi khi có user đăng nhập admin.
    (function () {
        var url = @json(route('admin.scheduler.tick'));
        var storageKey = 'crm_scheduler_tick_at';
        var minIntervalMs = 60 * 1000; // mỗi 60 giây / trình duyệt
        var inFlight = false;

        function shouldTick() {
            try {
                var last = parseInt(localStorage.getItem(storageKey) || '0', 10);
                return !last || (Date.now() - last) >= minIntervalMs;
            } catch (e) {
                return true;
            }
        }

        function markTick() {
            try {
                localStorage.setItem(storageKey, String(Date.now()));
            } catch (e) {}
        }

        function runTick() {
            if (inFlight || !shouldTick()) {
                return;
            }
            inFlight = true;
            $.ajax({
                url: url,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                data: { _token: $('meta[name="csrf-token"]').attr('content') },
                timeout: 55000
            }).done(function () {
                markTick();
            }).fail(function () {
                try { localStorage.removeItem(storageKey); } catch (e) {}
            }).always(function () {
                inFlight = false;
            });
        }

        // Trễ vài giây sau khi load trang để không chặn UI
        setTimeout(runTick, 3000);
        setInterval(runTick, minIntervalMs);

        // Tab quay lại focus → thử tick ngay (nếu đã qua 60s)
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                runTick();
            }
        });
    })();
</script>
@stack('scripts')
</body>
</html>
