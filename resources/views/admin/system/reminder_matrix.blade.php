@extends('layouts.admin')

@section('title', 'Ma trận nhắc việc')

@section('content')
<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Ma trận nhắc việc &amp; tự động hóa</h5>
            <small class="text-muted">
                Nguồn cấu hình: <code>config/reminder_matrix.php</code> — tick qua
                <code>scheduler/tick</code> (cron hoặc mở app).
            </small>
        </div>
        <a href="{{ route('admin.settings.edit') }}" class="btn btn-sm btn-outline-secondary">Cài đặt</a>
    </div>
    <div class="card-body-custom">
        <div class="alert alert-light border small mb-3">
            <strong>Tick scheduler:</strong> cron <code>GET /scheduler/tick?key=…</code> hoặc trình duyệt
            <code>POST /admin/scheduler/tick</code> khi mở <code>/home</code> / <code>/admin</code>.
            Mỗi lệnh dưới đây chạy khi <em>đến lượt</em> và <em>chưa chạy</em> trong chu kỳ (ngày / giờ / tháng).
            Cột <em>Đã chạy (cache)</em> phản ánh lần ghi nhận gần nhất trên server này.
        </div>

        <h6 class="font-weight-bold mb-2">A. Lệnh Artisan (scheduler tick)</h6>
        @foreach($schedulerGroups as $group => $jobs)
            <div class="mb-3">
                <div class="text-muted small font-weight-bold text-uppercase mb-1">{{ $group }}</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 reminder-matrix-table">
                        <thead class="thead-light">
                        <tr>
                            <th>Tên</th>
                            <th>Lệnh</th>
                            <th>Lịch</th>
                            <th>Đầu ra</th>
                            <th>source_type</th>
                            <th>Giao / quyền</th>
                            <th>Thông báo</th>
                            <th>Đóng khi</th>
                            <th>Đã chạy (cache)</th>
                            <th>Ghi chú</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($jobs as $job)
                            <tr>
                                <td class="font-weight-bold">{{ $job['label'] ?? '—' }}</td>
                                <td><code class="small">{{ $job['command'] ?? '' }}</code></td>
                                <td class="small">{{ $job['schedule_label'] ?? '—' }}</td>
                                <td><span class="badge badge-light border">{{ $job['output_label'] ?? '—' }}</span></td>
                                <td class="small"><code>{{ $job['source_type'] ?? '—' }}</code></td>
                                <td class="small">{{ $job['assignee'] ?? '—' }}</td>
                                <td class="small">{{ $job['notifications'] ?? '—' }}</td>
                                <td class="small">{{ $job['close_when'] ?? '—' }}</td>
                                <td class="small text-nowrap">
                                    @if(!empty($job['ran_at']))
                                        <span class="text-success">{{ $job['ran_at'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $job['notes'] ?? '' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <h6 class="font-weight-bold mb-2 mt-4">B. Kích hoạt theo thao tác (không qua tick)</h6>
        @foreach($eventGroups as $group => $entries)
            <div class="mb-3">
                <div class="text-muted small font-weight-bold text-uppercase mb-1">{{ $group }}</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 reminder-matrix-table">
                        <thead class="thead-light">
                        <tr>
                            <th>Tên</th>
                            <th>Kích hoạt</th>
                            <th>Đầu ra</th>
                            <th>source_type</th>
                            <th>Giao / quyền</th>
                            <th>Thông báo</th>
                            <th>Đóng khi</th>
                            <th>Ghi chú</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td class="font-weight-bold">{{ $entry['label'] ?? '—' }}</td>
                                <td class="small">{{ $entry['trigger'] ?? '—' }}</td>
                                <td><span class="badge badge-light border">{{ $entry['output_label'] ?? '—' }}</span></td>
                                <td class="small"><code>{{ $entry['source_type'] ?? '—' }}</code></td>
                                <td class="small">{{ $entry['assignee'] ?? '—' }}</td>
                                <td class="small">{{ $entry['notifications'] ?? '—' }}</td>
                                <td class="small">{{ $entry['close_when'] ?? '—' }}</td>
                                <td class="small text-muted">{{ $entry['notes'] ?? '' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <p class="small text-muted mb-0 mt-3">
            Tài liệu HTML: thư mục <code>docs/tu-dong-nhac-viec.html</code> trong mã nguồn.
            Sửa lịch / mô tả: chỉnh <code>config/reminder_matrix.php</code> rồi deploy.
        </p>
    </div>
</div>
<style>
.reminder-matrix-table th { font-size: .72rem; white-space: nowrap; }
.reminder-matrix-table td { vertical-align: top; font-size: .82rem; }
</style>
@endsection
