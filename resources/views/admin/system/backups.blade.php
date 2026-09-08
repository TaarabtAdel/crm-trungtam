@extends('layouts.admin')

@section('title', 'Backup dữ liệu')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Backup SQL làm gì?',
            'body' => '<p class="mb-0">Xuất toàn bộ bảng của database trung tâm hiện tại ra file <code>.sql</code> để lưu trữ / khôi phục khi cần.</p>',
        ],
        [
            'title' => 'File lưu ở đâu?',
            'body' => '<p class="mb-0">Shared hosting không dùng <code>storage:link</code>. File nằm tại <code>public/storage/{{ $tenant }}/backups/</code>. Thư mục backups bị chặn truy cập trực tiếp — chỉ tải qua nút <em>Tải về</em> (đã đăng nhập).</p>',
        ],
        [
            'title' => 'Lưu ý',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li>Backup chứa dữ liệu nhạy cảm — không chia sẻ file lung tung.</li>'
                .'<li>Nên tải về máy và xóa bản cũ trên server định kỳ.</li>'
                .'<li>Mỗi subdomain / tenant có thư mục backup riêng.</li>'
                .'</ul>',
        ],
    ];
    $fmtSize = function (int $bytes) {
        if ($bytes < 1024) return $bytes.' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1).' KB';
        return round($bytes / 1048576, 2).' MB';
    };
@endphp

<div class="page-card">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Backup dữ liệu</h5>
            <small class="text-muted">
                Tenant <code>{{ $tenant }}</code>
                · DB <code>{{ $database }}</code>
                · Lưu tại <code>public/storage/{{ $tenant }}/backups/</code>
            </small>
        </div>
        <div class="d-flex align-items-center" style="gap:.5rem">
            <form method="POST" action="{{ route('admin.backups.store') }}" onsubmit="return confirm('Tạo bản backup SQL database hiện tại?');">
                @csrf
                <button class="btn btn-primary btn-sm"><i class="bi bi-download"></i> Tạo backup SQL</button>
            </form>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalBackupsHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>File</th>
                    <th>Kích thước</th>
                    <th>Thời gian</th>
                    <th style="width:160px"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($files as $file)
                    <tr>
                        <td><code>{{ $file['filename'] }}</code></td>
                        <td>{{ $fmtSize($file['size']) }}</td>
                        <td>{{ $file['modified_at'] }}</td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.backups.download', $file['filename']) }}">
                                <i class="bi bi-download"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.backups.destroy', $file['filename']) }}" class="d-inline" onsubmit="return confirm('Xóa file backup này?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Chưa có bản backup. Bấm <strong>Tạo backup SQL</strong> để xuất lần đầu.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.page_help', [
    'modalId' => 'modalBackupsHelp',
    'title' => 'Hướng dẫn — Backup dữ liệu',
    'items' => $helpItems,
])
@endsection
