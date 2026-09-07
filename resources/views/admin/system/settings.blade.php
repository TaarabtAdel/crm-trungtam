@extends('layouts.admin')

@section('title', 'Cài đặt')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Cài đặt hệ thống',
            'body' => '<p class="mb-0">Đổi tên hiển thị của trung tâm và chữ logo trên sidebar. Không ảnh hưởng dữ liệu nghiệp vụ (học viên, hóa đơn…).</p>',
        ],
        [
            'title' => 'Tên trung tâm',
            'body' => '<p class="mb-0">Tên đầy đủ dùng trong giao diện / báo cáo nội bộ. Điền rõ để nhân viên nhận diện đúng cơ sở đang làm việc.</p>',
        ],
        [
            'title' => 'Logo text (sidebar)',
            'body' => '<p class="mb-0">Chuỗi ngắn hiện ở góc menu trái (ví dụ viết tắt thương hiệu). Nên ngắn gọn để sidebar không bị tràn.</p>',
        ],
        [
            'title' => 'Lưu thay đổi',
            'body' => '<p class="mb-0">Bấm <strong>Lưu thay đổi</strong> sau khi sửa. Tải lại trang nếu chưa thấy logo text cập nhật trên sidebar.</p>',
        ],
    ];
@endphp
<div class="page-card" style="max-width:640px">
    <div class="card-header-custom">
        <h5 class="mb-0 font-weight-bold">Cài đặt hệ thống</h5>
        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalSettingsHelp">
            <i class="bi bi-question-circle"></i> Hướng dẫn
        </button>
    </div>
    <div class="card-body-custom">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Tên trung tâm</label>
                <input name="center_name" class="form-control" value="{{ old('center_name', $settings['center_name']) }}" required>
            </div>
            <div class="form-group">
                <label>Logo text (sidebar)</label>
                <input name="logo_text" class="form-control" value="{{ old('logo_text', $settings['logo_text']) }}" required>
            </div>
            <button class="btn btn-primary">Lưu thay đổi</button>
        </form>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalSettingsHelp',
    'title' => 'Hướng dẫn — Cài đặt',
    'items' => $helpItems,
])
@endsection
