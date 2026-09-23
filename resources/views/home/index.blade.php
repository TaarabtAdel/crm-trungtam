@extends('layouts.workbench')

@section('title', 'Bàn làm việc — '.$brand)

@section('content')
<header class="wb-topbar wb-topbar-full">
    <a href="{{ route('home') }}" class="wb-brand" title="{{ $brand }}">
        <span class="wb-brand-mark">{{ mb_strtoupper(mb_substr($logoText, 0, 2)) }}</span>
        <span>
            <span class="wb-brand-text">{{ $brand }}</span>
            <span class="wb-brand-sub">Bàn làm việc</span>
        </span>
    </a>

    <div class="wb-top-actions">
        <label class="wb-search" aria-label="Tìm kiếm ứng dụng">
            <i class="bi bi-search"></i>
            <input type="search" id="wbSearch" placeholder="Tìm kiếm" autocomplete="off">
        </label>

        <div class="wb-notify-wrap" id="wbNotifyWrap">
            <button type="button" class="wb-icon-btn wb-notify-btn" id="wbNotifyBtn" title="Thông báo" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-bell"></i>
                @if(($headerUnreadNotifications ?? 0) > 0)
                    <span class="wb-notify-badge">{{ $headerUnreadNotifications > 99 ? '99+' : $headerUnreadNotifications }}</span>
                @endif
            </button>
            <div class="wb-notify-panel" role="menu">
                <div class="wb-notify-panel-head">
                    <strong>Thông báo</strong>
                    @if(($headerUnreadNotifications ?? 0) > 0)
                        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="wb-notify-link-btn">Đánh dấu đã đọc</button>
                        </form>
                    @endif
                </div>
                <div class="wb-notify-panel-body">
                    @forelse($headerNotifications ?? [] as $n)
                        @php
                            $data = $n->data ?? [];
                            $title = $data['title'] ?? 'Thông báo';
                            $body = $data['body'] ?? '';
                            $icon = $data['icon'] ?? 'bi-bell';
                            $unread = $n->read_at === null;
                        @endphp
                        <div class="wb-notify-item {{ $unread ? 'is-unread' : '' }}">
                            <a href="{{ route('admin.notifications.read', $n->id) }}" class="wb-notify-item-main">
                                <span class="wb-notify-item-icon"><i class="bi {{ $icon }}"></i></span>
                                <span class="wb-notify-item-content">
                                    <span class="wb-notify-item-title">{{ $title }}</span>
                                    @if($body)<span class="wb-notify-item-body">{{ $body }}</span>@endif
                                    <span class="wb-notify-item-time">{{ $n->created_at?->diffForHumans() }}</span>
                                </span>
                            </a>
                        </div>
                    @empty
                        <div class="wb-notify-empty">Chưa có thông báo.</div>
                    @endforelse
                </div>
                <div class="wb-notify-panel-foot">
                    <a href="{{ route('admin.notifications.index') }}">Xem tất cả</a>
                </div>
            </div>
        </div>

        <div class="wb-user-wrap" id="wbUserWrap">
            <button type="button" class="wb-user" id="wbUserBtn" aria-haspopup="true" aria-expanded="false">
                <span class="wb-avatar">{{ $user->initials() }}</span>
                <span class="wb-user-name">{{ $user->name }}</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="wb-menu" role="menu">
                @if($user->hasPermission('dashboard.view'))
                    <a href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Vào hệ thống</a>
                @endif
                <a href="{{ route('admin.notifications.index') }}"><i class="bi bi-bell"></i> Thông báo</a>
                <a href="{{ route('admin.my-payroll') }}"><i class="bi bi-wallet"></i> Lương của tôi</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><i class="bi bi-box-arrow-right"></i> Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="wb-layout">
    <div class="wb-main">
        <div class="wb-shell">
            <nav class="wb-filters" aria-label="Lọc ứng dụng">
                <button type="button" class="wb-chip" data-filter="recent" title="Gần đây">
                    <i class="bi bi-clock-history"></i> Gần đây
                </button>
                <button type="button" class="wb-chip" data-filter="favorites" title="Yêu thích">
                    <i class="bi bi-star"></i> Yêu thích
                </button>
                <button type="button" class="wb-chip is-active" data-filter="all">
                    Tất cả
                </button>
                @foreach($categories as $cat)
                    <button type="button"
                            class="wb-chip wb-chip-cat"
                            data-filter="{{ $cat['id'] }}"
                            style="--wb-cat: {{ $cat['color'] }}">
                        {{ $cat['label'] }}
                    </button>
                @endforeach
            </nav>

            <div class="wb-grid" id="wbGrid">
                @forelse($apps as $app)
                    <a href="{{ $app['url'] }}"
                       class="wb-app"
                       data-id="{{ $app['id'] }}"
                       data-name="{{ mb_strtolower($app['name']) }}"
                       data-category="{{ $app['category'] }}"
                       title="{{ $app['name'] }}">
                        <span class="wb-app-icon" style="background: {{ $app['color'] }}">
                            <i class="bi {{ $app['icon'] }}"></i>
                            <span class="wb-app-badge" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                        </span>
                        <span class="wb-app-name">{{ $app['name'] }}</span>
                    </a>
                @empty
                    <div class="wb-empty">
                        Chưa có ứng dụng nào khả dụng với quyền của bạn.
                    </div>
                @endforelse
                <div class="wb-empty is-hidden" id="wbEmptyFilter" hidden>
                    Không tìm thấy ứng dụng phù hợp.
                </div>
            </div>
        </div>
    </div>

    <aside class="wb-datetime" aria-label="Đồng hồ và lịch">
        <div class="wb-clock-card">
            <div class="wb-clock-time" id="wbClockTime">--:--</div>
            <div class="wb-clock-date" id="wbClockDate">—</div>
        </div>
        <div class="wb-cal-card">
            <div class="wb-cal-head">
                <button type="button" class="wb-cal-nav" id="wbCalPrev" aria-label="Tháng trước">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <div class="wb-cal-title" id="wbCalTitle">—</div>
                <button type="button" class="wb-cal-nav" id="wbCalNext" aria-label="Tháng sau">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
            <div class="wb-cal-weekdays" aria-hidden="true">
                <span>T2</span><span>T3</span><span>T4</span><span>T5</span><span>T6</span><span>T7</span><span>CN</span>
            </div>
            <div class="wb-cal-grid" id="wbCalGrid"></div>
        </div>

        @if($user->hasPermission('tasks.view') || $user->isSuperAdmin())
            <div class="wb-tasks-card">
                <div class="wb-tasks-head">
                    <div class="wb-tasks-title">
                        <i class="bi bi-check2-square"></i>
                        Việc hôm nay
                    </div>
                    <a href="{{ route('admin.tasks.index') }}" class="wb-tasks-all" title="Xem tất cả">Tất cả</a>
                </div>
                <div class="wb-tasks-list">
                    @forelse($tasksToday ?? [] as $task)
                        <a href="{{ route('admin.tasks.index', ['open' => $task->id]) }}"
                           class="wb-tasks-item {{ $task->isOverdue() ? 'is-overdue' : '' }}"
                           title="{{ $task->title }}">
                            <span class="wb-tasks-priority task-priority-{{ $task->priority }}"></span>
                            <span class="wb-tasks-item-main">
                                <span class="wb-tasks-item-title">{{ $task->title }}</span>
                                <span class="wb-tasks-item-meta">
                                    {{ $task->due_date?->format('H:i') ?? '—' }}
                                    @if($task->assignee)
                                        · {{ $task->assignee->name }}
                                    @endif
                                </span>
                            </span>
                        </a>
                    @empty
                        <div class="wb-tasks-empty">Không có việc đến hạn hôm nay.</div>
                    @endforelse
                </div>
            </div>
        @endif
    </aside>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var storageKeyFav = 'crm_wb_favorites';
    var storageKeyRecent = 'crm_wb_recent';
    var filter = 'all';
    var search = '';

    function readJson(key) {
        try { return JSON.parse(localStorage.getItem(key) || '[]'); }
        catch (e) { return []; }
    }

    function writeJson(key, value) {
        localStorage.setItem(key, JSON.stringify(value));
    }

    function favorites() { return readJson(storageKeyFav); }
    function recent() { return readJson(storageKeyRecent); }

    function setFavoriteState() {
        var fav = favorites();
        document.querySelectorAll('.wb-app').forEach(function (el) {
            el.classList.toggle('is-favorite', fav.indexOf(el.dataset.id) !== -1);
        });
    }

    function applyFilter() {
        var fav = favorites();
        var rec = recent();
        var q = search.trim().toLowerCase();
        var visible = 0;

        document.querySelectorAll('.wb-app').forEach(function (el) {
            var id = el.dataset.id;
            var cat = el.dataset.category;
            var name = el.dataset.name || '';
            var ok = true;

            if (filter === 'favorites') ok = fav.indexOf(id) !== -1;
            else if (filter === 'recent') ok = rec.indexOf(id) !== -1;
            else if (filter === 'all') ok = true;
            else ok = cat === filter;

            if (ok && q) ok = name.indexOf(q) !== -1;

            el.classList.toggle('is-hidden', !ok);
            if (ok) visible++;
        });

        var empty = document.getElementById('wbEmptyFilter');
        if (empty) {
            empty.hidden = visible > 0 || document.querySelectorAll('.wb-app').length === 0;
            empty.classList.toggle('is-hidden', empty.hidden);
        }
    }

    document.querySelectorAll('.wb-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.querySelectorAll('.wb-chip').forEach(function (c) { c.classList.remove('is-active'); });
            chip.classList.add('is-active');
            filter = chip.dataset.filter;
            applyFilter();
        });
    });

    var searchInput = document.getElementById('wbSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            search = searchInput.value || '';
            applyFilter();
        });
    }

    document.querySelectorAll('.wb-app').forEach(function (el) {
        el.addEventListener('click', function () {
            var id = el.dataset.id;
            var rec = recent().filter(function (x) { return x !== id; });
            rec.unshift(id);
            writeJson(storageKeyRecent, rec.slice(0, 12));
        });

        el.addEventListener('contextmenu', function (e) {
            e.preventDefault();
            var id = el.dataset.id;
            var fav = favorites();
            var idx = fav.indexOf(id);
            if (idx === -1) fav.push(id);
            else fav.splice(idx, 1);
            writeJson(storageKeyFav, fav);
            setFavoriteState();
            if (filter === 'favorites') applyFilter();
        });
    });

    function bindMenu(wrapId, btnId) {
        var wrap = document.getElementById(wrapId);
        var btn = document.getElementById(btnId);
        if (!wrap || !btn) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = wrap.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.querySelectorAll('.wb-user-wrap.is-open, .wb-notify-wrap.is-open').forEach(function (other) {
                if (other !== wrap) {
                    other.classList.remove('is-open');
                    var ob = other.querySelector('[aria-expanded]');
                    if (ob) ob.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    bindMenu('wbUserWrap', 'wbUserBtn');
    bindMenu('wbNotifyWrap', 'wbNotifyBtn');

    document.addEventListener('click', function () {
        document.querySelectorAll('.wb-user-wrap.is-open, .wb-notify-wrap.is-open').forEach(function (wrap) {
            wrap.classList.remove('is-open');
            var btn = wrap.querySelector('[aria-expanded]');
            if (btn) btn.setAttribute('aria-expanded', 'false');
        });
    });

    setFavoriteState();
    applyFilter();

    // —— Đồng hồ + lịch ——
    var clockTimeEl = document.getElementById('wbClockTime');
    var clockDateEl = document.getElementById('wbClockDate');
    var calTitleEl = document.getElementById('wbCalTitle');
    var calGridEl = document.getElementById('wbCalGrid');
    var calView = new Date();
    calView.setDate(1);

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function tickClock() {
        var now = new Date();
        if (clockTimeEl) {
            clockTimeEl.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes());
        }
        if (clockDateEl) {
            clockDateEl.textContent = now.toLocaleDateString('vi-VN', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }
    }

    var taskDueDates = @json($taskDueDates ?? []);
    var taskDueSet = {};
    taskDueDates.forEach(function (d) { taskDueSet[d] = true; });

    function renderCalendar() {
        if (!calGridEl || !calTitleEl) return;
        var year = calView.getFullYear();
        var month = calView.getMonth();
        var today = new Date();
        calTitleEl.textContent = 'Tháng ' + (month + 1) + ', ' + year;

        var firstDow = new Date(year, month, 1).getDay();
        var startOffset = (firstDow + 6) % 7;
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var daysPrev = new Date(year, month, 0).getDate();

        var html = '';
        for (var i = 0; i < 42; i++) {
            var dayNum, inMonth = true, d;
            if (i < startOffset) {
                dayNum = daysPrev - startOffset + i + 1;
                inMonth = false;
                d = new Date(year, month - 1, dayNum);
            } else if (i - startOffset + 1 > daysInMonth) {
                dayNum = i - startOffset + 1 - daysInMonth;
                inMonth = false;
                d = new Date(year, month + 1, dayNum);
            } else {
                dayNum = i - startOffset + 1;
                d = new Date(year, month, dayNum);
            }
            var ymd = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
            var isToday = d.getFullYear() === today.getFullYear()
                && d.getMonth() === today.getMonth()
                && d.getDate() === today.getDate();
            var hasTask = !!taskDueSet[ymd];
            html += '<span class="wb-cal-day'
                + (inMonth ? '' : ' is-out')
                + (isToday ? ' is-today' : '')
                + (hasTask ? ' has-task' : '')
                + '"' + (hasTask ? ' title="Có công việc đến hạn"' : '') + '>' + dayNum + '</span>';
        }
        calGridEl.innerHTML = html;
    }

    tickClock();
    setInterval(tickClock, 1000);
    renderCalendar();

    var prevBtn = document.getElementById('wbCalPrev');
    var nextBtn = document.getElementById('wbCalNext');
    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            calView.setMonth(calView.getMonth() - 1);
            renderCalendar();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            calView.setMonth(calView.getMonth() + 1);
            renderCalendar();
        });
    }
})();
</script>
@endpush
