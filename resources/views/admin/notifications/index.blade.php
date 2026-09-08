@extends('layouts.admin')

@section('title', 'Thông báo')

@section('content')
<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Thông báo</h5>
            <small class="text-muted">Các cập nhật gần đây trên hệ thống.</small>
        </div>
        @if(auth()->user()->unreadNotifications->isNotEmpty())
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            <button class="btn btn-sm btn-outline-primary">Đánh dấu tất cả đã đọc</button>
        </form>
        @endif
    </div>
    <div class="card-body-custom p-0">
        <div class="list-group list-group-flush">
            @forelse($notifications as $n)
                @php
                    $data = $n->data ?? [];
                    $title = $data['title'] ?? 'Thông báo';
                    $body = $data['body'] ?? '';
                    $icon = $data['icon'] ?? 'bi-bell';
                    $unread = $n->read_at === null;
                @endphp
                <div class="list-group-item {{ $unread ? 'bg-light' : '' }}">
                    <div class="d-flex align-items-start" style="gap:.75rem">
                        <a href="{{ route('admin.notifications.read', $n->id) }}" class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center text-decoration-none" style="width:40px;height:40px;flex-shrink:0">
                            <i class="bi {{ $icon }}"></i>
                        </a>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start" style="gap:.75rem">
                                <a href="{{ route('admin.notifications.read', $n->id) }}" class="text-dark text-decoration-none flex-grow-1">
                                    <strong>{{ $title }}</strong>
                                    @if($body)<div class="text-muted small mt-1">{{ $body }}</div>@endif
                                    <div class="text-muted small mt-1">{{ $n->created_at?->diffForHumans() }}</div>
                                </a>
                                <div class="text-right" style="flex-shrink:0">
                                    @if($unread)
                                        <span class="badge badge-primary mb-1 d-block">Mới</span>
                                        <form method="POST" action="{{ route('admin.notifications.mark-read', $n->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm p-0">Đánh dấu đã đọc</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">Chưa có thông báo.</div>
            @endforelse
        </div>
        <div class="p-3">{{ $notifications->links() }}</div>
    </div>
</div>
@endsection
