@extends('layouts.admin')

@section('title', 'Chấm công nhân viên')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ';
    $weekdayLabels = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
    $mode = $mode ?? 'person';
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Chấm công nhân viên</h5>
            <small class="text-muted">
                @if($mode === 'day')
                    Chọn ngày → tick nhiều nhân viên → chấm một lần.
                @else
                    Chọn nhân viên → tick nhiều ngày trên lịch → gắn trạng thái hàng loạt.
                @endif
            </small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Người dùng</a>
            @canPerm('finance.staff_payroll.view')
            <a href="{{ route('admin.finance.staff-payroll') }}" class="btn btn-sm btn-outline-primary">Lương NV</a>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <ul class="nav nav-pills mb-3" style="gap:.35rem">
            <li class="nav-item">
                <a class="nav-link py-1 px-3 {{ $mode === 'person' ? 'active' : '' }}"
                   href="{{ route('admin.staff-attendances.index', ['mode' => 'person', 'month' => $month, 'user_id' => $userId ?? null]) }}">
                    Theo nhân viên
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-1 px-3 {{ $mode === 'day' ? 'active' : '' }}"
                   href="{{ route('admin.staff-attendances.index', ['mode' => 'day', 'date' => $workDateStr ?? now()->toDateString()]) }}">
                    Theo ngày · nhiều NV
                </a>
            </li>
        </ul>

        @if($mode === 'day')
            @include('admin.system._staff_attendances_day')
        @else
            @include('admin.system._staff_attendances_person')
        @endif
    </div>
</div>

@include('partials.select2')
@endsection

@push('styles')
<style>
.staff-att-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: .35rem;
}
.staff-att-cell {
    min-height: 72px;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    background: #fff;
    padding: .4rem .45rem;
    text-align: left;
    cursor: pointer;
    transition: .15s ease;
}
.staff-att-cell.is-empty { border: 0; background: transparent; pointer-events: none; }
.staff-att-cell:hover:not(.is-empty) { border-color: #93c5fd; box-shadow: 0 0 0 2px rgba(59,130,246,.15); }
.staff-att-cell.is-selected { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 2px rgba(37,99,235,.25); }
.staff-att-cell.is-weekend { background: #f8fafc; }
.staff-att-cell.is-future { opacity: .55; }
.staff-att-cell .day-num { display: block; font-weight: 700; font-size: .95rem; color: #0f172a; }
.staff-att-cell .day-status { display: block; font-size: .68rem; margin-top: .2rem; color: #64748b; }
.staff-att-cell.status-present { background: #f0fdf4; border-color: #86efac; }
.staff-att-cell.status-half { background: #eff6ff; border-color: #93c5fd; }
.staff-att-cell.status-leave { background: #fffbeb; border-color: #fcd34d; }
.staff-att-cell.status-absent { background: #f1f5f9; border-color: #cbd5e1; }
.staff-att-cell.status-present .day-status { color: #15803d; font-weight: 600; }
.staff-att-cell.status-half .day-status { color: #1d4ed8; font-weight: 600; }
.staff-att-cell.status-leave .day-status { color: #b45309; font-weight: 600; }
.staff-att-day-table td, .staff-att-day-table th { vertical-align: middle; }
@media (max-width: 767.98px) {
    .staff-att-cell { min-height: 56px; padding: .3rem; }
    .staff-att-cell .day-status { font-size: .6rem; }
}
</style>
@endpush
