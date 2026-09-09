<header class="admin-topbar">
    <button class="btn btn-link d-lg-none p-0" id="sidebarToggle" type="button">
        <i class="bi bi-list" style="font-size:1.5rem"></i>
    </button>
    <form action="{{ route('admin.students.index') }}" method="GET" class="search-box">
        <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            </div>
            <input type="text" name="q" class="form-control border-left-0" placeholder="Tìm kiếm học viên..." value="{{ request('q') }}">
        </div>
    </form>
    <div class="ml-auto d-flex align-items-center">
        <form method="POST" action="{{ route('admin.current-branch.update') }}" class="branch-switcher mr-3 mb-0">
            @csrf
            <div class="input-group input-group-sm">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white"><i class="bi bi-building"></i></span>
                </div>
                <select name="branch_id" class="form-control" onchange="this.form.submit()" title="Chọn chi nhánh">
                    <option value="all" @selected(!$currentBranchId)>Tất cả</option>
                    @foreach($headerBranches as $branch)
                        <option value="{{ $branch->id }}" @selected($currentBranchId == $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="dropdown notification-dropdown mr-3">
            <a href="#" class="notification-bell text-dark" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Thông báo">
                <i class="bi bi-bell" style="font-size:1.35rem"></i>
                @if(($headerUnreadNotifications ?? 0) > 0)
                    <span class="badge badge-danger notification-bell-badge">{{ $headerUnreadNotifications > 99 ? '99+' : $headerUnreadNotifications }}</span>
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-right notification-panel shadow">
                <div class="notification-panel-header d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                    <strong>Thông báo</strong>
                    @if(($headerUnreadNotifications ?? 0) > 0)
                    <form method="POST" action="{{ route('admin.notifications.read-all') }}" class="mb-0">
                        @csrf
                        <button type="submit" class="btn btn-link btn-sm p-0">Đánh dấu đã đọc</button>
                    </form>
                    @endif
                </div>
                <div class="notification-panel-body">
                    @forelse($headerNotifications ?? [] as $n)
                        @php
                            $data = $n->data ?? [];
                            $title = $data['title'] ?? 'Thông báo';
                            $body = $data['body'] ?? '';
                            $icon = $data['icon'] ?? 'bi-bell';
                            $unread = $n->read_at === null;
                        @endphp
                        <div class="notification-item {{ $unread ? 'is-unread' : '' }}">
                            <a href="{{ route('admin.notifications.read', $n->id) }}" class="notification-item-main">
                                <div class="notification-item-icon"><i class="bi {{ $icon }}"></i></div>
                                <div class="notification-item-content">
                                    <div class="notification-item-title">{{ $title }}</div>
                                    @if($body)<div class="notification-item-body">{{ $body }}</div>@endif
                                    <div class="notification-item-time">{{ $n->created_at?->diffForHumans() }}</div>
                                </div>
                            </a>
                            <div class="notification-item-actions">
                                @if($unread)
                                    <form method="POST" action="{{ route('admin.notifications.mark-read', $n->id) }}" class="mb-0">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0 notification-mark-read" title="Đánh dấu đã đọc">Đã đọc</button>
                                    </form>
                                    <span class="notification-dot"></span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4 small">Chưa có thông báo.</div>
                    @endforelse
                </div>
                <div class="notification-panel-footer border-top text-center">
                    <a href="{{ route('admin.notifications.index') }}" class="d-block py-2 small font-weight-bold">Xem tất cả</a>
                </div>
            </div>
        </div>

        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-toggle="dropdown">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2" style="width:36px;height:36px">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="d-none d-md-block text-left">
                    <div class="font-weight-bold" style="line-height:1.1">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->roleLabel() }}</small>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="{{ route('admin.my-payroll') }}">
                    <i class="bi bi-wallet mr-1"></i> Bảng lương của tôi
                </a>
                <a class="dropdown-item" href="{{ route('admin.guide') }}">Hướng dẫn sử dụng</a>
                <a class="dropdown-item" href="{{ route('admin.notifications.index') }}">Thông báo</a>
                @if(auth()->user()->hasPermission('system.settings.manage'))
                <a class="dropdown-item" href="{{ route('admin.settings.edit') }}">Cài đặt</a>
                @endif
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item text-danger" type="submit">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
</header>
