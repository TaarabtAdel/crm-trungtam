<aside class="admin-sidebar">
    <div class="brand">{{ \App\Models\Setting::get('logo_text', 'TPT2') }}</div>

    @php $u = auth()->user(); @endphp

    @if($u->hasPermission('dashboard.view'))
    <div class="nav-section">Tổng quan</div>
    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <i class="bi bi-speedometer2 mr-2"></i> Bảng điều khiển
    </a>
    @endif

    @if($u->hasAnyPermission('crm.sales.view','crm.leads.view','crm.interactions.view'))
    <div class="nav-section">Tuyển sinh (CRM)</div>
    @if($u->hasPermission('crm.sales.view'))
    <a href="{{ route('admin.crm.sales') }}" class="nav-link {{ request()->routeIs('admin.crm.sales') ? 'active' : '' }}">
        <i class="bi bi-graph-up mr-2"></i> Dashboard Sales
    </a>
    @endif
    @if($u->hasPermission('crm.leads.view'))
    <a href="{{ route('admin.leads.index') }}" class="nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
        <i class="bi bi-people mr-2"></i> Danh sách Leads
    </a>
    @endif
    @if($u->hasPermission('crm.interactions.view'))
    <a href="{{ route('admin.interactions.index') }}" class="nav-link {{ request()->routeIs('admin.interactions.*') ? 'active' : '' }}">
        <i class="bi bi-calendar-check mr-2"></i> Lịch hẹn / Tương tác
    </a>
    @endif
    @endif

    @if($u->hasAnyPermission('training.classes.view','training.subjects.view','training.teachers.view'))
    <div class="nav-section">Quản trị đào tạo</div>
    @if($u->hasPermission('training.classes.view'))
    <a href="{{ route('admin.classes.index') }}" class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}">
        <i class="bi bi-journal-bookmark mr-2"></i> Lớp học
    </a>
    @endif
    @if($u->hasPermission('training.subjects.view'))
    <a href="{{ route('admin.subjects.index') }}" class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}">
        <i class="bi bi-book mr-2"></i> Môn học
    </a>
    @endif
    @if($u->hasPermission('training.teachers.view'))
    <a href="{{ route('admin.teachers.index') }}" class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
        <i class="bi bi-person-badge mr-2"></i> Giáo viên
    </a>
    @endif
    @endif

    @if($u->hasAnyPermission('students.view','attendances.view'))
    <div class="nav-section">Quản trị học viên</div>
    @if($u->hasPermission('students.view'))
    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
        <i class="bi bi-mortarboard mr-2"></i> Học sinh
    </a>
    @endif
    @if($u->hasPermission('attendances.view'))
    <a href="{{ route('admin.attendances.index') }}" class="nav-link {{ request()->routeIs('admin.attendances.*') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check mr-2"></i> Điểm danh
    </a>
    @endif
    @endif

    @if($u->hasAnyPermission('finance.dashboard.view','finance.invoices.view','finance.expenses.view','finance.debts.view','finance.commissions.view','finance.refunds.manage','finance.reports.view'))
    <div class="nav-section">Quản trị tài chính</div>
    @if($u->hasPermission('finance.dashboard.view'))
    <a href="{{ route('admin.finance.dashboard') }}" class="nav-link {{ request()->routeIs('admin.finance.dashboard') ? 'active' : '' }}">
        <i class="bi bi-pie-chart mr-2"></i> Dashboard TC
    </a>
    @endif
    @if($u->hasPermission('finance.invoices.view'))
    <a href="{{ route('admin.invoices.index') }}" class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
        <i class="bi bi-receipt mr-2"></i> Hóa đơn
    </a>
    @endif
    @if($u->hasPermission('finance.debts.view'))
    <a href="{{ route('admin.debts.index') }}" class="nav-link {{ request()->routeIs('admin.debts.*') ? 'active' : '' }}">
        <i class="bi bi-exclamation-triangle mr-2"></i> Công nợ
    </a>
    @endif
    @if($u->hasPermission('finance.expenses.view'))
    <a href="{{ route('admin.expenses.index') }}" class="nav-link {{ request()->routeIs('admin.expenses.*') ? 'active' : '' }}">
        <i class="bi bi-wallet2 mr-2"></i> Chi phí
    </a>
    @endif
    @if($u->hasPermission('finance.refunds.manage'))
    <a href="{{ route('admin.refunds.index') }}" class="nav-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
        <i class="bi bi-arrow-counterclockwise mr-2"></i> Hoàn tiền
    </a>
    @endif
    @if($u->hasPermission('finance.commissions.view'))
    <a href="{{ route('admin.commissions.index') }}" class="nav-link {{ request()->routeIs('admin.commissions.*') ? 'active' : '' }}">
        <i class="bi bi-percent mr-2"></i> Hoa hồng
    </a>
    @endif
    @if($u->hasPermission('finance.commissions.manage'))
    <a href="{{ route('admin.commission-rules.index') }}" class="nav-link {{ request()->routeIs('admin.commission-rules.*') ? 'active' : '' }}">
        <i class="bi bi-sliders mr-2"></i> Quy tắc HH
    </a>
    @endif
    @if($u->hasPermission('finance.reports.view'))
    <a href="{{ route('admin.finance.reports') }}" class="nav-link {{ request()->routeIs('admin.finance.reports*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line mr-2"></i> Báo cáo TC
    </a>
    @endif
    @endif

    @if($u->hasAnyPermission('system.branches.view','system.users.view','system.permissions.manage','system.reports.view','system.demo_data.manage','system.settings.manage'))
    <div class="nav-section">Hệ thống</div>
    @if($u->hasPermission('system.branches.view'))
    <a href="{{ route('admin.branches.index') }}" class="nav-link {{ request()->routeIs('admin.branches.*') ? 'active' : '' }}">
        <i class="bi bi-building mr-2"></i> Quản lý chi nhánh
    </a>
    @endif
    @if($u->hasPermission('system.users.view'))
    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i class="bi bi-person-gear mr-2"></i> Quản lý người dùng
    </a>
    @endif
    @if($u->hasPermission('system.permissions.manage'))
    <a href="{{ route('admin.permissions.edit') }}" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
        <i class="bi bi-shield-lock mr-2"></i> Phân quyền
    </a>
    @endif
    @if($u->hasPermission('system.reports.view'))
    <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart mr-2"></i> Báo cáo
    </a>
    @endif
    @if($u->hasPermission('system.demo_data.manage'))
    <a href="{{ route('admin.demo-data.index') }}" class="nav-link {{ request()->routeIs('admin.demo-data.*') ? 'active' : '' }}">
        <i class="bi bi-database-add mr-2"></i> Khởi tạo data demo
    </a>
    @endif
    @if($u->hasPermission('system.settings.manage'))
    <a href="{{ route('admin.settings.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
        <i class="bi bi-gear mr-2"></i> Cài đặt
    </a>
    @endif
    @endif

    <form method="POST" action="{{ route('logout') }}" class="logout-btn">
        @csrf
        <button type="submit" class="btn btn-outline-danger btn-block btn-sm">
            <i class="bi bi-box-arrow-right"></i> Đăng xuất
        </button>
    </form>
</aside>
