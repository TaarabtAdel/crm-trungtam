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
                <a class="dropdown-item" href="{{ route('admin.settings.edit') }}">Cài đặt</a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item text-danger" type="submit">Đăng xuất</button>
                </form>
            </div>
        </div>
    </div>
</header>
