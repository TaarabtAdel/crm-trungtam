@extends('layouts.admin')

@section('title', 'Bảng lương của tôi')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $dual = $dual ?? false;
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Bảng lương của tôi</h5>
            <small class="text-muted">
                @if($dual)
                    Tài khoản vừa Giáo viên vừa nhân viên — chọn loại lương bên dưới.
                @elseif($mode === 'teacher')
                    Lương giáo viên (theo buổi hoàn thành)
                @else
                    Lương nhân viên (theo ngày công)
                @endif
            </small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            @if($detail)
            <a href="{{ route('admin.my-payroll.pdf', ['month' => $month, 'year' => $year, 'type' => $mode]) }}"
               class="btn btn-sm btn-outline-danger" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i> Xuất PDF
            </a>
            @endif
        </div>
    </div>
    <div class="card-body-custom">
        @if($dual)
            <ul class="nav nav-pills mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $mode === 'teacher' ? 'active' : '' }}"
                       href="{{ route('admin.my-payroll', ['month' => $month, 'year' => $year, 'type' => 'teacher']) }}">
                        <i class="bi bi-person-badge mr-1"></i> Lương giáo viên
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $mode === 'staff' ? 'active' : '' }}"
                       href="{{ route('admin.my-payroll', ['month' => $month, 'year' => $year, 'type' => 'staff']) }}">
                        <i class="bi bi-cash-stack mr-1"></i> Lương nhân viên
                    </a>
                </li>
            </ul>
        @endif

        <form method="GET" class="filter-bar mb-3">
            @if($dual)
                <input type="hidden" name="type" value="{{ $mode }}">
            @endif
            <select name="month" class="form-control form-control-sm" style="max-width:130px">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month == $m)>Tháng {{ $m }}</option>
                @endfor
            </select>
            <select name="year" class="form-control form-control-sm" style="max-width:110px">
                @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                @endfor
            </select>
            <button class="btn btn-sm btn-outline-secondary">Xem</button>
        </form>

        @if($mode === 'teacher' && ! $teacher)
            <div class="alert alert-warning mb-0">
                Tài khoản Giáo viên chưa gắn hồ sơ giáo viên.
                Email đăng nhập cần trùng email trên hồ sơ <em>Giáo viên</em> (menu Đào tạo → Giáo viên).
            </div>
        @elseif($mode === 'teacher' && $detail)
            <div class="row mb-3">
                @foreach([
                    ['Gốc (buổi HT)', $detail['accrued'], 'secondary'],
                    ['Thưởng', $detail['bonus'] ?? 0, 'success'],
                    ['Phạt', $detail['penalty'] ?? 0, 'warning'],
                    ['Ứng', $detail['advance'] ?? 0, 'info'],
                    ['Phải trả (net)', $detail['net'] ?? $detail['accrued'], 'primary'],
                    ['Buổi / giờ', ($detail['sessions']->count()).' / '.$detail['hours'].'h', 'secondary'],
                ] as [$label, $val, $tone])
                    <div class="col-6 col-md-4 col-lg-2 mb-2">
                        <div class="stat-card py-3">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value text-{{ $tone }}" style="font-size:1.05rem">
                                {{ is_numeric($val) ? $fmt($val) : $val }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <h6 class="font-weight-bold">Chi tiết buổi dạy tháng {{ $month }}/{{ $year }}</h6>
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Lớp</th>
                        <th>Giờ</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($detail['sessions'] as $session)
                        <tr>
                            <td>{{ $session->session_date->format('d/m/Y') }}</td>
                            <td>{{ $session->courseClass?->name ?: '—' }}</td>
                            <td>
                                {{ $session->start_time ? substr((string)$session->start_time,0,5) : '' }}
                                –
                                {{ $session->end_time ? substr((string)$session->end_time,0,5) : '' }}
                                <span class="text-muted">({{ number_format($session->hours(), 2) }}h)</span>
                            </td>
                            <td>{{ $fmt($session->teacherPayRate()) }}</td>
                            <td class="font-weight-bold">{{ $fmt($session->teacherPayAmount()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Chưa có buổi hoàn thành trong tháng.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(($detail['adjustments'] ?? collect())->isNotEmpty())
                <h6 class="font-weight-bold mt-3">Điều chỉnh</h6>
                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Loại</th><th>Số tiền</th><th>Ghi chú</th></tr></thead>
                        <tbody>
                        @foreach($detail['adjustments'] as $adj)
                            <tr>
                                <td>{{ $adj->typeLabel() }}</td>
                                <td>{{ $fmt($adj->amount) }}</td>
                                <td>{{ $adj->note }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @elseif($mode === 'staff' && $detail)
            <div class="row mb-3">
                @foreach([
                    ['Gốc (công)', $detail['accrued'], 'secondary'],
                    ['Thưởng', $detail['bonus'] ?? 0, 'success'],
                    ['Phạt', $detail['penalty'] ?? 0, 'warning'],
                    ['Ứng', $detail['advance'] ?? 0, 'info'],
                    ['Phải trả (net)', $detail['net'] ?? $detail['accrued'], 'primary'],
                    ['Ngày công', $detail['days'].' ngày', 'secondary'],
                ] as [$label, $val, $tone])
                    <div class="col-6 col-md-4 col-lg-2 mb-2">
                        <div class="stat-card py-3">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value text-{{ $tone }}" style="font-size:1.05rem">
                                {{ is_numeric($val) ? $fmt($val) : $val }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <h6 class="font-weight-bold">Chi tiết ngày công tháng {{ $month }}/{{ $year }}</h6>
            <div class="table-responsive border rounded">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Trạng thái</th>
                        <th>Công</th>
                        <th>Thành tiền</th>
                        <th>Ghi chú</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($detail['attendances'] as $att)
                        <tr>
                            <td>{{ $att->work_date->format('d/m/Y') }}</td>
                            <td>{{ $att->statusLabel() }}</td>
                            <td>{{ number_format($att->dayUnits(), 1) }}</td>
                            <td>{{ $fmt($att->payAmount($detail['rate'])) }}</td>
                            <td>{{ $att->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Chưa có chấm công trong tháng.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(($detail['adjustments'] ?? collect())->isNotEmpty())
                <h6 class="font-weight-bold mt-3">Điều chỉnh</h6>
                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Loại</th><th>Số tiền</th><th>Ghi chú</th></tr></thead>
                        <tbody>
                        @foreach($detail['adjustments'] as $adj)
                            <tr>
                                <td>{{ $adj->typeLabel() }}</td>
                                <td>{{ $fmt($adj->amount) }}</td>
                                <td>{{ $adj->note }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
