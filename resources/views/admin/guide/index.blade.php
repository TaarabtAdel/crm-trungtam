@extends('layouts.admin')

@section('title', 'Hướng dẫn sử dụng')

@section('content')
@php
    $tab = $tab ?? $defaultTab ?? 'setup';
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Hướng dẫn sử dụng CRM</h5>
            <small class="text-muted">Cài đặt ban đầu · Quy trình end-to-end · Chi tiết theo từng vai trò.</small>
        </div>
    </div>
    <div class="card-body-custom guide-content">
        <div class="guide-common mb-3">
            <div class="font-weight-bold mb-2">Dùng chung mọi vai trò</div>
            <ul class="mb-0 pl-3">
                <li><strong>Chi nhánh</strong> (góc trên): chọn 1 cơ sở hoặc “Tất cả” — hầu hết danh sách/báo cáo lọc theo lựa chọn này.</li>
                <li><strong>Thông báo</strong> (chuông): lead follow-up, buổi chưa cập nhật trạng thái, <em>nhắc ghi nhật ký</em>, chi phí chờ duyệt, phân lead… — bấm để mở hoặc đánh dấu đã đọc.</li>
                <li>Menu trái chỉ hiện mục được phân quyền. Đường dẫn xanh bên dưới là <strong>liên kết bấm được</strong> (mở tab mới nếu bạn có quyền vào trang đó).</li>
                <li>Mới nhận tài khoản / data trống → bắt đầu tab <a href="{{ route('admin.guide', ['tab' => 'setup']) }}">Cài đặt ban đầu</a> (có <strong>checklist tự tick</strong>). Muốn nắm toàn bộ vòng đời → tab <a href="{{ route('admin.guide', ['tab' => 'flow']) }}">Quy trình</a>.</li>
            </ul>
        </div>

        <ul class="nav nav-pills guide-role-tabs mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'setup' ? 'active' : '' }}" href="{{ route('admin.guide', ['tab' => 'setup']) }}">Cài đặt ban đầu</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'flow' ? 'active' : '' }}" href="{{ route('admin.guide', ['tab' => 'flow']) }}">Quy trình</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'accountant' ? 'active' : '' }}" href="{{ route('admin.guide', ['tab' => 'accountant']) }}">Kế toán</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'training' ? 'active' : '' }}" href="{{ route('admin.guide', ['tab' => 'training']) }}">Đào tạo</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'sales' ? 'active' : '' }}" href="{{ route('admin.guide', ['tab' => 'sales']) }}">Sale</a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $tab === 'admin' ? 'active' : '' }}" href="{{ route('admin.guide', ['tab' => 'admin']) }}">Admin</a>
            </li>
        </ul>

        <div class="guide-panel">
        @if($tab === 'setup')
            @include('admin.guide._setup')
        @elseif($tab === 'flow')
            @include('admin.guide._flow')
        @elseif($tab === 'accountant')
            @include('admin.guide._accountant')
        @elseif($tab === 'training')
            @include('admin.guide._training')
        @elseif($tab === 'sales')
            @include('admin.guide._sales')
        @else
            @include('admin.guide._admin')
        @endif
        </div>
    </div>
</div>
<script>
(function () {
    var root = document.querySelector('.guide-content');
    if (!root) return;
    root.querySelectorAll('a[href]').forEach(function (a) {
        if (a.closest('.guide-role-tabs')) return;
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
        a.setAttribute('target', '_blank');
        a.setAttribute('rel', 'noopener noreferrer');
    });
})();
</script>
@endsection
