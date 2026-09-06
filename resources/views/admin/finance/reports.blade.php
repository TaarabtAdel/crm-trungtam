@extends('layouts.admin')

@section('title', 'Báo cáo tài chính')

@section('content')
@php $fmt = fn ($n) => number_format((float) $n, 0, ',', '.').' đ'; @endphp
<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Báo cáo tài chính</h5>
            <small class="text-muted">{{ $label }}</small>
        </div>
        <div class="d-flex" style="gap:.5rem">
            @canPerm('finance.reports.export')
            <a href="{{ route('admin.finance.reports.excel', request()->query()) }}" class="btn btn-sm btn-outline-success">Excel</a>
            <a href="{{ route('admin.finance.reports.pdf', request()->query()) }}" class="btn btn-sm btn-outline-primary">PDF</a>
            @endcanPerm
        </div>
    </div>
    <div class="card-body-custom">
        <form class="filter-bar" method="GET">
            <select name="period" class="form-control form-control-sm" style="max-width:140px">
                @foreach(['day'=>'Ngày','month'=>'Tháng','quarter'=>'Quý','year'=>'Năm'] as $k=>$v)
                    <option value="{{ $k }}" @selected($period===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="max-width:160px">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" style="max-width:160px">
            <button class="btn btn-sm btn-outline-secondary">Xem</button>
        </form>

        <div class="row mb-3">
            @foreach([
                ['Thu', $data['revenue'], 'success'],
                ['Hoàn', $data['refunds'], 'warning'],
                ['Thu ròng', $data['net_revenue'], 'primary'],
                ['Chi', $data['expenses'], 'danger'],
                ['Lãi/Lỗ', $data['profit'], $data['profit']>=0?'success':'danger'],
            ] as [$labelKpi,$val,$tone])
                <div class="col-md mb-2">
                    <div class="stat-card">
                        <div class="stat-label">{{ $labelKpi }}</div>
                        <div class="stat-value text-{{ $tone }}" style="font-size:1.2rem">{{ $fmt($val) }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <strong>Theo lớp</strong>
                <ul class="list-unstyled mt-2 mb-0">
                    @forelse($data['by_class'] as $row)
                        <li class="d-flex justify-content-between py-1 border-bottom"><span>{{ $row->courseClass?->name ?: '—' }}</span><span>{{ $fmt($row->total) }}</span></li>
                    @empty
                        <li class="text-muted">Không có dữ liệu</li>
                    @endforelse
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <strong>Theo chi nhánh</strong>
                <ul class="list-unstyled mt-2 mb-0">
                    @forelse($data['by_branch'] as $row)
                        <li class="d-flex justify-content-between py-1 border-bottom"><span>{{ $row->branch?->name ?: '—' }}</span><span>{{ $fmt($row->total) }}</span></li>
                    @empty
                        <li class="text-muted">Không có dữ liệu</li>
                    @endforelse
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <strong>Theo Sales</strong>
                <ul class="list-unstyled mt-2 mb-0">
                    @forelse($data['by_sales'] as $row)
                        <li class="d-flex justify-content-between py-1 border-bottom"><span>{{ $row->sales?->name ?: '—' }}</span><span>{{ $fmt($row->total) }}</span></li>
                    @empty
                        <li class="text-muted">Không có dữ liệu</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
