@extends('layouts.admin')

@section('title', $lead->name)

@section('content')
@php
    $tabs = [
        'info' => ['label' => 'Thông tin', 'icon' => 'bi-info-circle'],
        'history' => ['label' => 'Lịch sử tư vấn', 'icon' => 'bi-chat-left-text', 'count' => $lead->interactions_count],
    ];
@endphp

<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap" style="gap:.75rem">
    <div>
        <a href="{{ route('admin.leads.index') }}" class="text-muted small"><i class="bi bi-arrow-left"></i> Danh sách Leads</a>
        <div class="d-flex align-items-center mt-1 flex-wrap" style="gap:.5rem">
            <h4 class="mb-0 font-weight-bold">{{ $lead->name }}</h4>
            <span class="badge lead-status {{ $lead->statusBadgeClass() }}">{{ $lead->statusLabel() }}</span>
        </div>
        <div class="text-muted small mt-1">
            <span class="mr-2"><i class="bi bi-telephone"></i> {{ $lead->phone }}</span>
            @if($lead->email)<span class="mr-2"><i class="bi bi-envelope"></i> {{ $lead->email }}</span>@endif
            <span class="mr-2">· {{ $lead->branch?->name }}</span>
            @if($lead->source)<span class="mr-2">· {{ $lead->source }}</span>@endif
            <span>· Sales: {{ $lead->assignedSales?->name ?? 'Chưa gán' }}</span>
        </div>
    </div>
</div>

<ul class="nav nav-tabs class-detail-tabs mb-0">
    @foreach($tabs as $key => $meta)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}"
               href="{{ route('admin.leads.show', ['lead' => $lead, 'tab' => $key]) }}">
                <i class="bi {{ $meta['icon'] }} mr-1"></i>{{ $meta['label'] }}
                @isset($meta['count'])
                    <span class="badge badge-light border ml-1">{{ $meta['count'] }}</span>
                @endisset
            </a>
        </li>
    @endforeach
</ul>

<div class="page-card class-detail-panel border-top-0" style="border-top-left-radius:0;border-top-right-radius:0">
    <div class="card-body-custom">
        @if($tab === 'info')
            @include('admin.crm.lead_tabs.info')
        @elseif($tab === 'history')
            @include('admin.crm.lead_tabs.history')
        @endif
    </div>
</div>
@endsection
