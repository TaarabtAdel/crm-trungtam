@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp

<div class="row mb-3">
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Trạng thái</div>
            @if($user->is_active)
                <span class="badge badge-success">Active</span>
            @else
                <span class="badge badge-secondary">Khóa</span>
            @endif
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Chi nhánh</div>
            <div class="stat-value" style="font-size:1.05rem">{{ $user->branch?->name ?: '—' }}</div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Vai trò</div>
            <div>
                @foreach($user->roleKeys() as $rk)
                    <span class="badge badge-role badge-role-{{ $rk }} mr-1">{{ config('permissions.roles.'.$rk, $rk) }}</span>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-2">
        <div class="stat-card py-3">
            <div class="stat-label">Lương ngày</div>
            <div class="stat-value text-primary" style="font-size:1.15rem">{{ $fmt($user->daily_rate) }}</div>
        </div>
    </div>
</div>

@canPerm('system.users.manage')
<form method="POST" action="{{ route('admin.users.update', $user) }}">
    @csrf @method('PUT')
    <input type="hidden" name="from_detail" value="1">
    @include('admin.system._user_form', ['user' => $user, 'branches' => $branches])
    <div class="text-right mt-2">
        <button class="btn btn-primary">Lưu thông tin</button>
    </div>
</form>
@else
<div class="border rounded p-3">
    <div class="row">
        <div class="col-md-6 mb-2"><strong>Họ tên:</strong> {{ $user->name }}</div>
        <div class="col-md-6 mb-2"><strong>Email:</strong> {{ $user->email }}</div>
        <div class="col-md-6 mb-2"><strong>SĐT:</strong> {{ $user->phone ?: '—' }}</div>
        <div class="col-md-6 mb-2"><strong>Lương ngày:</strong> {{ $fmt($user->daily_rate) }}</div>
        <div class="col-md-6 mb-2"><strong>Chi nhánh:</strong> {{ $user->branch?->name ?: '—' }}</div>
    </div>
</div>
@endcanPerm
