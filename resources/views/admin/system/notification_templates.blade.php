@extends('layouts.admin')

@section('title', 'Mẫu thông báo')

@section('content')
@php
    $helpItems = [
        [
            'title' => 'Mẫu dùng chung Email + Zalo',
            'body' => '<p class="mb-0">Một mẫu (vd <code>payment_success</code>, <code>session_reminder</code>) dùng cho Email và Zalo ZNS. Email thay <code>'.'{'.'{ten_bien}'.'}'.'}'.'</code> trong nội dung. Zalo: điền <em>Zalo Template ID</em> + JSON biến ZNS — key = tên tham số ZNS, value = <code>'.'{'.'{ten_bien}'.'}'.'}'.'</code> (không cần map trung gian).</p>',
        ],
        [
            'title' => 'Khi nào gửi?',
            'body' => '<ul class="mb-0 pl-3">'
                .'<li><code>payment_success</code>: sau khi ghi nhận thanh toán → HV + PH.</li>'
                .'<li><code>session_reminder</code>: trước buổi học ~2 tiếng (scheduler/cron) → HV + PH.</li>'
                .'<li>Bật kênh trên mẫu + SMTP / ZNS ở Cài đặt (<em>Nhắc lịch học</em> cho Zalo lịch).</li>'
                .'</ul>',
        ],
    ];
@endphp

<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Mẫu thông báo</h5>
            <small class="text-muted">Email &amp; Zalo ZNS — theo từng trung tâm (tenant DB)</small>
        </div>
        <div class="d-flex" style="gap:.5rem">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreateTemplate">+ Thêm mẫu</button>
            <button class="btn btn-sm btn-outline-info" type="button" data-toggle="modal" data-target="#modalTplHelp">
                <i class="bi bi-question-circle"></i> Hướng dẫn
            </button>
        </div>
    </div>
    <div class="card-body-custom">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="mb-3 p-2 bg-light border rounded small">
            <strong>Biến dùng được:</strong>
            @foreach($placeholders as $key => $label)
                <code class="mr-1" title="{{ $label }}">{{ '{'.'{'.$key.'}'.'}' }}</code>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tiêu đề</th>
                    <th>Email</th>
                    <th>Zalo</th>
                    <th>ZNS Template ID</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($templates as $tpl)
                    <tr>
                        <td><code>{{ $tpl->code }}</code></td>
                        <td>{{ $tpl->title }}</td>
                        <td>
                            <span class="badge badge-{{ $tpl->is_active_email ? 'success' : 'secondary' }}">
                                {{ $tpl->is_active_email ? 'Bật' : 'Tắt' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $tpl->is_active_zalo ? 'success' : 'secondary' }}">
                                {{ $tpl->is_active_zalo ? 'Bật' : 'Tắt' }}
                            </span>
                        </td>
                        <td><code>{{ $tpl->zalo_template_id ?: '—' }}</code></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editTpl{{ $tpl->id }}"><i class="bi bi-pencil"></i></button>
                            @if($tpl->code !== 'payment_success')
                            <form method="POST" action="{{ route('admin.notification-templates.destroy', $tpl) }}" class="d-inline" onsubmit="return confirm('Xóa mẫu?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có mẫu.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="card-header-custom">
        <strong>Lịch sử gửi gần đây</strong>
    </div>
    <div class="card-body-custom">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Mẫu</th>
                    <th>Kênh</th>
                    <th>Người nhận</th>
                    <th>Liên hệ</th>
                    <th>TT</th>
                    <th>Lỗi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="small text-nowrap">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                        <td><code>{{ $log->template_code }}</code></td>
                        <td>{{ $log->channelLabel() }}</td>
                        <td>{{ $log->recipient_name }} <span class="text-muted small">({{ $log->recipient_type }})</span></td>
                        <td class="small">{{ $log->recipient_contact }}</td>
                        <td>
                            <span class="badge badge-{{ $log->status === 'sent' ? 'success' : ($log->status === 'failed' ? 'danger' : 'secondary') }}">
                                {{ $log->statusLabel() }}
                            </span>
                        </td>
                        <td class="small text-danger">{{ \Illuminate\Support\Str::limit($log->error_message, 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Chưa có lịch sử gửi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.system._notification_template_form', ['modalId' => 'modalCreateTemplate', 'template' => null, 'action' => route('admin.notification-templates.store'), 'method' => 'POST'])

@foreach($templates as $tpl)
    @include('admin.system._notification_template_form', [
        'modalId' => 'editTpl'.$tpl->id,
        'template' => $tpl,
        'action' => route('admin.notification-templates.update', $tpl),
        'method' => 'PUT',
    ])
@endforeach

@include('partials.page_help', [
    'modalId' => 'modalTplHelp',
    'title' => 'Hướng dẫn — Mẫu thông báo',
    'items' => $helpItems,
])
@endsection
