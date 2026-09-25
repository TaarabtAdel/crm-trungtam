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
        @if(session()->has('impersonator_id'))
            <div class="alert alert-warning mb-0 rounded-0 d-flex align-items-center justify-content-between px-3 py-2" style="border-left:0;border-right:0">
                <span class="small mb-0">
                    <i class="bi bi-person-bounding-box mr-1"></i>
                    Đang xem với tư cách <strong>{{ auth()->user()->name }}</strong>.
                </span>
                <form method="POST" action="{{ route('admin.impersonation.leave') }}" class="mb-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-dark">
                        <i class="bi bi-arrow-counterclockwise mr-1"></i> Trở lại tài khoản Admin
                    </button>
                </form>
            </div>
        @endif
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

    // Dropdown trong .table-responsive: gắn menu ra body (fixed) để không bị scroll/clip theo bảng.
    (function () {
        function placeMenu($toggle, $menu) {
            var rect = $toggle[0].getBoundingClientRect();
            var menuWidth = $menu.outerWidth();
            var left = rect.right - menuWidth;
            if (left < 8) {
                left = 8;
            }
            if (left + menuWidth > window.innerWidth - 8) {
                left = Math.max(8, window.innerWidth - menuWidth - 8);
            }
            var top = rect.bottom + 2;
            var menuHeight = $menu.outerHeight();
            if (top + menuHeight > window.innerHeight - 8 && rect.top - menuHeight - 2 > 8) {
                top = rect.top - menuHeight - 2;
            }
            $menu.css({
                position: 'fixed',
                top: top,
                left: left,
                right: 'auto',
                bottom: 'auto',
                transform: 'none',
                zIndex: 1060
            });
        }

        function findDetachedMenu(ddEl) {
            return $('body > .dropdown-menu[data-table-dropdown="1"]').filter(function () {
                return $(this).data('dropdownParent') === ddEl;
            });
        }

        $(document).on('shown.bs.dropdown', '.table-responsive .dropdown', function () {
            var $dd = $(this);
            var $toggle = $dd.children('[data-toggle="dropdown"]').add($dd.children('.dropdown-toggle')).first();
            if (!$toggle.length) {
                $toggle = $dd.find('> [data-toggle="dropdown"], > .dropdown-toggle').first();
            }
            var $menu = $dd.children('.dropdown-menu');
            if (!$menu.length) {
                $menu = findDetachedMenu($dd[0]);
            }
            if (!$toggle.length || !$menu.length) {
                return;
            }

            $menu.attr('data-table-dropdown', '1').data('dropdownParent', $dd[0]);
            $('body').append($menu);
            $menu.addClass('show');
            placeMenu($toggle, $menu);

            var $scrollParents = $dd.closest('.table-responsive')
                .add('.admin-content, .admin-main, .admin-wrapper');

            var closeOnScroll = function (e) {
                if ($menu[0] && $menu[0].contains(e.target)) {
                    return;
                }
                $toggle.dropdown('hide');
            };
            var reposition = function () {
                if ($menu.hasClass('show')) {
                    placeMenu($toggle, $menu);
                }
            };

            $scrollParents.on('scroll.tableDropdownFix', closeOnScroll);
            $(window).on('scroll.tableDropdownFix', reposition);
            $(window).on('resize.tableDropdownFix', reposition);

            $dd.data('tableDropdownCleanup', function () {
                $scrollParents.off('.tableDropdownFix');
                $(window).off('.tableDropdownFix');
            });
        });

        $(document).on('hide.bs.dropdown', '.table-responsive .dropdown', function () {
            var $dd = $(this);
            var cleanup = $dd.data('tableDropdownCleanup');
            if (typeof cleanup === 'function') {
                cleanup();
                $dd.removeData('tableDropdownCleanup');
            }
            var $menu = findDetachedMenu($dd[0]);
            if (!$menu.length) {
                return;
            }
            $menu.removeClass('show')
                .removeAttr('data-table-dropdown')
                .removeData('dropdownParent')
                .css({ position: '', top: '', left: '', right: '', bottom: '', transform: '', zIndex: '' });
            $dd.append($menu);
        });
    })();
</script>
@include('partials.scheduler_tick')
@stack('scripts')
</body>
</html>
