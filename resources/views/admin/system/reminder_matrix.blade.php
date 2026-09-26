@extends('layouts.admin')

@section('title', 'Ma trận nhắc việc')

@section('content')
<div class="page-card mb-3">
    <div class="card-header-custom">
        <div>
            <h5 class="mb-0 font-weight-bold">Ma trận nhắc việc</h5>
            <small class="text-muted">Tổng hợp việc và thông báo hệ thống tự tạo theo lịch hoặc khi bạn thao tác trên phần mềm.</small>
        </div>
        <a href="{{ route('admin.settings.edit') }}" class="btn btn-sm btn-outline-secondary">Cài đặt</a>
    </div>
    <div class="card-body-custom">
        <div class="alert alert-light border small mb-3 mb-md-4">
            Hệ thống tự chạy nền khi có người mở <strong>Bàn làm việc</strong> hoặc <strong>Admin</strong>
            (hoặc theo lịch server nếu trung tâm đã cấu hình).
            Mỗi dòng dưới đây chạy đúng <em>khung giờ / ngày</em> và tối đa một lần trong chu kỳ tương ứng.
        </div>

        <h6 class="font-weight-bold mb-2">1. Nhắc theo lịch (tự động mỗi ngày / tuần / tháng)</h6>
        @foreach($schedulerGroups as $group => $jobs)
            <div class="mb-4">
                <div class="text-muted small font-weight-bold mb-2">{{ $group }}</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 reminder-matrix-table">
                        <thead class="thead-light">
                        <tr>
                            <th style="min-width:11rem">Việc / nhắc</th>
                            <th style="min-width:12rem">Khi nào</th>
                            <th style="min-width:10rem">Ai nhận</th>
                            <th style="min-width:12rem">Hình thức</th>
                            <th style="min-width:9rem">Coi là xong khi</th>
                            <th style="min-width:7rem">Lần chạy gần nhất</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($jobs as $job)
                            <tr>
                                <td class="font-weight-bold">{{ $job['label'] ?? '—' }}</td>
                                <td class="small">
                                    {{ $job['schedule_label'] ?? '—' }}
                                    @if(!empty($job['notes']))
                                        <div class="text-muted mt-1">{{ $job['notes'] }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $job['assignee'] ?? '—' }}</td>
                                <td class="small">{{ $job['delivery'] ?? '—' }}</td>
                                <td class="small">{{ $job['close_when'] ?? '—' }}</td>
                                <td class="small text-nowrap">
                                    @if(!empty($job['ran_at']))
                                        <span class="text-success">{{ $job['ran_at'] }}</span>
                                    @else
                                        <span class="text-muted">Chưa trong kỳ này</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <h6 class="font-weight-bold mb-2 mt-2">2. Nhắc ngay khi thao tác (lưu phiếu, gán lead, thu tiền…)</h6>
        @foreach($eventGroups as $group => $entries)
            <div class="mb-4">
                <div class="text-muted small font-weight-bold mb-2">{{ $group }}</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 reminder-matrix-table">
                        <thead class="thead-light">
                        <tr>
                            <th style="min-width:11rem">Việc / nhắc</th>
                            <th style="min-width:12rem">Khi nào</th>
                            <th style="min-width:10rem">Ai nhận</th>
                            <th style="min-width:12rem">Hình thức</th>
                            <th style="min-width:9rem">Coi là xong khi</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($entries as $entry)
                            <tr>
                                <td class="font-weight-bold">{{ $entry['label'] ?? '—' }}</td>
                                <td class="small">
                                    {{ $entry['trigger'] ?? '—' }}
                                    @if(!empty($entry['notes']))
                                        <div class="text-muted mt-1">{{ $entry['notes'] }}</div>
                                    @endif
                                </td>
                                <td class="small">{{ $entry['assignee'] ?? '—' }}</td>
                                <td class="small">{{ $entry['delivery'] ?? '—' }}</td>
                                <td class="small">{{ $entry['close_when'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</div>
<style>
.reminder-matrix-table th { font-size: .78rem; vertical-align: middle; }
.reminder-matrix-table td { vertical-align: top; font-size: .88rem; }
</style>
@endsection
